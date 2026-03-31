<?php
declare(strict_types=1);

namespace Planning\Worker\Application\RegisterWorker;

final readonly class RegisterWorkerCommand
{
    /** @param string[] $skills */
    public function __construct(
        public string $workerId,
        public string $primaryRole,
        public array $skills,
    ) {}
}
