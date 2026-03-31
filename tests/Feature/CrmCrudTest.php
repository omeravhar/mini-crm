<?php

namespace Tests\Feature;

use App\Mail\LeadFollowUpInviteMail;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CrmCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_convert_a_lead(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor']);

        $this->actingAs($admin)
            ->post(route('admin.saveNewLead'), [
                'first_name' => 'Dana',
                'last_name' => 'Levi',
                'email' => 'dana@example.com',
                'phone' => '0501112233',
                'company' => 'Northwind',
                'job_title' => 'Operations Manager',
                'source' => 'website',
                'status' => 'new',
                'priority' => 'high',
                'expected_value' => 15000,
                'follow_up' => '2026-04-10',
                'follow_up_time' => '10:30',
                'tags_text' => 'enterprise, urgent',
                'street' => '1 Main St',
                'city' => 'Tel Aviv',
                'zip' => '61000',
                'country' => 'Israel',
                'notes' => 'High intent lead',
                'owner_id' => $owner->id,
                'pipeline' => 'default',
                'stage' => 'lead',
                'visibility' => 'team',
            ])
            ->assertRedirect(route('admin.leads.index'));

        $lead = Lead::firstOrFail();

        $this->assertSame($admin->id, $lead->created_by);
        $this->assertSame($owner->id, $lead->owner_id);

        $this->actingAs($admin)
            ->put(route('leads.update', $lead), [
                'first_name' => 'Dana',
                'last_name' => 'Levi',
                'email' => 'dana@example.com',
                'phone' => '0501112233',
                'company' => 'Northwind',
                'job_title' => 'Operations Manager',
                'source' => 'website',
                'status' => 'proposal',
                'priority' => 'high',
                'expected_value' => 17500,
                'follow_up' => '2026-04-12',
                'follow_up_time' => '11:15',
                'tags_text' => 'proposal',
                'street' => '1 Main St',
                'city' => 'Tel Aviv',
                'zip' => '61000',
                'country' => 'Israel',
                'notes' => 'Updated lead',
                'owner_id' => $owner->id,
                'pipeline' => 'enterprise',
                'stage' => 'negotiation',
                'visibility' => 'team',
            ])
            ->assertRedirect(route('admin.leads.index'));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'proposal',
            'pipeline' => 'enterprise',
            'stage' => 'negotiation',
        ]);

        $this->from(route('admin.leads.index'))
            ->actingAs($admin)
            ->post(route('leads.convert', $lead))
            ->assertRedirect(route('admin.leads.index'));

        $this->assertDatabaseHas('customers', [
            'lead_id' => $lead->id,
            'email' => 'dana@example.com',
        ]);
    }

    public function test_authenticated_user_can_create_update_and_delete_a_customer(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'first_name' => 'Yoav',
                'last_name' => 'Cohen',
                'email' => 'yoav@example.com',
                'phone' => '0501234567',
                'company' => 'Acme',
                'job_title' => 'CEO',
                'website' => 'https://example.com',
                'street' => '10 Market St',
                'city' => 'Haifa',
                'zip' => '33000',
                'country' => 'Israel',
                'notes' => 'VIP customer',
            ])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::firstOrFail();

        $this->assertSame($user->id, $customer->owner_id);

        $this->actingAs($user)
            ->put(route('customers.update', $customer), [
                'first_name' => 'Yoav',
                'last_name' => 'Cohen',
                'email' => 'yoav@example.com',
                'phone' => '0509999999',
                'company' => 'Acme Updated',
                'job_title' => 'CEO',
                'website' => 'https://example.com',
                'street' => '10 Market St',
                'city' => 'Haifa',
                'zip' => '33000',
                'country' => 'Israel',
                'notes' => 'Updated customer',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '0509999999',
            'company' => 'Acme Updated',
        ]);

        $this->actingAs($user)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Support User',
                'email' => 'support@example.com',
                'role' => 'viewer',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'support@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Support User',
                'email' => 'support@example.com',
                'role' => 'editor',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Support User',
            'role' => 'editor',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_leads_fragment_returns_rendered_html_for_live_refresh(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor', 'name' => 'Dana Owner']);

        Lead::create([
            'first_name' => 'Ron',
            'last_name' => 'Levi',
            'email' => 'ron@example.com',
            'owner_id' => $owner->id,
            'created_by' => $admin->id,
            'status' => 'new',
            'priority' => 'medium',
            'follow_up' => '2026-04-10',
            'follow_up_time' => '09:00',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.leads.index', ['fragment' => 1]))
            ->assertOk()
            ->assertSee('Ron Levi');
    }

    public function test_assign_endpoint_returns_json_for_live_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor', 'name' => 'Assigned User']);
        $lead = Lead::create([
            'first_name' => 'Noa',
            'last_name' => 'Shalev',
            'email' => 'noa@example.com',
            'owner_id' => null,
            'created_by' => $admin->id,
            'status' => 'new',
            'priority' => 'medium',
            'follow_up' => '2026-04-10',
            'follow_up_time' => '09:00',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.leads.assign', $lead), [
                'owner_id' => $owner->id,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'שיוך הליד עודכן בהצלחה.',
                'lead_id' => $lead->id,
                'owner_id' => $owner->id,
                'owner_name' => 'Assigned User',
            ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_future_follow_up_update_sends_calendar_invite_to_assigned_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor', 'email' => 'owner@example.com']);
        $lead = Lead::create([
            'first_name' => 'Neta',
            'last_name' => 'Bar',
            'email' => 'neta@example.com',
            'created_by' => $admin->id,
            'status' => 'new',
            'priority' => 'medium',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        $this->actingAs($admin)
            ->put(route('leads.update', $lead), [
                'first_name' => 'Neta',
                'last_name' => 'Bar',
                'email' => 'neta@example.com',
                'phone' => '0501234567',
                'company' => 'Bar Labs',
                'job_title' => 'Manager',
                'source' => 'website',
                'status' => 'contacted',
                'priority' => 'high',
                'expected_value' => 25000,
                'follow_up' => '2026-04-15',
                'follow_up_time' => '14:45',
                'tags_text' => 'priority',
                'street' => '1 Main St',
                'city' => 'Tel Aviv',
                'zip' => '61000',
                'country' => 'Israel',
                'notes' => 'Needs quick response',
                'owner_id' => $owner->id,
                'pipeline' => 'enterprise',
                'stage' => 'negotiation',
                'visibility' => 'team',
            ])
            ->assertRedirect(route('admin.leads.index'));

        Mail::assertQueued(LeadFollowUpInviteMail::class, function (LeadFollowUpInviteMail $mail) use ($lead, $owner) {
            return $mail->hasTo('owner@example.com')
                && $mail->lead->is($lead)
                && $mail->recipient->is($owner)
                && $mail->scheduledAt->format('Y-m-d H:i') === '2026-04-15 14:45';
        });

        $owner->refresh();
        $notification = $owner->notifications()->where('type', LeadFollowUpScheduledNotification::class)->first();

        $this->assertNotNull($notification);
        $this->assertSame($lead->id, data_get($notification?->data, 'lead_id'));
        $this->assertSame('2026-04-15', data_get($notification?->data, 'scheduled_for_date'));
    }

    public function test_due_today_follow_up_is_shown_as_internal_alert(): void
    {
        Carbon::setTestNow('2026-04-15 08:00:00');

        try {
            $user = User::factory()->create(['role' => 'editor']);

            $lead = Lead::create([
                'first_name' => 'Adi',
                'last_name' => 'Mor',
                'email' => 'adi@example.com',
                'owner_id' => $user->id,
                'created_by' => $user->id,
                'status' => 'new',
                'priority' => 'medium',
                'follow_up' => '2026-04-15',
                'follow_up_time' => '09:30',
                'pipeline' => 'default',
                'stage' => 'lead',
                'visibility' => 'team',
            ]);

            $user->notify(new LeadFollowUpScheduledNotification(
                $lead,
                Carbon::parse('2026-04-15 09:30', config('app.timezone'))
            ));

            $this->actingAs($user)
                ->get(route('leads.my'))
                ->assertOk()
                ->assertSee('תזכורות מעקב להיום')
                ->assertSee('Adi Mor')
                ->assertSee('2026-04-15 09:30');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_user_can_mark_today_lead_notification_as_read(): void
    {
        Carbon::setTestNow('2026-04-15 08:00:00');

        try {
            $user = User::factory()->create(['role' => 'editor']);
            $lead = Lead::create([
                'first_name' => 'Yael',
                'last_name' => 'Dayan',
                'email' => 'yael@example.com',
                'owner_id' => $user->id,
                'created_by' => $user->id,
                'status' => 'new',
                'priority' => 'medium',
                'follow_up' => '2026-04-15',
                'follow_up_time' => '12:00',
                'pipeline' => 'default',
                'stage' => 'lead',
                'visibility' => 'team',
            ]);

            $user->notify(new LeadFollowUpScheduledNotification(
                $lead,
                Carbon::parse('2026-04-15 12:00', config('app.timezone'))
            ));

            $notification = $user->unreadNotifications()->firstOrFail();

            $this->actingAs($user)
                ->post(route('notifications.read', $notification))
                ->assertRedirect();

            $this->assertNotNull($notification->fresh()->read_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_filter_leads_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor']);

        Lead::create([
            'first_name' => 'Shira',
            'last_name' => 'Gold',
            'email' => 'shira@example.com',
            'company' => 'Target Co',
            'owner_id' => $owner->id,
            'created_by' => $admin->id,
            'status' => 'new',
            'priority' => 'high',
            'follow_up' => '2026-04-20',
            'follow_up_time' => '09:30',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        Lead::create([
            'first_name' => 'Avi',
            'last_name' => 'Mizrahi',
            'email' => 'avi@example.com',
            'company' => 'Other Co',
            'created_by' => $admin->id,
            'status' => 'lost',
            'priority' => 'low',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.leads.index', [
                'q' => 'Shira',
                'status' => 'new',
                'priority' => 'high',
                'owner_id' => $owner->id,
                'follow_up_scope' => 'upcoming',
            ]))
            ->assertOk()
            ->assertSee('Shira Gold')
            ->assertDontSee('Avi Mizrahi');
    }

    public function test_closing_a_lead_sets_closed_at_timestamp(): void
    {
        Carbon::setTestNow('2026-04-18 13:15:00');

        try {
            $admin = User::factory()->create(['role' => 'admin']);
            $owner = User::factory()->create(['role' => 'editor']);
            $lead = Lead::create([
                'first_name' => 'Tal',
                'last_name' => 'Barak',
                'email' => 'tal@example.com',
                'owner_id' => $owner->id,
                'created_by' => $admin->id,
                'status' => 'proposal',
                'priority' => 'medium',
                'pipeline' => 'default',
                'stage' => 'negotiation',
                'visibility' => 'team',
            ]);

            $this->actingAs($admin)
                ->put(route('leads.update', $lead), [
                    'first_name' => 'Tal',
                    'last_name' => 'Barak',
                    'email' => 'tal@example.com',
                    'phone' => '0501231234',
                    'company' => 'Barak Co',
                    'job_title' => 'CEO',
                    'source' => 'website',
                    'status' => 'won',
                    'priority' => 'high',
                    'expected_value' => 9000,
                    'follow_up' => null,
                    'follow_up_time' => null,
                    'tags_text' => 'deal',
                    'street' => '1 Main St',
                    'city' => 'Tel Aviv',
                    'zip' => '61000',
                    'country' => 'Israel',
                    'notes' => 'Closed successfully',
                    'owner_id' => $owner->id,
                    'pipeline' => 'enterprise',
                    'stage' => 'won',
                    'visibility' => 'team',
                ])
                ->assertRedirect(route('admin.leads.index'));

            $lead->refresh();

            $this->assertSame('won', $lead->status);
            $this->assertNotNull($lead->closed_at);
            $this->assertSame('2026-04-18 13:15', $lead->closed_at?->format('Y-m-d H:i'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_view_analytics_dashboard_with_date_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'editor', 'name' => 'Dana Manager']);

        $openLead = Lead::create([
            'first_name' => 'Shir',
            'last_name' => 'Amit',
            'email' => 'shir@example.com',
            'owner_id' => $owner->id,
            'created_by' => $admin->id,
            'status' => 'proposal',
            'priority' => 'medium',
            'pipeline' => 'default',
            'stage' => 'negotiation',
            'visibility' => 'team',
        ]);

        DB::table('leads')->where('id', $openLead->id)->update([
            'created_at' => Carbon::parse('2026-04-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 09:00:00'),
        ]);

        $wonLead = Lead::create([
            'first_name' => 'Omer',
            'last_name' => 'Levi',
            'email' => 'omer@example.com',
            'owner_id' => $owner->id,
            'created_by' => $admin->id,
            'status' => 'won',
            'priority' => 'high',
            'pipeline' => 'enterprise',
            'stage' => 'won',
            'visibility' => 'team',
        ]);

        DB::table('leads')->where('id', $wonLead->id)->update([
            'created_at' => Carbon::parse('2026-04-11 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-15 10:00:00'),
            'closed_at' => Carbon::parse('2026-04-15 10:00:00'),
        ]);

        $outsideLead = Lead::create([
            'first_name' => 'Outside',
            'last_name' => 'Range',
            'email' => 'outside@example.com',
            'owner_id' => $owner->id,
            'created_by' => $admin->id,
            'status' => 'lost',
            'priority' => 'low',
            'pipeline' => 'default',
            'stage' => 'lead',
            'visibility' => 'team',
        ]);

        DB::table('leads')->where('id', $outsideLead->id)->update([
            'created_at' => Carbon::parse('2026-03-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-05 10:00:00'),
            'closed_at' => Carbon::parse('2026-03-05 10:00:00'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', [
                'from' => '2026-04-10',
                'to' => '2026-04-15',
            ]))
            ->assertOk()
            ->assertSee('אנליטיקה ניהולית')
            ->assertSee('Dana Manager')
            ->assertSee('data-created-leads="2"', false)
            ->assertSee('data-open-leads="1"', false)
            ->assertSee('data-won-leads="1"', false)
            ->assertSee('data-closed-leads="1"', false);
    }
}
