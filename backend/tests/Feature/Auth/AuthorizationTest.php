<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditEvent;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function roleCapabilities(): array
    {
        return [
            'admin' => [UserRole::Admin, array_column(Capability::cases(), 'value')],
            'operator' => [UserRole::Operator, [
                'catalog.view',
                'catalog.manage',
                'inventory.view',
                'inventory.manage',
            ]],
            'viewer' => [UserRole::Viewer, ['catalog.view', 'inventory.view']],
        ];
    }

    #[Test]
    #[DataProvider('roleCapabilities')]
    public function session_exposes_effective_capabilities(UserRole $role, array $capabilities): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user, 'web')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('role', $role->value)
            ->assertJsonPath('capabilities', $capabilities);
    }

    #[Test]
    public function every_active_role_can_view_the_catalog(): void
    {
        $book = Book::factory()->create();

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user, 'web')
                ->getJson("/api/books/{$book->id}")
                ->assertOk();
        }
    }

    #[Test]
    public function admin_and_operator_can_manage_the_catalog(): void
    {
        foreach ([UserRole::Admin, UserRole::Operator] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user, 'web')
                ->postJson('/api/books', $this->bookPayload($role->value))
                ->assertCreated();
        }
    }

    #[Test]
    public function viewer_cannot_manage_the_catalog_and_the_denial_is_audited(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer, 'web')
            ->postJson('/api/books', $this->bookPayload('viewer'))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $viewer->id,
            'event' => AuditEvent::AuthorizationDenied->value,
        ]);
        $this->assertSame(
            ['action' => 'books.store'],
            AuditLog::query()->where('actor_id', $viewer->id)->firstOrFail()->metadata,
        );
    }

    private function bookPayload(string $suffix): array
    {
        return [
            'title' => "Book {$suffix}",
            'isbn' => "isbn-{$suffix}",
            'author' => 'Author',
            'purchase_price' => 10,
            'sale_price' => 20,
            'stock' => 5,
        ];
    }
}
