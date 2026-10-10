<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    #[Test]
    public function it_defines_the_supported_roles(): void
    {
        $this->assertSame(
            ['admin', 'operator', 'viewer'],
            array_column(UserRole::cases(), 'value'),
        );
    }

    #[Test]
    public function it_rejects_an_unknown_role(): void
    {
        $this->assertNull(UserRole::tryFrom('owner'));
    }
}
