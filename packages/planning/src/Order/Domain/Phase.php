<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

use Planning\Worker\Domain\WorkerRole;

final class Phase
{
    /** @var Assignment[] */
    private array $assignments;

    private function __construct(
        private readonly PhaseId $id,
        private readonly string $name,
        private readonly WorkerRole $requiredRole,
        /** @var string[] */
        private readonly array $requiredSkills,
        private readonly int $headcount,
        private readonly int $durationDays,
        /** @var string[] PhaseId values */
        private readonly array $dependsOn,
        private ?\DateTimeImmutable $startDate,
        private ?\DateTimeImmutable $endDate,
        array $assignments,
    ) {
        $this->assignments = $assignments;
    }

    /**
     * @param string[] $requiredSkills
     * @param string[] $dependsOn  PhaseId values
     */
    public static function create(
        PhaseId $id,
        string $name,
        WorkerRole $requiredRole,
        array $requiredSkills,
        int $headcount,
        int $durationDays,
        array $dependsOn,
    ): self {
        return new self($id, $name, $requiredRole, $requiredSkills, $headcount, $durationDays, $dependsOn, null, null, []);
    }

    public static function reconstruct(
        PhaseId $id,
        string $name,
        WorkerRole $requiredRole,
        array $requiredSkills,
        int $headcount,
        int $durationDays,
        array $dependsOn,
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate,
        array $assignments,
    ): self {
        return new self($id, $name, $requiredRole, $requiredSkills, $headcount, $durationDays, $dependsOn, $startDate, $endDate, $assignments);
    }

    public function setDates(\DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $this->startDate = $start;
        $this->endDate   = $end;
    }

    public function addAssignment(Assignment $assignment): void
    {
        if (count($this->assignments) >= $this->headcount) {
            throw new \DomainException("Phase '{$this->name}' already has {$this->headcount} assignment(s) — headcount full");
        }
        $this->assignments[] = $assignment;
    }

    public function id(): PhaseId { return $this->id; }
    public function name(): string { return $this->name; }
    public function requiredRole(): WorkerRole { return $this->requiredRole; }
    /** @return string[] */
    public function requiredSkills(): array { return $this->requiredSkills; }
    public function headcount(): int { return $this->headcount; }
    public function durationDays(): int { return $this->durationDays; }
    /** @return string[] */
    public function dependsOn(): array { return $this->dependsOn; }
    public function startDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function endDate(): ?\DateTimeImmutable { return $this->endDate; }
    /** @return Assignment[] */
    public function assignments(): array { return $this->assignments; }
}
