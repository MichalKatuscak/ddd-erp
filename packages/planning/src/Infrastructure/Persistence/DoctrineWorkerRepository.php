<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\Worker;
use Planning\Worker\Domain\WorkerAllocation;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerNotFoundException;
use Planning\Worker\Domain\WorkerRepository;
use Planning\Worker\Domain\WorkerRole;

final class DoctrineWorkerRepository implements WorkerRepository
{
    public function __construct(private readonly Connection $connection) {}

    public function get(WorkerId $id): Worker
    {
        $row = $this->connection->executeQuery(
            'SELECT id, primary_role, skills FROM planning_workers WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();

        if (!$row) {
            throw new WorkerNotFoundException($id->value());
        }

        $allocationRows = $this->connection->executeQuery(
            'SELECT id, order_id, phase_id, allocation_percent, start_date, end_date
             FROM planning_worker_allocations WHERE worker_id = :id',
            ['id' => $id->value()],
        )->fetchAllAssociative();

        $allocations = array_map(
            fn(array $a) => new WorkerAllocation(
                id: $a['id'],
                orderId: OrderId::fromString($a['order_id']),
                phaseId: PhaseId::fromString($a['phase_id']),
                allocationPercent: (int) $a['allocation_percent'],
                startDate: new \DateTimeImmutable($a['start_date']),
                endDate: new \DateTimeImmutable($a['end_date']),
            ),
            $allocationRows,
        );

        return Worker::reconstruct(
            WorkerId::fromString($row['id']),
            WorkerRole::from($row['primary_role']),
            json_decode($row['skills'], true) ?? [],
            $allocations,
        );
    }

    public function save(Worker $worker): void
    {
        $this->connection->executeStatement(
            'INSERT INTO planning_workers (id, primary_role, skills)
             VALUES (:id, :primary_role, :skills)
             ON CONFLICT (id) DO UPDATE SET
                 primary_role = EXCLUDED.primary_role,
                 skills = EXCLUDED.skills',
            [
                'id'           => $worker->id()->value(),
                'primary_role' => $worker->primaryRole()->value,
                'skills'       => json_encode($worker->skills()),
            ],
        );

        $this->connection->executeStatement(
            'DELETE FROM planning_worker_allocations WHERE worker_id = :id',
            ['id' => $worker->id()->value()],
        );

        foreach ($worker->allocations() as $allocation) {
            $this->connection->executeStatement(
                'INSERT INTO planning_worker_allocations
                    (id, worker_id, order_id, phase_id, allocation_percent, start_date, end_date)
                 VALUES (:id, :worker_id, :order_id, :phase_id, :allocation_percent, :start_date, :end_date)',
                [
                    'id'                 => $allocation->id,
                    'worker_id'          => $worker->id()->value(),
                    'order_id'           => $allocation->orderId->value(),
                    'phase_id'           => $allocation->phaseId->value(),
                    'allocation_percent' => $allocation->allocationPercent,
                    'start_date'         => $allocation->startDate->format('Y-m-d'),
                    'end_date'           => $allocation->endDate->format('Y-m-d'),
                ],
            );
        }
    }
}
