<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
final readonly class QuotePhaseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $requiredRole,
        public int    $durationDays,
        public int    $dailyRateAmount,
        public string $dailyRateCurrency,
        public int    $subtotalAmount,
        public string $subtotalCurrency,
    ) {}
}
