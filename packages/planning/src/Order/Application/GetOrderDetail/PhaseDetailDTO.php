<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderDetail;

final readonly class PhaseDetailDTO
{
    /**
     * @param string[] $requiredSkills
     * @param string[] $dependsOn
     * @param array<array{user_id: string, allocation_percent: int}> $assignments
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $requiredRole,
        public array $requiredSkills,
        public int $headcount,
        public int $durationDays,
        public array $dependsOn,
        public ?string $startDate,
        public ?string $endDate,
        public array $assignments,
    ) {}
}
