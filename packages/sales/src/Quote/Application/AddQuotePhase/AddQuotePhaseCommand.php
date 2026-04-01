<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AddQuotePhase;
final readonly class AddQuotePhaseCommand
{
    public function __construct(
        public string $quoteId,
        public string $phaseId,
        public string $name,
        public string $requiredRole,
        public int    $durationDays,
        public int    $dailyRateAmount,
        public string $dailyRateCurrency,
    ) {}
}
