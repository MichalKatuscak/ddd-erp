<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Domain;

use Identity\Role\Domain\RoleName;
use PHPUnit\Framework\TestCase;

final class RoleNameTest extends TestCase
{
    public function test_creates_from_valid_slug(): void
    {
        $name = RoleName::fromString('crm-manager');
        $this->assertSame('crm-manager', $name->value());
    }

    public function test_throws_on_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('');
    }

    public function test_throws_on_whitespace_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('   ');
    }

    public function test_throws_on_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('CRM Manager!');
    }

    public function test_equality(): void
    {
        $name1 = RoleName::fromString('crm-manager');
        $name2 = RoleName::fromString('crm-manager');
        $this->assertTrue($name1->equals($name2));
    }

    public function test_to_string(): void
    {
        $name = RoleName::fromString('super-admin');
        $this->assertSame('super-admin', (string) $name);
    }
}
