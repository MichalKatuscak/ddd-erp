<?php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserName;
use PHPUnit\Framework\TestCase;

final class UserNameTest extends TestCase
{
    public function test_creates_from_parts(): void
    {
        $name = UserName::fromParts('Jan', 'Novák');
        $this->assertSame('Jan', $name->firstName());
        $this->assertSame('Novák', $name->lastName());
        $this->assertSame('Jan Novák', $name->fullName());
    }

    public function test_throws_on_empty_first_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserName::fromParts('', 'Novák');
    }

    public function test_throws_on_empty_last_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserName::fromParts('Jan', '  ');
    }

    public function test_equality(): void
    {
        $name1 = UserName::fromParts('Jan', 'Novák');
        $name2 = UserName::fromParts('Jan', 'Novák');
        $this->assertTrue($name1->equals($name2));
    }
}
