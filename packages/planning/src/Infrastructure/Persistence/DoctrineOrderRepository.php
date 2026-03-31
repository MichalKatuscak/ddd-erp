<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Planning\Order\Domain\Assignment;
use Planning\Order\Domain\Order;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderNotFoundException;
use Planning\Order\Domain\OrderRepository;
use Planning\Order\Domain\OrderStatus;
use Planning\Order\Domain\Phase;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\WorkerRole;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(private readonly Connection $connection) {}

    public function get(OrderId $id): Order
    {
        $row = $this->connection->executeQuery(
            'SELECT id, name, client_name, planned_start_date, status FROM planning_orders WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();

        if (!$row) {
            throw new OrderNotFoundException($id->value());
        }

        $phaseRows = $this->connection->executeQuery(
            'SELECT id, name, required_role, required_skills, headcount, duration_days,
                    depends_on, start_date, end_date, assignments
             FROM planning_phases WHERE order_id = :orderId',
            ['orderId' => $id->value()],
        )->fetchAllAssociative();

        $phases = array_map(fn(array $p) => $this->hydratePhase($p), $phaseRows);

        return Order::reconstruct(
            OrderId::fromString($row['id']),
            $row['name'],
            $row['client_name'],
            new \DateTimeImmutable($row['planned_start_date']),
            OrderStatus::from($row['status']),
            $phases,
        );
    }

    public function save(Order $order): void
    {
        $this->connection->executeStatement(
            'INSERT INTO planning_orders (id, name, client_name, planned_start_date, status)
             VALUES (:id, :name, :client_name, :planned_start_date, :status)
             ON CONFLICT (id) DO UPDATE SET
                 name = EXCLUDED.name,
                 client_name = EXCLUDED.client_name,
                 planned_start_date = EXCLUDED.planned_start_date,
                 status = EXCLUDED.status',
            [
                'id'                 => $order->id()->value(),
                'name'               => $order->name(),
                'client_name'        => $order->clientName(),
                'planned_start_date' => $order->plannedStartDate()->format('Y-m-d'),
                'status'             => $order->status()->value,
            ],
        );

        $this->connection->executeStatement(
            'DELETE FROM planning_phases WHERE order_id = :orderId',
            ['orderId' => $order->id()->value()],
        );

        foreach ($order->phases() as $phase) {
            $this->connection->executeStatement(
                'INSERT INTO planning_phases
                    (id, order_id, name, required_role, required_skills, headcount,
                     duration_days, depends_on, start_date, end_date, assignments)
                 VALUES
                    (:id, :order_id, :name, :required_role, :required_skills, :headcount,
                     :duration_days, :depends_on, :start_date, :end_date, :assignments)',
                [
                    'id'              => $phase->id()->value(),
                    'order_id'        => $order->id()->value(),
                    'name'            => $phase->name(),
                    'required_role'   => $phase->requiredRole()->value,
                    'required_skills' => json_encode($phase->requiredSkills()),
                    'headcount'       => $phase->headcount(),
                    'duration_days'   => $phase->durationDays(),
                    'depends_on'      => json_encode($phase->dependsOn()),
                    'start_date'      => $phase->startDate()?->format('Y-m-d'),
                    'end_date'        => $phase->endDate()?->format('Y-m-d'),
                    'assignments'     => json_encode(array_map(
                        fn(Assignment $a) => ['user_id' => $a->userId, 'allocation_percent' => $a->allocationPercent],
                        $phase->assignments(),
                    )),
                ],
            );
        }
    }

    private function hydratePhase(array $row): Phase
    {
        $assignments = array_map(
            fn(array $a) => new Assignment($a['user_id'], $a['allocation_percent']),
            json_decode($row['assignments'], true) ?? [],
        );

        return Phase::reconstruct(
            PhaseId::fromString($row['id']),
            $row['name'],
            WorkerRole::from($row['required_role']),
            json_decode($row['required_skills'], true) ?? [],
            (int) $row['headcount'],
            (int) $row['duration_days'],
            json_decode($row['depends_on'], true) ?? [],
            $row['start_date'] ? new \DateTimeImmutable($row['start_date']) : null,
            $row['end_date']   ? new \DateTimeImmutable($row['end_date'])   : null,
            $assignments,
        );
    }
}
