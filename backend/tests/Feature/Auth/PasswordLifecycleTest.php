<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_change_their_password_after_confirming_the_current_one(): void
    {
        $user = User::factory()->mustChangePassword()->create([
            'password' => 'current-secure-password',
        ]);
        $this->login($user, 'current-secure-password');

        $this->putJson('/api/user/password', [
            'current_password' => 'current-secure-password',
            'password' => 'replacement secure password',
            'password_confirmation' => 'replacement secure password',
        ])->assertOk()
            ->assertJsonPath('must_change_password', false);

        $user->refresh();

        $this->assertTrue(Hash::check('replacement secure password', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertSame($user->id, $user->updated_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => AuditEvent::UserPasswordChanged->value,
            'subject_id' => $user->id,
        ]);
    }

    #[Test]
    public function current_password_must_be_correct_before_changing_it(): void
    {
        $user = User::factory()->create([
            'password' => 'current-secure-password',
        ]);
        $this->login($user, 'current-secure-password');

        $this->putJson('/api/user/password', [
            'current_password' => 'incorrect-password',
            'password' => 'replacement secure password',
            'password_confirmation' => 'replacement secure password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('current-secure-password', $user->refresh()->password));
    }

    #[Test]
    public function temporary_password_session_is_restricted_until_password_changes(): void
    {
        $user = User::factory()->mustChangePassword()->create([
            'password' => 'temporary secure password',
        ]);
        $this->login($user, 'temporary secure password');

        $this->getJson('/api/user')->assertOk();
        $this->getJson('/api/books')
            ->assertForbidden()
            ->assertJsonPath('code', 'password_change_required');

        $this->putJson('/api/user/password', [
            'current_password' => 'temporary secure password',
            'password' => 'permanent secure password',
            'password_confirmation' => 'permanent secure password',
        ])->assertOk();

        $this->getJson('/api/books')->assertOk();
    }

    #[Test]
    public function recovery_request_has_the_same_response_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/forgot-password', [
            'email' => ' Known@Example.com ',
        ]);
        $unknown = $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function valid_single_use_token_resets_the_password_and_is_audited(): void
    {
        Notification::fake();
        $user = User::factory()->mustChangePassword()->create([
            'email' => 'user@example.com',
            'password' => 'previous secure password',
        ]);
        $token = null;

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'restored secure password',
            'password_confirmation' => 'restored secure password',
        ];

        $this->postJson('/api/reset-password', $payload)->assertOk();
        $this->postJson('/api/reset-password', $payload)->assertUnprocessable();

        $user->refresh();

        $this->assertTrue(Hash::check('restored secure password', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => AuditEvent::PasswordResetCompleted->value,
            'subject_id' => $user->id,
        ]);
    }

    #[Test]
    public function inactive_user_can_reset_password_but_still_cannot_login(): void
    {
        Notification::fake();
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);
        $token = null;

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'restored secure password',
            'password_confirmation' => 'restored secure password',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'restored secure password',
        ])->assertUnprocessable();
    }

    private function login(User $user, string $password): void
    {
        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => $password,
            ])
            ->assertOk();
    }
}
