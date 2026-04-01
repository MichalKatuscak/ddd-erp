<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
final readonly class QuoteDetailDTO
{
    /** @param QuotePhaseDTO[] $phases */
    public function __construct(
        public string  $id,
        public string  $inquiryId,
        public string  $validUntil,
        public string  $status,
        public ?string $pdfPath,
        public string  $notes,
        public array   $phases,
        public int     $totalPriceAmount,
        public string  $totalPriceCurrency,
    ) {}
}
