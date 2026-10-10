<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function administrator_can_filter_paginated_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $auditService = app(AuditService::class);

        $this->travelTo('2026-01-10 10:00:00');
        $auditService->record(
            AuditEvent::UserRoleChanged,
            $admin,
            $target,
            ['old_role' => 'viewer', 'new_role' => 'operator'],
            'request-filter-1',
        );
        $this->travelTo('2026-01-11 10:00:00');
        $auditService->record(
            AuditEvent::UserUpdated,
            $admin,
            $target,
            ['fields' => ['name']],
            'request-filter-2',
        );
        $this->travelBack();

        $this->actingAs($admin, 'web')
            ->getJson(sprintf(
                '/api/audit-logs?event=%s&actor_id=%d&request_id=request-filter-1&date_from=2026-01-10&date_to=2026-01-10&per_page=5',
                AuditEvent::UserRoleChanged->value,
                $admin->id,
            ))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', AuditEvent::UserRoleChanged->value)
            ->assertJsonPath('data.0.actor.email', $admin->email)
            ->assertJsonPath('data.0.subject.type', 'User')
            ->assertJsonPath('data.0.request_id', 'request-filter-1')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.actor.password');
    }

    #[Test]
    public function non_administrator_cannot_view_audit_logs_and_the_denial_is_correlated(): void
    {
        $operator = User::factory()->operator()->create();

        $response = $this->actingAs($operator, 'web')->getJson('/api/audit-logs');

        $response->assertForbidden();
        $requestId = $response->headers->get('X-Request-ID');
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $operator->id,
            'event' => AuditEvent::AuthorizationDenied->value,
            'request_id' => $requestId,
        ]);
    }

    #[Test]
    public function request_id_is_generated_by_the_server_and_attached_to_audit_events(): void
    {
        $user = User::factory()->create([
            'email' => 'correlation@example.com',
            'password' => 'correlation-password',
        ]);

        $response = $this
            ->withHeaders([
                'Origin' => 'http://localhost:5173',
                'X-Request-ID' => "untrusted\nvalue",
            ])
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'correlation-password',
            ]);

        $response->assertOk();
        $requestId = $response->headers->get('X-Request-ID');
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertNotSame("untrusted\nvalue", $requestId);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => AuditEvent::LoginSucceeded->value,
            'request_id' => $requestId,
        ]);
    }

    #[Test]
    public function unsafe_or_inconsistent_filters_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'web')
            ->getJson('/api/audit-logs?request_id=unsafe%0Avalue')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('request_id');

        $this->getJson('/api/audit-logs?date_from=2026-02-02&date_to=2026-02-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    }

    #[Test]
    public function audit_results_are_paginated_without_duplicate_metadata(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (range(1, 6) as $index) {
            app(AuditService::class)->record(
                AuditEvent::UserUpdated,
                $admin,
                $admin,
                ['fields' => ['name']],
                "request-page-{$index}",
            );
        }

        $this->actingAs($admin, 'web')
            ->getJson('/api/audit-logs?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('meta.per_page', 5);
    }
}
