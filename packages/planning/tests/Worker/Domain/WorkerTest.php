<?php
declare(strict_types=1);

namespace Planning\Tests\Worker\Domain;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\OverAllocationException;
use Planning\Worker\Domain\Worker;
use Planning\Worker\Domain\WorkerAllocation;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerRole;
use PHPUnit\Framework\TestCase;

final class WorkerTest extends TestCase
{
    private WorkerId $id;

    protected function setUp(): void
    {
        $this->id = WorkerId::fromString('019577a0-0000-7000-8000-000000000099');
    }

    public function test_registers_with_role_and_skills(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Backend, ['PHP', 'Symfony']);

        $this->assertSame(WorkerRole::Backend, $worker->primaryRole());
        $this->assertSame(['PHP', 'Symfony'], $worker->skills());
        $this->assertEmpty($worker->allocations());
    }

    public function test_updates_skills(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Frontend, ['React']);
        $worker->updateSkills(['React', 'TypeScript', 'CSS']);

        $this->assertSame(['React', 'TypeScript', 'CSS'], $worker->skills());
    }

    public function test_adds_allocation(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Backend, []);
        $allocation = $this->makeAllocation(50, '2026-04-01', '2026-05-01');
        $worker->addAllocation($allocation);

        $this->assertCount(1, $worker->allocations());
    }

    public function test_allows_two_allocations_not_exceeding_100_percent(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Backend, []);
        $worker->addAllocation($this->makeAllocation(60, '2026-04-01', '2026-05-01'));
        $worker->addAllocation($this->makeAllocation(40, '2026-04-01', '2026-05-01'));

        $this->assertCount(2, $worker->allocations());
    }

    public function test_throws_over_allocation_when_total_exceeds_100(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Backend, []);
        $worker->addAllocation($this->makeAllocation(70, '2026-04-01', '2026-05-01'));

        $this->expectException(OverAllocationException::class);
        $worker->addAllocation($this->makeAllocation(40, '2026-04-01', '2026-05-01'));
    }

    public function test_non_overlapping_allocations_do_not_conflict(): void
    {
        $worker = Worker::register($this->id, WorkerRole::Backend, []);
        $worker->addAllocation($this->makeAllocation(100, '2026-04-01', '2026-05-01'));
        // May start on the same day the previous one ends
        $worker->addAllocation($this->makeAllocation(100, '2026-05-01', '2026-06-01'));

        $this->assertCount(2, $worker->allocations());
    }

    private function makeAllocation(int $percent, string $start, string $end): WorkerAllocation
    {
        return new WorkerAllocation(
            id: '019577a0-0000-7000-8000-' . str_pad((string) rand(1, 999999), 12, '0', STR_PAD_LEFT),
            orderId: OrderId::generate(),
            phaseId: PhaseId::generate(),
            allocationPercent: $percent,
            startDate: new \DateTimeImmutable($start),
            endDate: new \DateTimeImmutable($end),
        );
    }
}
