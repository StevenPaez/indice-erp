<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActiveUserSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deactivation_revokes_an_existing_session_and_reactivation_does_not_restore_it(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'a-valid-test-password',
        ]);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'a-valid-test-password',
            ])
            ->assertOk();

        $user->forceFill(['is_active' => false])->save();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/user')->assertUnauthorized();

        $user->forceFill(['is_active' => true])->save();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/user')->assertUnauthorized();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'a-valid-test-password',
        ])->assertOk();
    }
}
