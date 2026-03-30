<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerId;
use PHPUnit\Framework\TestCase;

final class CustomerIdTest extends TestCase
{
    public function test_generates_unique_ids(): void
    {
        $id1 = CustomerId::generate();
        $id2 = CustomerId::generate();
        $this->assertNotEquals($id1->value(), $id2->value());
    }

    public function test_creates_from_valid_string(): void
    {
        $id = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', $id->value());
    }

    public function test_throws_on_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerId::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $id1 = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $id2 = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertTrue($id1->equals($id2));
    }

    public function test_to_string(): void
    {
        $id = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', (string) $id);
    }
}
