<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderDetail;

use Doctrine\DBAL\Connection;
use Planning\Order\Domain\OrderNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderDetailHandler
{
    public function __construct(private readonly Connection $connection) {}

    public function __invoke(GetOrderDetailQuery $query): OrderDetailDTO
    {
        $order = $this->connection->executeQuery(
            'SELECT id, name, client_name, planned_start_date, status
             FROM planning_orders WHERE id = :id',
            ['id' => $query->orderId],
        )->fetchAssociative();

        if (!$order) {
            throw new OrderNotFoundException($query->orderId);
        }

        $phaseRows = $this->connection->executeQuery(
            'SELECT id, name, required_role, required_skills, headcount, duration_days,
                    depends_on, start_date, end_date, assignments
             FROM planning_phases
             WHERE order_id = :orderId',
            ['orderId' => $query->orderId],
        )->fetchAllAssociative();

        $phases = array_map(
            fn(array $p) => new PhaseDetailDTO(
                id: $p['id'],
                name: $p['name'],
                requiredRole: $p['required_role'],
                requiredSkills: json_decode($p['required_skills'], true) ?? [],
                headcount: (int) $p['headcount'],
                durationDays: (int) $p['duration_days'],
                dependsOn: json_decode($p['depends_on'], true) ?? [],
                startDate: $p['start_date'],
                endDate: $p['end_date'],
                assignments: json_decode($p['assignments'], true) ?? [],
            ),
            $phaseRows,
        );

        return new OrderDetailDTO(
            id: $order['id'],
            name: $order['name'],
            clientName: $order['client_name'],
            plannedStartDate: $order['planned_start_date'],
            status: $order['status'],
            phases: $phases,
        );
    }
}
