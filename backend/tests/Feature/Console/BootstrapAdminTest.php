<?php

namespace Tests\Feature\Console;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BootstrapAdminTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_promotes_and_activates_an_existing_user_explicitly(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'existing@example.com',
        ]);

        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Administrator email', ' Existing@Example.com ')
            ->expectsOutputToContain('Existing user:')
            ->expectsConfirmation('Promote this user to administrator?', 'yes')
            ->expectsOutputToContain('promoted successfully')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => null,
            'event' => AuditEvent::AdminBootstrapped->value,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_creates_the_first_administrator_without_exposing_a_known_credential(): void
    {
        $password = 'correct horse battery staple';

        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Administrator name', 'Initial Admin')
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutputToContain('created successfully')
            ->assertSuccessful();

        $user = User::query()->sole();

        $this->assertSame('Initial Admin', $user->name);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame(1, AuditLog::query()->count());
    }

    #[Test]
    public function it_is_idempotent_when_an_active_administrator_exists(): void
    {
        User::factory()->admin()->create();

        $this->artisan('app:bootstrap-admin')
            ->expectsOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function it_leaves_an_existing_user_unchanged_when_promotion_is_cancelled(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Administrator email', 'existing@example.com')
            ->expectsConfirmation('Promote this user to administrator?', 'no')
            ->expectsOutputToContain('cancelled')
            ->assertSuccessful();

        $this->assertSame(UserRole::Viewer, $user->refresh()->role);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function it_rejects_a_short_password_without_creating_a_user(): void
    {
        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Administrator name', 'Initial Admin')
            ->expectsQuestion('Password', 'too-short')
            ->expectsQuestion('Confirm password', 'too-short')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }
}
