<?php
declare(strict_types=1);

namespace Planning\Worker\Domain;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;

final readonly class WorkerAllocation
{
    public function __construct(
        public string $id,
        public OrderId $orderId,
        public PhaseId $phaseId,
        public int $allocationPercent,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {}
}
