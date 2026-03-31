<?php
declare(strict_types=1);

namespace Planning\Tests\Order\Domain;

use Planning\Order\Domain\CycleDetectedException;
use Planning\Order\Domain\InvalidStatusTransitionException;
use Planning\Order\Domain\Order;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderStatus;
use Planning\Order\Domain\Phase;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\WorkerRole;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private OrderId $id;
    private \DateTimeImmutable $start;

    protected function setUp(): void
    {
        $this->id = OrderId::generate();
        $this->start = new \DateTimeImmutable('2026-04-01');
    }

    public function test_creates_order_with_new_status(): void
    {
        $order = Order::create($this->id, 'CRM Redesign', 'Acme Corp', $this->start);

        $this->assertSame(OrderStatus::New, $order->status());
        $this->assertSame('CRM Redesign', $order->name());
        $this->assertEmpty($order->phases());
    }

    public function test_adds_phase(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $phase = Phase::create(PhaseId::generate(), 'Design', WorkerRole::Designer, [], 1, 30, []);
        $order->addPhase($phase);

        $this->assertCount(1, $order->phases());
    }

    public function test_detects_direct_cycle(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $idA = PhaseId::generate();
        $idB = PhaseId::generate();

        $phaseA = Phase::create($idA, 'Phase A', WorkerRole::Backend, [], 1, 10, []);
        $phaseB = Phase::create($idB, 'Phase B', WorkerRole::Backend, [], 1, 10, [$idA->value()]);
        $order->addPhase($phaseA);
        $order->addPhase($phaseB);

        // Now try to add phaseA2 that depends on B (creating cycle A→B→A)
        $phaseA2 = Phase::create($idA, 'Phase A updated', WorkerRole::Backend, [], 1, 10, [$idB->value()]);

        $this->expectException(CycleDetectedException::class);
        $order->addPhase($phaseA2);
    }

    public function test_schedules_single_phase_from_start_date(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $phase = Phase::create(PhaseId::generate(), 'Design', WorkerRole::Designer, [], 1, 30, []);
        $order->addPhase($phase);
        $order->schedule();

        $this->assertEquals(new \DateTimeImmutable('2026-04-01'), $phase->startDate());
        $this->assertEquals(new \DateTimeImmutable('2026-05-01'), $phase->endDate());
    }

    public function test_schedules_sequential_phases(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $idA = PhaseId::generate();
        $phaseA = Phase::create($idA, 'Design', WorkerRole::Designer, [], 1, 30, []);
        $phaseB = Phase::create(PhaseId::generate(), 'Development', WorkerRole::Backend, [], 1, 60, [$idA->value()]);
        $order->addPhase($phaseA);
        $order->addPhase($phaseB);
        $order->schedule();

        // B starts after A ends (Apr 1 + 30 days = May 1)
        $this->assertEquals(new \DateTimeImmutable('2026-05-01'), $phaseB->startDate());
        $this->assertEquals(new \DateTimeImmutable('2026-06-30'), $phaseB->endDate());
    }

    public function test_schedules_dag_phase_waits_for_latest_predecessor(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $idA = PhaseId::generate(); // 30 days, ends May 1
        $idB = PhaseId::generate(); // 60 days, ends May 31
        $idC = PhaseId::generate(); // depends on both A and B

        $phaseA = Phase::create($idA, 'Design',   WorkerRole::Designer, [], 1, 30, []);
        $phaseB = Phase::create($idB, 'Research',  WorkerRole::Pm,      [], 1, 60, []);
        $phaseC = Phase::create($idC, 'Dev',       WorkerRole::Backend, [], 1, 30, [$idA->value(), $idB->value()]);

        $order->addPhase($phaseA);
        $order->addPhase($phaseB);
        $order->addPhase($phaseC);
        $order->schedule();

        // C must start after both A (May 1) and B (May 31) → starts May 31
        $this->assertEquals(new \DateTimeImmutable('2026-05-31'), $phaseC->startDate());
    }

    public function test_advance_status_new_to_confirmed(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $order->advanceStatus();

        $this->assertSame(OrderStatus::Confirmed, $order->status());
    }

    public function test_throws_when_advancing_past_shipped(): void
    {
        $order = Order::create($this->id, 'Project', 'Client', $this->start);
        $order->advanceStatus(); // → confirmed
        $order->advanceStatus(); // → in_progress
        $order->advanceStatus(); // → completed
        $order->advanceStatus(); // → shipped

        $this->expectException(InvalidStatusTransitionException::class);
        $order->advanceStatus();
    }
}
