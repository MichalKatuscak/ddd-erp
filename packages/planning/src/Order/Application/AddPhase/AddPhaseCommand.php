<?php
declare(strict_types=1);

namespace Planning\Order\Application\AddPhase;

final readonly class AddPhaseCommand
{
    /**
     * @param string[] $requiredSkills
     * @param string[] $dependsOn PhaseId values
     */
    public function __construct(
        public string $orderId,
        public string $phaseId,
        public string $name,
        public string $requiredRole,
        public array $requiredSkills,
        public int $headcount,
        public int $durationDays,
        public array $dependsOn,
    ) {}
}
