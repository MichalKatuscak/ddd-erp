<?php

declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerDetail;

final readonly class GetWorkerDetailQuery
{
    public function __construct(
        public string $workerId,
    ) {}
}
