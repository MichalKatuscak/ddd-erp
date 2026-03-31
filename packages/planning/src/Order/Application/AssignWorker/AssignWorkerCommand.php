<?php
declare(strict_types=1);

namespace Planning\Order\Application\AssignWorker;

final readonly class AssignWorkerCommand
{
    public function __construct(
        public string $orderId,
        public string $phaseId,
        public string $workerId,
        public int $allocationPercent,
    ) {}
}
