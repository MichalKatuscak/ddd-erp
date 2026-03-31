<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var Phase[] */
    private array $phases;

    private function __construct(
        private readonly OrderId $id,
        private readonly string $name,
        private readonly string $clientName,
        private readonly \DateTimeImmutable $plannedStartDate,
        private OrderStatus $status,
        array $phases = [],
    ) {
        $this->phases = $phases;
    }

    public static function create(
        OrderId $id,
        string $name,
        string $clientName,
        \DateTimeImmutable $plannedStartDate,
    ): self {
        return new self($id, $name, $clientName, $plannedStartDate, OrderStatus::New);
    }

    public static function reconstruct(
        OrderId $id,
        string $name,
        string $clientName,
        \DateTimeImmutable $plannedStartDate,
        OrderStatus $status,
        array $phases,
    ): self {
        return new self($id, $name, $clientName, $plannedStartDate, $status, $phases);
    }

    public function addPhase(Phase $phase): void
    {
        if ($this->wouldCreateCycle($phase)) {
            throw new CycleDetectedException();
        }
        // Replace existing phase with same id, or add new
        foreach ($this->phases as $i => $existing) {
            if ($existing->id()->equals($phase->id())) {
                $this->phases[$i] = $phase;
                return;
            }
        }
        $this->phases[] = $phase;
    }

    public function schedule(): void
    {
        $sorted = $this->topologicalSort();
        $endDates = []; // phaseId string -> \DateTimeImmutable

        foreach ($sorted as $phase) {
            $start = $this->plannedStartDate;
            foreach ($phase->dependsOn() as $depId) {
                if (isset($endDates[$depId]) && $endDates[$depId] > $start) {
                    $start = $endDates[$depId];
                }
            }
            $end = \DateTimeImmutable::createFromMutable(
                (new \DateTime($start->format('Y-m-d')))->modify("+{$phase->durationDays()} days")
            );
            $phase->setDates($start, $end);
            $endDates[$phase->id()->value()] = $end;
        }
    }

    public function assignWorker(PhaseId $phaseId, string $userId, int $allocationPercent): void
    {
        $phase = $this->findPhase($phaseId);
        $phase->addAssignment(new Assignment($userId, $allocationPercent));
    }

    public function advanceStatus(): void
    {
        $this->status = $this->status->next();
    }

    private function wouldCreateCycle(Phase $newPhase): bool
    {
        // Build candidate phases list: replace existing phase with same id, or add new
        $phases = [];
        $replaced = false;
        foreach ($this->phases as $existing) {
            if ($existing->id()->equals($newPhase->id())) {
                $phases[] = $newPhase;
                $replaced = true;
            } else {
                $phases[] = $existing;
            }
        }
        if (!$replaced) {
            $phases[] = $newPhase;
        }

        try {
            $this->topologicalSortFrom($phases);
            return false;
        } catch (CycleDetectedException) {
            return true;
        }
    }

    /** @return Phase[] topologically sorted */
    private function topologicalSort(): array
    {
        return $this->topologicalSortFrom($this->phases);
    }

    /** @param Phase[] $phases */
    private function topologicalSortFrom(array $phases): array
    {
        $phaseMap = [];
        foreach ($phases as $p) {
            $phaseMap[$p->id()->value()] = $p;
        }

        $inDegree = [];
        foreach ($phases as $p) {
            $inDegree[$p->id()->value()] = count(
                array_filter($p->dependsOn(), fn(string $depId) => isset($phaseMap[$depId]))
            );
        }

        $queue = [];
        foreach ($inDegree as $id => $deg) {
            if ($deg === 0) {
                $queue[] = $id;
            }
        }

        $sorted = [];
        while (!empty($queue)) {
            $nodeId = array_shift($queue);
            $sorted[] = $phaseMap[$nodeId];

            foreach ($phases as $p) {
                if (in_array($nodeId, $p->dependsOn(), true)) {
                    $inDegree[$p->id()->value()]--;
                    if ($inDegree[$p->id()->value()] === 0) {
                        $queue[] = $p->id()->value();
                    }
                }
            }
        }

        if (count($sorted) !== count($phases)) {
            throw new CycleDetectedException();
        }

        return $sorted;
    }

    private function findPhase(PhaseId $phaseId): Phase
    {
        foreach ($this->phases as $phase) {
            if ($phase->id()->equals($phaseId)) {
                return $phase;
            }
        }
        throw new \DomainException("Phase not found: '{$phaseId->value()}'");
    }

    public function id(): OrderId { return $this->id; }
    public function name(): string { return $this->name; }
    public function clientName(): string { return $this->clientName; }
    public function plannedStartDate(): \DateTimeImmutable { return $this->plannedStartDate; }
    public function status(): OrderStatus { return $this->status; }
    /** @return Phase[] */
    public function phases(): array { return $this->phases; }
}
