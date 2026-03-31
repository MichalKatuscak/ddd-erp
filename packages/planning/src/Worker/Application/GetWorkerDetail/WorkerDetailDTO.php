<?php

declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerDetail;

final class WorkerDetailDTO
{
    public string $id;
    public string $name;
    public string $primaryRole;
    /** @var string[] */
    public array $skills;
    /** @var WorkerAllocationDTO[] */
    public array $allocations;
}
