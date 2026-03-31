<?php

declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerDetail;

final class WorkerAllocationDTO
{
    public string $orderId;
    public string $orderName;
    public string $phaseId;
    public string $phaseName;
    public int $allocationPercent;
    public string $startDate;
    public string $endDate;
}
