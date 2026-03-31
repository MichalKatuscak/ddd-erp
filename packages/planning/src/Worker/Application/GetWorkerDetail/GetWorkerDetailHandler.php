<?php

declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerDetail;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkerDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetWorkerDetailQuery $query): ?WorkerDetailDTO
    {
        $row = $this->connection->fetchAssociative(
            'SELECT w.id, w.primary_role, w.skills,
                    u.first_name, u.last_name
             FROM planning_workers w
             JOIN identity_users u ON u.id = w.id
             WHERE w.id = :id',
            ['id' => $query->workerId],
        );

        if ($row === false) {
            return null;
        }

        $allocations = $this->connection->fetchAllAssociative(
            'SELECT wa.order_id, wa.phase_id, wa.allocation_percent,
                    wa.start_date, wa.end_date,
                    o.name AS order_name,
                    p.name AS phase_name
             FROM planning_worker_allocations wa
             JOIN planning_orders o ON o.id = wa.order_id
             JOIN planning_phases p ON p.id = wa.phase_id
             WHERE wa.worker_id = :id
             ORDER BY wa.start_date',
            ['id' => $query->workerId],
        );

        $dto = new WorkerDetailDTO();
        $dto->id = $row['id'];
        $dto->name = $row['first_name'] . ' ' . $row['last_name'];
        $dto->primaryRole = $row['primary_role'];
        $dto->skills = json_decode($row['skills'], true);
        $dto->allocations = array_map(function (array $a): WorkerAllocationDTO {
            $alloc = new WorkerAllocationDTO();
            $alloc->orderId = $a['order_id'];
            $alloc->orderName = $a['order_name'];
            $alloc->phaseId = $a['phase_id'];
            $alloc->phaseName = $a['phase_name'];
            $alloc->allocationPercent = (int) $a['allocation_percent'];
            $alloc->startDate = $a['start_date'];
            $alloc->endDate = $a['end_date'];
            return $alloc;
        }, $allocations);

        return $dto;
    }
}
