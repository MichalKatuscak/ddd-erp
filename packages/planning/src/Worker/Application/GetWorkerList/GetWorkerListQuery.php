<?php
declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerList;

final readonly class GetWorkerListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
