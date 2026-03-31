<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Application;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Application\AddPhase\AddPhaseHandler;
use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Application\CreateOrder\CreateOrderHandler;
use Planning\Order\Domain\CycleDetectedException;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderStatus;
use Planning\Order\Domain\PhaseId;
use PHPUnit\Framework\TestCase;

final class CreateOrderHandlerTest extends TestCase
{
    private InMemoryOrderRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryOrderRepository();
    }

    public function test_creates_order(): void
    {
        $id = OrderId::generate()->value();
        (new CreateOrderHandler($this->repo))(
            new CreateOrderCommand($id, 'CRM Project', 'Acme Corp', '2026-04-01')
        );

        $order = $this->repo->get(OrderId::fromString($id));
        $this->assertSame('CRM Project', $order->name());
        $this->assertSame(OrderStatus::New, $order->status());
    }

    public function test_adds_phase_to_order(): void
    {
        $orderId = OrderId::generate()->value();
        (new CreateOrderHandler($this->repo))(
            new CreateOrderCommand($orderId, 'Project', 'Client', '2026-04-01')
        );

        $phaseId = PhaseId::generate()->value();
        (new AddPhaseHandler($this->repo))(new AddPhaseCommand(
            orderId: $orderId,
            phaseId: $phaseId,
            name: 'Design',
            requiredRole: 'designer',
            requiredSkills: ['Figma'],
            headcount: 1,
            durationDays: 30,
            dependsOn: [],
        ));

        $order = $this->repo->get(OrderId::fromString($orderId));
        $this->assertCount(1, $order->phases());
        $this->assertSame('Design', $order->phases()[0]->name());
    }

    public function test_add_phase_rejects_cycle(): void
    {
        $orderId = OrderId::generate()->value();
        (new CreateOrderHandler($this->repo))(
            new CreateOrderCommand($orderId, 'Project', 'Client', '2026-04-01')
        );

        $idA = PhaseId::generate()->value();
        $idB = PhaseId::generate()->value();
        $handler = new AddPhaseHandler($this->repo);

        $handler(new AddPhaseCommand($orderId, $idA, 'A', 'backend', [], 1, 10, []));
        $handler(new AddPhaseCommand($orderId, $idB, 'B', 'backend', [], 1, 10, [$idA]));

        $this->expectException(CycleDetectedException::class);
        // Adding A again with dependsOn B creates A→B→A cycle
        $handler(new AddPhaseCommand($orderId, $idA, 'A', 'backend', [], 1, 10, [$idB]));
    }
}
