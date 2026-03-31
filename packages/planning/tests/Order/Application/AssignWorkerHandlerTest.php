<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Application;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Application\AddPhase\AddPhaseHandler;
use Planning\Order\Application\AdvanceStatus\AdvanceStatusCommand;
use Planning\Order\Application\AdvanceStatus\AdvanceStatusHandler;
use Planning\Order\Application\AssignWorker\AssignWorkerCommand;
use Planning\Order\Application\AssignWorker\AssignWorkerHandler;
use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Application\CreateOrder\CreateOrderHandler;
use Planning\Order\Application\ScheduleOrder\ScheduleOrderCommand;
use Planning\Order\Application\ScheduleOrder\ScheduleOrderHandler;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderStatus;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Application\RegisterWorker\RegisterWorkerCommand;
use Planning\Worker\Application\RegisterWorker\RegisterWorkerHandler;
use Planning\Tests\Worker\Application\InMemoryWorkerRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class AssignWorkerHandlerTest extends TestCase
{
    private InMemoryOrderRepository $orderRepo;
    private InMemoryWorkerRepository $workerRepo;

    protected function setUp(): void
    {
        $this->orderRepo  = new InMemoryOrderRepository();
        $this->workerRepo = new InMemoryWorkerRepository();
    }

    public function test_assigns_worker_to_phase(): void
    {
        $orderId  = OrderId::generate()->value();
        $phaseId  = PhaseId::generate()->value();
        $workerId = '019577a0-0000-7000-8000-000000000010';

        (new CreateOrderHandler($this->orderRepo))(
            new CreateOrderCommand($orderId, 'Project', 'Client', '2026-04-01')
        );
        (new AddPhaseHandler($this->orderRepo))(
            new AddPhaseCommand($orderId, $phaseId, 'Design', 'designer', [], 1, 30, [])
        );
        (new ScheduleOrderHandler($this->orderRepo))(new ScheduleOrderCommand($orderId));
        (new RegisterWorkerHandler($this->workerRepo))(
            new RegisterWorkerCommand($workerId, 'designer', [])
        );

        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');

        (new AssignWorkerHandler($this->orderRepo, $this->workerRepo, $connection))(
            new AssignWorkerCommand($orderId, $phaseId, $workerId, 50)
        );

        $order = $this->orderRepo->get(OrderId::fromString($orderId));
        $this->assertCount(1, $order->phases()[0]->assignments());
        $this->assertSame($workerId, $order->phases()[0]->assignments()[0]->userId);
    }

    public function test_advances_order_status(): void
    {
        $orderId = OrderId::generate()->value();
        (new CreateOrderHandler($this->orderRepo))(
            new CreateOrderCommand($orderId, 'Project', 'Client', '2026-04-01')
        );

        (new AdvanceStatusHandler($this->orderRepo))(new AdvanceStatusCommand($orderId));

        $order = $this->orderRepo->get(OrderId::fromString($orderId));
        $this->assertSame(OrderStatus::Confirmed, $order->status());
    }
}
