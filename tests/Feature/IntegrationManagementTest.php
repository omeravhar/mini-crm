<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookEvent;
use App\Models\Integration;
use App\Models\IntegrationFormMapping;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IntegrationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_integration_and_form_mapping(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor']);

        $this->actingAs($admin)
            ->post(route('admin.integrations.store'), [
                'name' => 'Meta Leads',
                'platform' => 'meta',
                'status' => 'active',
                'verify_token' => 'verify-123',
                'external_account_id' => 'act_123',
                'external_page_id' => 'page_123',
                'notes' => 'Primary connection',
            ])
            ->assertRedirect();

        $integration = Integration::firstOrFail();

        $this->assertSame('Meta Leads', $integration->name);
        $this->assertSame('meta', $integration->platform);
        $this->assertNotEmpty($integration->webhook_key);

        $this->actingAs($admin)
            ->post(route('admin.integrations.mappings.store', $integration), [
                'external_form_id' => 'form_456',
                'external_form_name' => 'Winter Campaign',
                'default_owner_id' => $owner->id,
                'is_active' => '1',
                'field_map_json' => '{"email":"email","phone":"phone"}',
                'notes' => 'Default mapping',
            ])
            ->assertRedirect();

        $mapping = IntegrationFormMapping::firstOrFail();

        $this->assertSame($integration->id, $mapping->integration_id);
        $this->assertSame($owner->id, $mapping->default_owner_id);
        $this->assertSame('phone', $mapping->field_map['phone']);
    }

    public function test_meta_verify_endpoint_returns_challenge_for_valid_token(): void
    {
        $integration = Integration::create([
            'name' => 'Meta Verify',
            'platform' => 'meta',
            'status' => 'active',
            'verify_token' => 'meta-secret',
        ]);

        $this->get(route('webhooks.meta.verify', [
            'integration' => $integration->webhook_key,
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'meta-secret',
            'hub_challenge' => 'challenge-token',
        ]))
            ->assertOk()
            ->assertSeeText('challenge-token');
    }

    public function test_generic_webhook_processes_lead_immediately(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $this->withHeaders([
            'Origin' => 'https://example.com',
        ])->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'event_type' => 'lead.created',
            'external_form_id' => 'form_123',
            'lead' => [
                'external_lead_id' => 'lead_123',
                'full_name' => 'Dana Levi',
                'email' => 'dana@example.com',
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'status' => 'success',
                'ok' => true,
            ])
            ->assertHeader('Access-Control-Allow-Origin', '*');

        $event = WebhookEvent::firstOrFail();

        $this->assertSame('generic', $event->platform);
        $this->assertSame('lead.created', $event->event_type);
        $this->assertSame('form_123', $event->external_form_id);
        $this->assertSame('processed', $event->fresh()->status);
        $this->assertDatabaseHas('leads', [
            'source_platform' => 'generic',
            'external_lead_id' => 'lead_123',
            'email' => 'dana@example.com',
        ]);
    }

    public function test_generic_webhook_processes_flat_payload_immediately(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $this->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'full_name' => 'Dana Levi',
            'email' => 'dana@example.com',
            'phone' => '0501234567',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'status' => 'success',
                'ok' => true,
            ]);

        $event = WebhookEvent::firstOrFail();
        $lead = Lead::firstOrFail();

        $this->assertSame('processed', $event->fresh()->status);
        $this->assertSame('Dana', $lead->first_name);
        $this->assertSame('Levi', $lead->last_name);
        $this->assertSame('dana@example.com', $lead->email);
        $this->assertSame('0501234567', $lead->phone);
        $this->assertSame('generic', $lead->source_platform);
    }

    public function test_generic_webhook_processes_mapped_payload_without_lead_wrapper(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => 'form_123',
            'external_form_name' => 'Website Form',
            'is_active' => true,
            'field_map' => [
                'full_name' => 'fields.name',
                'email' => 'fields.email',
                'phone' => 'fields.field_d4085dd',
                'notes' => 'fields.message',
            ],
        ]);

        $this->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'external_form_id' => 'form_123',
            'fields' => [
                'name' => 'Dana Levi',
                'email' => 'dana@example.com',
                'field_d4085dd' => '0501234567',
                'message' => 'Needs a callback',
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'status' => 'success',
                'ok' => true,
            ]);

        $event = WebhookEvent::firstOrFail();
        $lead = Lead::firstOrFail();

        $this->assertSame('processed', $event->fresh()->status);
        $this->assertSame('Dana', $lead->first_name);
        $this->assertSame('Levi', $lead->last_name);
        $this->assertSame('dana@example.com', $lead->email);
        $this->assertSame('0501234567', $lead->phone);
        $this->assertSame('Needs a callback', $lead->notes);
        $this->assertSame('generic', $lead->source_platform);
        $this->assertSame('form_123', $lead->external_form_id);
    }

    public function test_generic_webhook_uses_single_active_mapping_without_form_id(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => 'website_form_main',
            'external_form_name' => 'Website Form',
            'is_active' => true,
            'field_map' => [
                'full_name' => 'full_name',
                'email' => 'email',
                'phone' => 'phone',
                'interested_in' => 'message',
            ],
        ]);

        $this->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'full_name' => 'Dana Levi',
            'email' => 'dana@example.com',
            'phone' => '0501234567',
            'message' => 'Kitchen project',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'status' => 'success',
                'ok' => true,
            ]);

        $event = WebhookEvent::firstOrFail();
        $lead = Lead::firstOrFail();

        $this->assertSame('processed', $event->fresh()->status);
        $this->assertSame('website_form_main', $event->external_form_id);
        $this->assertSame('website_form_main', $lead->external_form_id);
        $this->assertSame('Kitchen project', $lead->interested_in);
    }

    public function test_generic_webhook_creates_lead_with_name_only_when_no_identifiers_exist(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $this->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'full_name' => 'Dana Levi',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'status' => 'success',
                'ok' => true,
            ]);

        $event = WebhookEvent::firstOrFail();
        $lead = Lead::firstOrFail();

        $this->assertSame('processed', $event->fresh()->status);
        $this->assertSame('Dana', $lead->first_name);
        $this->assertSame('Levi', $lead->last_name);
        $this->assertNull($lead->email);
        $this->assertNull($lead->phone);
        $this->assertSame('generic', $lead->source_platform);
    }

    public function test_generic_webhook_allows_cors_preflight_requests(): void
    {
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $this->call('OPTIONS', route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [], [], [], [
            'HTTP_ORIGIN' => 'https://example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_google_webhook_returns_ok_and_processes_lead_immediately(): void
    {
        $integration = Integration::create([
            'name' => 'Google Leads',
            'platform' => 'google',
            'status' => 'active',
            'webhook_secret' => 'google-key-123',
        ]);

        $this->postJson(route('webhooks.google.receive', ['integration' => $integration->webhook_key]), [
            'lead_id' => 'google-lead-123',
            'form_id' => 1234,
            'campaign_id' => 555,
            'google_key' => 'google-key-123',
            'user_column_data' => [
                [
                    'column_id' => 'FULL_NAME',
                    'string_value' => 'Dana Levi',
                ],
                [
                    'column_id' => 'EMAIL',
                    'string_value' => 'dana@example.com',
                ],
            ],
        ])
            ->assertOk()
            ->assertContent('{}');

        $event = WebhookEvent::firstOrFail();

        $this->assertSame('google', $event->platform);
        $this->assertSame('google_lead', $event->event_type);
        $this->assertSame('1234', $event->external_form_id);
        $this->assertSame('google-lead-123', $event->external_event_id);
        $this->assertSame('processed', $event->fresh()->status);
        $this->assertDatabaseHas('leads', [
            'source_platform' => 'google',
            'external_lead_id' => 'google-lead-123',
            'email' => 'dana@example.com',
        ]);
    }

    public function test_google_webhook_rejects_invalid_google_key(): void
    {
        $integration = Integration::create([
            'name' => 'Google Leads',
            'platform' => 'google',
            'status' => 'active',
            'webhook_secret' => 'expected-google-key',
        ]);

        $this->postJson(route('webhooks.google.receive', ['integration' => $integration->webhook_key]), [
            'lead_id' => 'google-lead-123',
            'form_id' => 1234,
            'google_key' => 'wrong-key',
            'user_column_data' => [
                [
                    'column_id' => 'EMAIL',
                    'string_value' => 'dana@example.com',
                ],
            ],
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Invalid google_key.');

        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_processing_webhook_event_creates_or_updates_lead(): void
    {
        $owner = User::factory()->create(['role' => 'editor']);
        $receivedAt = now()->subMinutes(5);
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => 'form_123',
            'external_form_name' => 'Demo Form',
            'default_owner_id' => $owner->id,
            'is_active' => true,
            'field_map' => [
                'company' => 'company_name',
                'interested_in' => 'interest',
            ],
        ]);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'generic',
            'event_type' => 'lead.created',
            'external_form_id' => 'form_123',
            'status' => 'received',
            'payload' => [
                'event_type' => 'lead.created',
                'external_form_id' => 'form_123',
                'lead' => [
                    'external_lead_id' => 'lead_999',
                    'full_name' => 'Noa Cohen',
                    'email' => 'noa@example.com',
                    'phone' => '0509999999',
                    'company_name' => 'Northwind',
                    'interest' => 'Kitchen remodeling',
                    'campaign_name' => 'Meta Kitchen April',
                    'external_campaign_id' => 'cmp_777',
                    'source_channel' => 'facebook',
                    'notes' => 'Imported from social',
                ],
            ],
            'received_at' => $receivedAt,
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $lead = Lead::firstOrFail();
        $event->refresh();

        $this->assertSame('Noa', $lead->first_name);
        $this->assertSame('Cohen', $lead->last_name);
        $this->assertSame('noa@example.com', $lead->email);
        $this->assertSame('Northwind', $lead->company);
        $this->assertSame('Kitchen remodeling', $lead->interested_in);
        $this->assertSame('new', $lead->lead_type);
        $this->assertSame('Meta Kitchen April', $lead->external_campaign_name);
        $this->assertSame('cmp_777', $lead->external_campaign_id);
        $this->assertSame('generic', $lead->source_platform);
        $this->assertSame('facebook', $lead->source_channel);
        $this->assertSame('lead_999', $lead->external_lead_id);
        $this->assertSame($receivedAt->format('Y-m-d H:i:s'), $lead->received_at?->format('Y-m-d H:i:s'));
        $this->assertSame($owner->id, $lead->owner_id);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => LeadAssignedNotification::class,
        ]);
        $this->assertSame('processed', $event->status);
        $this->assertSame($lead->id, $event->lead_id);
    }

    public function test_processing_existing_webhook_lead_marks_it_as_returning(): void
    {
        $owner = User::factory()->create(['role' => 'editor']);
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => 'form_123',
            'default_owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $lead = Lead::create([
            'first_name' => 'Noa',
            'last_name' => 'Cohen',
            'email' => 'noa@example.com',
            'phone' => '0509999999',
            'lead_type' => 'new',
            'created_by' => $owner->id,
            'owner_id' => $owner->id,
            'status' => 'new',
            'priority' => 'medium',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'generic',
            'event_type' => 'lead.created',
            'external_form_id' => 'form_123',
            'status' => 'received',
            'payload' => [
                'lead' => [
                    'email' => 'noa@example.com',
                    'phone' => '0509999999',
                    'full_name' => 'Noa Cohen',
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $lead->refresh();

        $this->assertSame('returning', $lead->lead_type);
    }

    public function test_pending_fetch_webhook_is_written_to_log_with_payload(): void
    {
        Log::spy();

        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'generic',
            'event_type' => 'generic_event',
            'status' => 'received',
            'payload' => [
                'foo' => 'bar',
            ],
            'received_at' => now(),
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $event->refresh();

        $this->assertSame('pending_fetch', $event->status);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($event) {
                return $message === 'Webhook event marked as pending_fetch.'
                    && $context['webhook_event_id'] === $event->id
                    && $context['platform'] === 'generic'
                    && ($context['payload']['foo'] ?? null) === 'bar';
            });
    }

    public function test_processed_webhook_is_written_to_log_with_payload(): void
    {
        Log::spy();

        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'generic',
            'event_type' => 'generic_event',
            'status' => 'received',
            'payload' => [
                'full_name' => 'Dana Levi',
                'email' => 'dana@example.com',
                'phone' => '0501234567',
            ],
            'received_at' => now(),
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $event->refresh();

        $this->assertSame('processed', $event->status);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($event) {
                return $message === 'Webhook event processed successfully.'
                    && $context['webhook_event_id'] === $event->id
                    && $context['lead_id'] === $event->lead_id
                    && $context['platform'] === 'generic'
                    && ($context['payload']['full_name'] ?? null) === 'Dana Levi'
                    && ($context['normalized_lead']['email'] ?? null) === 'dana@example.com';
            });
    }

    public function test_processing_google_webhook_event_creates_lead_from_user_column_data(): void
    {
        $owner = User::factory()->create(['role' => 'editor']);
        $integration = Integration::create([
            'name' => 'Google Source',
            'platform' => 'google',
            'status' => 'active',
            'webhook_secret' => 'google-key-123',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => '1234',
            'external_form_name' => 'Google Form',
            'default_owner_id' => $owner->id,
            'is_active' => true,
            'field_map' => [
                'notes' => 'google_fields.PREFERRED_CONTACT_METHOD',
            ],
        ]);

        $event = WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'google',
            'event_type' => 'google_lead',
            'external_event_id' => 'google-lead-123',
            'external_form_id' => '1234',
            'status' => 'received',
            'payload' => [
                'lead_id' => 'google-lead-123',
                'form_id' => 1234,
                'campaign_id' => 999,
                'creative_id' => 321,
                'google_key' => 'google-key-123',
                'user_column_data' => [
                    [
                        'column_id' => 'FULL_NAME',
                        'string_value' => 'Dana Levi',
                    ],
                    [
                        'column_id' => 'EMAIL',
                        'string_value' => 'dana@example.com',
                    ],
                    [
                        'column_id' => 'PHONE_NUMBER',
                        'string_value' => '+972501234567',
                    ],
                    [
                        'column_id' => 'COMPANY_NAME',
                        'string_value' => 'Ritzufim LTD',
                    ],
                    [
                        'column_id' => 'PRODUCT',
                        'string_value' => 'Premium Tiles',
                    ],
                    [
                        'column_id' => 'PREFERRED_CONTACT_METHOD',
                        'string_value' => 'Phone',
                    ],
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $lead = Lead::firstOrFail();

        $this->assertSame('Dana', $lead->first_name);
        $this->assertSame('Levi', $lead->last_name);
        $this->assertSame('dana@example.com', $lead->email);
        $this->assertSame('+972501234567', $lead->phone);
        $this->assertSame('Ritzufim LTD', $lead->company);
        $this->assertSame('Premium Tiles', $lead->interested_in);
        $this->assertSame('Phone', $lead->notes);
        $this->assertSame('google', $lead->source_platform);
        $this->assertSame('google_ads', $lead->source_channel);
        $this->assertSame('google-lead-123', $lead->external_lead_id);
        $this->assertSame('1234', $lead->external_form_id);
        $this->assertSame('999', $lead->external_campaign_id);
        $this->assertSame('321', $lead->external_ad_id);
        $this->assertSame($owner->id, $lead->owner_id);
    }

    public function test_admin_can_view_integrations_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integration = Integration::create([
            'name' => 'TikTok Leads',
            'platform' => 'tiktok',
            'status' => 'draft',
        ]);

        WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'tiktok',
            'event_type' => 'lead.created',
            'status' => 'failed',
            'payload' => ['event_type' => 'lead.created'],
            'error_message' => 'Signature mismatch',
            'received_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.integrations.index'))
            ->assertOk()
            ->assertSee('אינטגרציות ולוגי Webhook')
            ->assertSee('TikTok Leads')
            ->assertSee('Signature mismatch');
    }

    public function test_dashboard_shows_payload_preview_for_pending_fetch_webhooks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        WebhookEvent::create([
            'integration_id' => $integration->id,
            'platform' => 'generic',
            'event_type' => 'generic_event',
            'status' => 'pending_fetch',
            'payload' => [
                'full_name' => 'Dana Levi',
                'email' => 'dana@example.com',
            ],
            'error_message' => 'Missing identifiers',
            'received_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.integrations.index'))
            ->assertOk()
            ->assertSee('Missing identifiers')
            ->assertSee('full_name')
            ->assertSee('Dana Levi')
            ->assertSee('dana@example.com');
    }

    public function test_dashboard_shows_payload_preview_for_processed_webhooks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'first_name' => 'Stored',
            'last_name' => 'Lead',
            'email' => 'stored@example.com',
            'phone' => '0507777777',
            'status' => 'new',
            'priority' => 'medium',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        WebhookEvent::create([
            'integration_id' => $integration->id,
            'lead_id' => $lead->id,
            'platform' => 'generic',
            'event_type' => 'generic_event',
            'status' => 'processed',
            'payload' => [
                'full_name' => 'Dana Levi',
                'interest' => 'Kitchen',
            ],
            'received_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.integrations.index'))
            ->assertOk()
            ->assertSee('הצג payload שהתקבל')
            ->assertSee('full_name')
            ->assertSee('Dana Levi')
            ->assertSee('Kitchen');
    }

    public function test_admin_can_run_meta_connection_test_for_existing_integration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integration = Integration::create([
            'name' => 'Meta Leads',
            'platform' => 'meta',
            'status' => 'active',
            'verify_token' => 'verify-123',
            'external_page_id' => '12039931243197',
            'access_token' => 'stored-meta-token',
        ]);

        IntegrationFormMapping::create([
            'integration_id' => $integration->id,
            'external_form_id' => '416356547604942',
            'external_form_name' => 'Demo Form',
            'is_active' => true,
        ]);

        Http::fake(function (HttpRequest $request) {
            if (str_ends_with($request->url(), '/me?fields=id%2Cname&access_token=stored-meta-token')) {
                return Http::response([
                    'id' => 'user_1',
                    'name' => 'Meta Tester',
                ]);
            }

            if (str_contains($request->url(), '/12039931243197?fields=id%2Cname&access_token=stored-meta-token')) {
                return Http::response([
                    'id' => '12039931243197',
                    'name' => 'Ritzufim Page',
                ]);
            }

            if (str_contains($request->url(), '/12039931243197/leadgen_forms?fields=id%2Cname%2Cstatus&limit=100&access_token=stored-meta-token')) {
                return Http::response([
                    'data' => [
                        [
                            'id' => '416356547604942',
                            'name' => 'Ritzufim Form',
                            'status' => 'ACTIVE',
                        ],
                    ],
                ]);
            }

            return Http::response([], 404);
        });

        $this->actingAs($admin)
            ->postJson(route('admin.integrations.test.saved', $integration), [
                'name' => 'Meta Leads',
                'platform' => 'meta',
                'status' => 'active',
                'verify_token' => 'verify-123',
                'external_page_id' => '12039931243197',
                'access_token' => '',
                'refresh_token' => '',
                'webhook_secret' => '',
                'config_json' => '',
                'notes' => '',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('ok', true);
    }

    public function test_meta_connection_test_reports_missing_access_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.integrations.test'), [
                'name' => 'Meta Leads',
                'platform' => 'meta',
                'status' => 'active',
                'verify_token' => 'verify-123',
                'external_page_id' => '12039931243197',
                'access_token' => '',
                'refresh_token' => '',
                'webhook_secret' => '',
                'config_json' => '',
                'notes' => '',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('ok', false);
    }

    public function test_google_connection_test_requires_webhook_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.integrations.test'), [
                'name' => 'Google Leads',
                'platform' => 'google',
                'status' => 'active',
                'verify_token' => '',
                'external_page_id' => '',
                'access_token' => '',
                'refresh_token' => '',
                'webhook_secret' => '',
                'config_json' => '',
                'notes' => '',
            ])
            ->assertOk()
            ->assertJsonPath('platform', 'google')
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('ok', false);
    }
}
