<?php
declare(strict_types=1);

namespace Planning\Worker\Domain;

interface WorkerRepository
{
    /** @throws WorkerNotFoundException */
    public function get(WorkerId $id): Worker;

    public function save(Worker $worker): void;
}
