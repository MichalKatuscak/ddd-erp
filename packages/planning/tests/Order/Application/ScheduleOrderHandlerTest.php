<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Application;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Application\AddPhase\AddPhaseHandler;
use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Application\CreateOrder\CreateOrderHandler;
use Planning\Order\Application\ScheduleOrder\ScheduleOrderCommand;
use Planning\Order\Application\ScheduleOrder\ScheduleOrderHandler;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;
use PHPUnit\Framework\TestCase;

final class ScheduleOrderHandlerTest extends TestCase
{
    private InMemoryOrderRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryOrderRepository();
    }

    public function test_schedules_phases_sets_dates(): void
    {
        $orderId = OrderId::generate()->value();
        (new CreateOrderHandler($this->repo))(
            new CreateOrderCommand($orderId, 'Project', 'Client', '2026-04-01')
        );
        $phaseId = PhaseId::generate()->value();
        (new AddPhaseHandler($this->repo))(
            new AddPhaseCommand($orderId, $phaseId, 'Design', 'designer', [], 1, 30, [])
        );

        (new ScheduleOrderHandler($this->repo))(new ScheduleOrderCommand($orderId));

        $order = $this->repo->get(OrderId::fromString($orderId));
        $phase = $order->phases()[0];
        $this->assertEquals(new \DateTimeImmutable('2026-04-01'), $phase->startDate());
        $this->assertEquals(new \DateTimeImmutable('2026-05-01'), $phase->endDate());
    }
}
