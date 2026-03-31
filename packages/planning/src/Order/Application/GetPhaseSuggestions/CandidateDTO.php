<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetPhaseSuggestions;

final readonly class CandidateDTO
{
    /**
     * @param string[] $skills
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $primaryRole,
        public array $skills,
        public int $availablePercent,
    ) {}
}
