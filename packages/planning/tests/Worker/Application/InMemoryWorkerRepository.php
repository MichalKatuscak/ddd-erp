<?php
declare(strict_types=1);

namespace Planning\Tests\Worker\Application;

use Planning\Worker\Domain\Worker;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerNotFoundException;
use Planning\Worker\Domain\WorkerRepository;

final class InMemoryWorkerRepository implements WorkerRepository
{
    /** @var Worker[] */
    private array $workers = [];

    public function get(WorkerId $id): Worker
    {
        return $this->workers[$id->value()]
            ?? throw new WorkerNotFoundException($id->value());
    }

    public function save(Worker $worker): void
    {
        $this->workers[$worker->id()->value()] = $worker;
    }
}
