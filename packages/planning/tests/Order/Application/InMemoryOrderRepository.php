<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Application;

use Planning\Order\Domain\Order;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderNotFoundException;
use Planning\Order\Domain\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var Order[] */
    private array $orders = [];

    public function get(OrderId $id): Order
    {
        return $this->orders[$id->value()]
            ?? throw new OrderNotFoundException($id->value());
    }

    public function save(Order $order): void
    {
        $this->orders[$order->id()->value()] = $order;
    }
}
