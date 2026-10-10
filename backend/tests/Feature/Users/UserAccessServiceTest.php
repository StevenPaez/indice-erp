<?php

namespace Tests\Feature\Users;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Exceptions\LastActiveAdministrator;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class UserAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_rejects_demoting_the_last_active_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        try {
            app(UserAccessService::class)->changeRole($admin, UserRole::Viewer, $admin);
            $this->fail('The last active administrator was demoted.');
        } catch (LastActiveAdministrator) {
            $admin->refresh();

            $this->assertSame(UserRole::Admin, $admin->role);
            $this->assertNull($admin->updated_by);
            $this->assertSame(0, AuditLog::query()->count());
        }
    }

    #[Test]
    public function it_rejects_deactivating_the_last_active_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        try {
            app(UserAccessService::class)->changeActiveStatus($admin, false, $admin);
            $this->fail('The last active administrator was deactivated.');
        } catch (LastActiveAdministrator) {
            $admin->refresh();

            $this->assertTrue($admin->is_active);
            $this->assertNull($admin->updated_by);
            $this->assertSame(0, AuditLog::query()->count());
        }
    }

    #[Test]
    public function it_demotes_an_administrator_when_another_active_admin_remains(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $result = app(UserAccessService::class)->changeRole(
            $target,
            UserRole::Operator,
            $actor,
        );

        $this->assertSame(UserRole::Operator, $result->role);
        $this->assertSame($actor->id, $result->updated_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'event' => AuditEvent::UserRoleChanged->value,
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function it_deactivates_an_administrator_when_another_active_admin_remains(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $result = app(UserAccessService::class)->changeActiveStatus($target, false, $actor);

        $this->assertFalse($result->is_active);
        $this->assertSame($actor->id, $result->updated_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'event' => AuditEvent::UserDeactivated->value,
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function it_activates_a_user_and_records_the_actor(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->inactive()->create();

        $result = app(UserAccessService::class)->changeActiveStatus($target, true, $actor);

        $this->assertTrue($result->is_active);
        $this->assertSame($actor->id, $result->updated_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'event' => AuditEvent::UserActivated->value,
            'subject_id' => $target->id,
        ]);
    }

    #[Test]
    public function unchanged_access_state_does_not_create_audit_noise(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->operator()->create();

        app(UserAccessService::class)->changeRole($target, UserRole::Operator, $actor);
        app(UserAccessService::class)->changeActiveStatus($target, true, $actor);

        $this->assertSame(0, AuditLog::query()->count());
        $this->assertNull($target->refresh()->updated_by);
    }

    #[Test]
    public function an_audit_failure_rolls_back_the_access_change(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->mock(AuditService::class)
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        try {
            app(UserAccessService::class)->changeRole($target, UserRole::Viewer, $actor);
            $this->fail('The operation should fail when its audit record cannot be stored.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit storage unavailable.', $exception->getMessage());
            $this->assertSame(UserRole::Admin, $target->refresh()->role);
            $this->assertNull($target->updated_by);
        }
    }
}
