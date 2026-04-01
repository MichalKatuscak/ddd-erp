<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Quote\Domain\{Quote, QuoteId, QuoteNotFoundException, QuoteRepository};
final class InMemoryQuoteRepository implements QuoteRepository
{
    private array $items = [];
    public function get(QuoteId $id): Quote
    {
        return $this->items[$id->value()] ?? throw new QuoteNotFoundException($id->value());
    }
    public function findByInquiry(string $inquiryId): array
    {
        return array_values(array_filter($this->items, fn($q) => $q->inquiryId()->value() === $inquiryId));
    }
    public function save(Quote $quote): void
    {
        $this->items[$quote->id()->value()] = $quote;
    }
}
