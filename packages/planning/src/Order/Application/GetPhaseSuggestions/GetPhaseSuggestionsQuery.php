<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetPhaseSuggestions;

final readonly class GetPhaseSuggestionsQuery
{
    public function __construct(
        public string $orderId,
        public string $phaseId,
    ) {}
}
