<?php
declare(strict_types=1);
namespace Sales\Quote\Application\CreateQuote;
final readonly class CreateQuoteCommand
{
    public function __construct(
        public string $quoteId,
        public string $inquiryId,
        public string $validUntil,
        public string $notes,
    ) {}
}
