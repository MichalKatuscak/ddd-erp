<?php
declare(strict_types=1);

namespace Planning\Worker\Application\GetWorkerList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkerListHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return WorkerListItemDTO[] */
    public function __invoke(GetWorkerListQuery $query): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d');

        $rows = $this->connection->executeQuery(
            "SELECT w.id, w.primary_role, w.skills,
                    u.first_name || ' ' || u.last_name AS name,
                    COALESCE(SUM(wa.allocation_percent), 0) AS current_allocation
             FROM planning_workers w
             JOIN identity_users u ON u.id = w.id
             LEFT JOIN planning_worker_allocations wa ON wa.worker_id = w.id
                 AND wa.start_date <= :now AND wa.end_date > :now
             GROUP BY w.id, w.primary_role, w.skills, u.first_name, u.last_name
             ORDER BY u.last_name ASC, u.first_name ASC
             LIMIT :limit OFFSET :offset",
            ['now' => $now, 'limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new WorkerListItemDTO(
                id: $row['id'],
                name: $row['name'],
                primaryRole: $row['primary_role'],
                skills: json_decode($row['skills'], true) ?? [],
                currentAllocationPercent: (int) $row['current_allocation'],
            ),
            $rows,
        );
    }
}
