<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderDetail;

final readonly class OrderDetailDTO
{
    /** @param PhaseDetailDTO[] $phases */
    public function __construct(
        public string $id,
        public string $name,
        public string $clientName,
        public string $plannedStartDate,
        public string $status,
        public array $phases,
    ) {}
}
