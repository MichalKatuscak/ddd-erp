<?php
declare(strict_types=1);

namespace Planning\Order\Application\CreateOrder;

final readonly class CreateOrderCommand
{
    public function __construct(
        public string $orderId,
        public string $name,
        public string $clientName,
        public string $plannedStartDate,
    ) {}
}
