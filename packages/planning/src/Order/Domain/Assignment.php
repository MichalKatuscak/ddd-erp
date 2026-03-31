<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

final readonly class Assignment
{
    public function __construct(
        public string $userId,
        public int $allocationPercent,
    ) {}
}
