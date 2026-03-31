<?php
declare(strict_types=1);

namespace Planning\Worker\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Worker extends AggregateRoot
{
    /** @var WorkerAllocation[] */
    private array $allocations;

    private function __construct(
        private readonly WorkerId $id,
        private WorkerRole $primaryRole,
        /** @var string[] */
        private array $skills,
        array $allocations = [],
    ) {
        $this->allocations = $allocations;
    }

    /** @param string[] $skills */
    public static function register(WorkerId $id, WorkerRole $primaryRole, array $skills): self
    {
        return new self($id, $primaryRole, $skills);
    }

    /** @param string[] $skills */
    public static function reconstruct(
        WorkerId $id,
        WorkerRole $primaryRole,
        array $skills,
        array $allocations,
    ): self {
        return new self($id, $primaryRole, $skills, $allocations);
    }

    public function updatePrimaryRole(WorkerRole $role): void
    {
        $this->primaryRole = $role;
    }

    /** @param string[] $skills */
    public function updateSkills(array $skills): void
    {
        $this->skills = $skills;
    }

    public function addAllocation(WorkerAllocation $allocation): void
    {
        $total = $this->totalAllocationInWindow($allocation->startDate, $allocation->endDate);
        if ($total + $allocation->allocationPercent > 100) {
            throw new OverAllocationException(
                "Worker would be {$total}% allocated — adding {$allocation->allocationPercent}% exceeds 100%"
            );
        }
        $this->allocations[] = $allocation;
    }

    private function totalAllocationInWindow(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $total = 0;
        foreach ($this->allocations as $existing) {
            if ($existing->startDate < $to && $existing->endDate > $from) {
                $total += $existing->allocationPercent;
            }
        }
        return $total;
    }

    public function id(): WorkerId { return $this->id; }
    public function primaryRole(): WorkerRole { return $this->primaryRole; }
    /** @return string[] */
    public function skills(): array { return $this->skills; }
    /** @return WorkerAllocation[] */
    public function allocations(): array { return $this->allocations; }
}
