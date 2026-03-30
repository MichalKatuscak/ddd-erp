<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerName;
use PHPUnit\Framework\TestCase;

final class CustomerNameTest extends TestCase
{
    public function test_creates_from_parts(): void
    {
        $name = CustomerName::fromParts('Jan', 'Novák');
        $this->assertSame('Jan', $name->firstName());
        $this->assertSame('Novák', $name->lastName());
        $this->assertSame('Jan Novák', $name->fullName());
    }

    public function test_throws_on_empty_first_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerName::fromParts('', 'Novák');
    }

    public function test_throws_on_empty_last_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerName::fromParts('Jan', '  ');
    }

    public function test_equality(): void
    {
        $name1 = CustomerName::fromParts('Jan', 'Novák');
        $name2 = CustomerName::fromParts('Jan', 'Novák');
        $this->assertTrue($name1->equals($name2));
    }
}
