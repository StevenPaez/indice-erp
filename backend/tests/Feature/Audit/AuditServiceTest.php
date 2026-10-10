<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_allowed_event_with_actor_subject_and_metadata(): void
    {
        $actor = User::factory()->admin()->create();
        $subject = User::factory()->create();

        $auditLog = app(AuditService::class)->record(
            AuditEvent::UserRoleChanged,
            $actor,
            $subject,
            [
                'old_role' => UserRole::Viewer->value,
                'new_role' => UserRole::Operator->value,
            ],
            'request-123',
        );

        $this->assertSame(AuditEvent::UserRoleChanged, $auditLog->event);
        $this->assertTrue($auditLog->actor->is($actor));
        $this->assertTrue($auditLog->subject->is($subject));
        $this->assertSame('viewer', $auditLog->metadata['old_role']);
        $this->assertSame('operator', $auditLog->metadata['new_role']);
        $this->assertSame('request-123', $auditLog->request_id);
    }

    #[Test]
    public function it_records_a_system_event_without_an_actor(): void
    {
        $subject = User::factory()->admin()->create();

        $auditLog = app(AuditService::class)->record(
            AuditEvent::AdminBootstrapped,
            subject: $subject,
        );

        $this->assertNull($auditLog->actor_id);
        $this->assertNull($auditLog->metadata);
    }

    #[Test]
    public function it_rejects_metadata_outside_the_event_allowlist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AuditService::class)->record(
            AuditEvent::LoginFailed,
            metadata: ['password' => 'must-not-be-stored'],
        );
    }

    #[Test]
    public function audit_logs_cannot_be_updated(): void
    {
        $auditLog = app(AuditService::class)->record(AuditEvent::LoginSucceeded);

        $this->expectException(LogicException::class);

        $auditLog->event = AuditEvent::Logout;
        $auditLog->save();
    }

    #[Test]
    public function audit_logs_cannot_be_deleted_through_the_model(): void
    {
        $auditLog = app(AuditService::class)->record(AuditEvent::LoginSucceeded);

        $this->expectException(LogicException::class);

        $auditLog->delete();
    }

    #[Test]
    public function deleting_an_actor_preserves_the_audit_record(): void
    {
        $actor = User::factory()->create();
        $auditLog = app(AuditService::class)->record(AuditEvent::Logout, $actor);

        $actor->delete();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'actor_id' => null,
        ]);
        $this->assertSame(1, AuditLog::query()->count());
    }
}
