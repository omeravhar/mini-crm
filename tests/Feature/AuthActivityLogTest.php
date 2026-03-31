<?php

namespace Tests\Feature;

use App\Models\AuthActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_and_logout_are_logged(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'email' => 'agent@example.com',
            'password' => 'secret123',
        ]);

        $this->withHeader('User-Agent', 'CRM Test Browser')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.10'])
            ->post(route('login.submit'), [
                'email' => 'agent@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $user->id,
            'event_type' => AuthActivityLog::LOGIN_SUCCESS,
            'status' => AuthActivityLog::STATUS_SUCCESS,
            'email' => 'agent@example.com',
            'ip_address' => '127.0.0.10',
        ]);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'CRM Test Browser')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.10'])
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $user->id,
            'event_type' => AuthActivityLog::LOGOUT,
            'status' => AuthActivityLog::STATUS_SUCCESS,
            'email' => 'agent@example.com',
        ]);
    }

    public function test_failed_logins_are_logged_with_correct_reason(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $this->from(route('login'))
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.20'])
            ->post(route('login.submit'), [
                'email' => 'owner@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => $user->id,
            'event_type' => AuthActivityLog::LOGIN_FAILED,
            'status' => AuthActivityLog::STATUS_FAILED,
            'email' => 'owner@example.com',
            'failure_reason' => 'invalid_password',
        ]);

        $this->from(route('login'))
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.21'])
            ->post(route('login.submit'), [
                'email' => 'missing@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => null,
            'event_type' => AuthActivityLog::LOGIN_FAILED,
            'status' => AuthActivityLog::STATUS_FAILED,
            'email' => 'missing@example.com',
            'failure_reason' => 'user_not_found',
        ]);
    }

    public function test_invalid_login_payload_is_logged_as_validation_error(): void
    {
        $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseHas('auth_activity_logs', [
            'user_id' => null,
            'event_type' => AuthActivityLog::LOGIN_FAILED,
            'status' => AuthActivityLog::STATUS_FAILED,
            'email' => 'not-an-email',
            'failure_reason' => 'validation_error',
        ]);
    }

    public function test_admin_can_view_auth_activity_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'editor',
            'name' => 'Dana Manager',
            'email' => 'dana@example.com',
        ]);

        AuthActivityLog::create([
            'user_id' => $user->id,
            'event_type' => AuthActivityLog::LOGIN_SUCCESS,
            'status' => AuthActivityLog::STATUS_SUCCESS,
            'email' => $user->email,
            'ip_address' => '192.168.1.20',
            'user_agent' => 'Chrome',
            'occurred_at' => now()->subHour(),
        ]);

        AuthActivityLog::create([
            'user_id' => $user->id,
            'event_type' => AuthActivityLog::LOGIN_FAILED,
            'status' => AuthActivityLog::STATUS_FAILED,
            'email' => $user->email,
            'failure_reason' => 'invalid_password',
            'ip_address' => '192.168.1.21',
            'user_agent' => 'Chrome',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.auth-activity.index'))
            ->assertOk()
            ->assertSee('לוגי התחברות')
            ->assertSee('Dana Manager')
            ->assertSee('dana@example.com')
            ->assertSee('סיסמה שגויה');
    }
}
