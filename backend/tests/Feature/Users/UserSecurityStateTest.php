<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSecurityStateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function migration_preserves_existing_users_with_least_privilege_defaults(): void
    {
        $migration = require database_path(
            'migrations/2026_10_09_000001_add_security_fields_to_users_table.php',
        );

        $migration->down();

        DB::table('users')->insert([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('existing-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $user = User::query()->where('email', 'existing@example.com')->firstOrFail();

        $this->assertSame(UserRole::Viewer, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->created_by);
        $this->assertNull($user->updated_by);
    }

    #[Test]
    public function security_attributes_are_cast_and_linked_to_their_actors(): void
    {
        $actor = User::factory()->admin()->create();
        $user = User::factory()->operator()->inactive()->mustChangePassword()->create([
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->assertSame(UserRole::Operator, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->createdBy->is($actor));
        $this->assertTrue($user->updatedBy->is($actor));
    }

    #[Test]
    public function security_attributes_cannot_be_mass_assigned_through_the_user_model(): void
    {
        $user = User::query()->create([
            'name' => 'Attempted Admin',
            'email' => 'attempted-admin@example.com',
            'password' => 'a-secure-test-password',
            'role' => UserRole::Admin,
            'is_active' => false,
            'must_change_password' => true,
        ]);
        $user->refresh();

        $this->assertSame(UserRole::Viewer, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
    }
}
