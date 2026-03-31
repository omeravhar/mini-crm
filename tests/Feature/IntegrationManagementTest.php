<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookEvent;
use App\Models\Integration;
use App\Models\IntegrationFormMapping;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_generic_webhook_logs_event_and_queues_processing_job(): void
    {
        Queue::fake();

        $integration = Integration::create([
            'name' => 'Generic Source',
            'platform' => 'generic',
            'status' => 'active',
        ]);

        $this->postJson(route('webhooks.generic.receive', ['integration' => $integration->webhook_key]), [
            'event_type' => 'lead.created',
            'external_form_id' => 'form_123',
            'lead' => [
                'external_lead_id' => 'lead_123',
                'full_name' => 'Dana Levi',
                'email' => 'dana@example.com',
            ],
        ])
            ->assertAccepted()
            ->assertJsonFragment([
                'status' => 'accepted',
            ]);

        $event = WebhookEvent::firstOrFail();

        $this->assertSame('generic', $event->platform);
        $this->assertSame('lead.created', $event->event_type);
        $this->assertSame('form_123', $event->external_form_id);

        Queue::assertPushed(ProcessWebhookEvent::class, function (ProcessWebhookEvent $job) use ($event) {
            return $job->webhookEventId === $event->id;
        });
    }

    public function test_processing_webhook_event_creates_or_updates_lead(): void
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
            'external_form_name' => 'Demo Form',
            'default_owner_id' => $owner->id,
            'is_active' => true,
            'field_map' => [
                'company' => 'company_name',
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
                    'source_channel' => 'facebook',
                    'notes' => 'Imported from social',
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessWebhookEvent($event->id))->handle();

        $lead = Lead::firstOrFail();
        $event->refresh();

        $this->assertSame('Noa', $lead->first_name);
        $this->assertSame('Cohen', $lead->last_name);
        $this->assertSame('noa@example.com', $lead->email);
        $this->assertSame('Northwind', $lead->company);
        $this->assertSame('generic', $lead->source_platform);
        $this->assertSame('facebook', $lead->source_channel);
        $this->assertSame('lead_999', $lead->external_lead_id);
        $this->assertSame($owner->id, $lead->owner_id);
        $this->assertSame('processed', $event->status);
        $this->assertSame($lead->id, $event->lead_id);
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
}
