<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Domain;

use Planning\Order\Domain\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    public function test_generates_unique_ids(): void
    {
        $a = OrderId::generate();
        $b = OrderId::generate();
        $this->assertNotSame($a->value(), $b->value());
    }

    public function test_from_string_round_trips(): void
    {
        $id = OrderId::generate();
        $restored = OrderId::fromString($id->value());
        $this->assertTrue($id->equals($restored));
    }

    public function test_rejects_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OrderId::fromString('not-a-uuid');
    }

    public function test_to_string(): void
    {
        $id = OrderId::generate();
        $this->assertSame($id->value(), (string) $id);
    }
}
