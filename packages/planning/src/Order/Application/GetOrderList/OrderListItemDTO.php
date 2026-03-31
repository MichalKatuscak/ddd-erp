<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderList;

final readonly class OrderListItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $clientName,
        public string $plannedStartDate,
        public string $status,
        public int $phaseCount,
    ) {}
}
