<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

interface OrderRepository
{
    /** @throws OrderNotFoundException */
    public function get(OrderId $id): Order;

    public function save(Order $order): void;
}
