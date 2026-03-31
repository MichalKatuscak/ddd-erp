<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderListHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return OrderListItemDTO[] */
    public function __invoke(GetOrderListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT o.id, o.name, o.client_name, o.planned_start_date, o.status,
                    COUNT(p.id) AS phase_count
             FROM planning_orders o
             LEFT JOIN planning_phases p ON p.order_id = o.id
             GROUP BY o.id, o.name, o.client_name, o.planned_start_date, o.status
             ORDER BY o.planned_start_date DESC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new OrderListItemDTO(
                id: $row['id'],
                name: $row['name'],
                clientName: $row['client_name'],
                plannedStartDate: $row['planned_start_date'],
                status: $row['status'],
                phaseCount: (int) $row['phase_count'],
            ),
            $rows,
        );
    }
}
