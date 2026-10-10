<?php

namespace Tests\Feature\Users;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrator_can_create_a_user_with_a_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->postJson('/api/users', [
            'name' => '  New Operator  ',
            'email' => '  OPERATOR@EXAMPLE.COM ',
            'role' => 'operator',
            'is_active' => true,
            'password' => 'temporary-password',
            'password_confirmation' => 'temporary-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Operator')
            ->assertJsonPath('data.email', 'operator@example.com')
            ->assertJsonPath('data.role', 'operator')
            ->assertJsonPath('data.must_change_password', true);

        $user = User::query()->where('email', 'operator@example.com')->firstOrFail();
        $this->assertSame($admin->id, $user->created_by);
        $this->assertSame($admin->id, $user->updated_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'event' => AuditEvent::UserCreated->value,
            'subject_id' => $user->id,
        ]);
    }

    #[Test]
    public function administrator_can_filter_and_page_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->operator()->create(['name' => 'Matching Operator']);
        User::factory()->viewer()->create(['name' => 'Matching Viewer']);
        User::factory()->operator()->inactive()->create(['name' => 'Inactive Operator']);

        $this->actingAs($admin, 'web')
            ->getJson('/api/users?search=matching&role=operator&is_active=1&per_page=5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Matching Operator')
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function administrator_can_update_identity_role_and_status_with_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->viewer()->create();

        $this->actingAs($admin, 'web')
            ->putJson("/api/users/{$target->id}", [
                'name' => 'Updated User',
                'email' => 'UPDATED@EXAMPLE.COM',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->patchJson("/api/users/{$target->id}/role", ['role' => 'operator'])
            ->assertOk()
            ->assertJsonPath('data.role', 'operator');

        $this->patchJson("/api/users/{$target->id}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $target->refresh();
        $this->assertSame(UserRole::Operator, $target->role);
        $this->assertFalse($target->is_active);
        $this->assertSame($admin->id, $target->updated_by);
        $this->assertEqualsCanonicalizing(
            [
                AuditEvent::UserUpdated->value,
                AuditEvent::UserRoleChanged->value,
                AuditEvent::UserDeactivated->value,
            ],
            AuditLog::query()
                ->where('actor_id', $admin->id)
                ->where('subject_id', $target->id)
                ->pluck('event')
                ->map(fn (AuditEvent $event): string => $event->value)
                ->all(),
        );
    }

    #[Test]
    public function non_administrators_cannot_discover_or_modify_users(): void
    {
        $target = User::factory()->create();

        foreach ([UserRole::Operator, UserRole::Viewer] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor, 'web')
                ->getJson('/api/users')
                ->assertForbidden();
            $this->getJson("/api/users/{$target->id}")->assertForbidden();
            $this->patchJson("/api/users/{$target->id}/role", ['role' => 'admin'])
                ->assertForbidden();
        }
    }

    #[Test]
    public function administrator_cannot_change_their_own_role_or_deactivate_the_last_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'web')
            ->patchJson("/api/users/{$admin->id}/role", ['role' => 'viewer'])
            ->assertForbidden();

        $this->patchJson("/api/users/{$admin->id}/status", ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Debe permanecer al menos un administrador activo.');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
        $this->assertTrue($admin->is_active);
    }

    #[Test]
    public function missing_user_remains_not_found_instead_of_becoming_an_authorization_bypass(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'web')
            ->getJson('/api/users/999999')
            ->assertNotFound();
    }
}
