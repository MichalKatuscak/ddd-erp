<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
interface QuoteRepository
{
    public function get(QuoteId $id): Quote;
    /** @return Quote[] */
    public function findByInquiry(string $inquiryId): array;
    public function save(Quote $quote): void;
}
