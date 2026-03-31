<?php
declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerList;

final readonly class WorkerListItemDTO
{
    /**
     * @param string[] $skills
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $primaryRole,
        public array $skills,
        public int $currentAllocationPercent,
    ) {}
}
