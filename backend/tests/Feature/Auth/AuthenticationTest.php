<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_registration_is_not_available(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Untrusted User',
            'email' => 'untrusted@example.com',
            'password' => 'a-long-untrusted-password',
            'password_confirmation' => 'a-long-untrusted-password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'untrusted@example.com']);
    }

    #[Test]
    public function active_user_can_login_with_a_normalized_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'a-valid-test-password',
        ]);
        $previousSessionId = session()->getId();

        $response = $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', [
                'email' => ' Test@Example.com ',
                'password' => 'a-valid-test-password',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['email' => 'test@example.com']);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => AuditEvent::LoginSucceeded->value,
            'subject_id' => $user->id,
        ]);
    }

    #[Test]
    public function invalid_credentials_and_inactive_accounts_have_the_same_response(): void
    {
        User::factory()->create([
            'email' => 'active@example.com',
            'password' => 'a-valid-test-password',
        ]);
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'a-valid-test-password',
        ]);

        $invalid = $this->postJson('/api/login', [
            'email' => 'active@example.com',
            'password' => 'incorrect-password',
        ]);
        $inactive = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'a-valid-test-password',
        ]);
        $unknown = $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'a-valid-test-password',
        ]);

        $invalid->assertUnprocessable();
        $inactive->assertUnprocessable();
        $unknown->assertUnprocessable();
        $this->assertSame($invalid->json('message'), $inactive->json('message'));
        $this->assertSame($invalid->json('message'), $unknown->json('message'));
        $this->assertDatabaseCount('audit_logs', 3);
    }

    #[Test]
    public function login_is_rate_limited_by_normalized_identity_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => ' RateLimited@Example.com ',
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login', [
            'email' => 'ratelimited@example.com',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    #[Test]
    public function authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_profile(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    #[Test]
    public function logout_invalidates_the_session_and_is_audited(): void
    {
        $user = User::factory()->create([
            'password' => 'a-valid-test-password',
        ]);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'a-valid-test-password',
            ])
            ->assertOk();

        $this->postJson('/api/logout')->assertNoContent();

        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => AuditEvent::Logout->value,
        ]);
    }
}
