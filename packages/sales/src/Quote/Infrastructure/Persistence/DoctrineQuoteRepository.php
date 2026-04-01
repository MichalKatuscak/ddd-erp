<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Persistence;
use Doctrine\DBAL\Connection;
use Sales\Quote\Domain\{Quote, QuoteId, QuoteNotFoundException, QuoteRepository};
final class DoctrineQuoteRepository implements QuoteRepository
{
    public function __construct(private readonly Connection $connection) {}
    public function get(QuoteId $id): Quote
    {
        throw new QuoteNotFoundException($id->value());
    }
    /** @return Quote[] */
    public function findByInquiry(string $inquiryId): array
    {
        return [];
    }
    public function save(Quote $quote): void
    {
        throw new \RuntimeException('DoctrineQuoteRepository::save() not yet implemented');
    }
}
