<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Persistence;
use Doctrine\DBAL\Connection;
use Sales\Inquiry\Domain\{InquiryId, SalesRole};
use Sales\Quote\Domain\{Money, Quote, QuoteId, QuoteNotFoundException, QuotePhase, QuotePhaseId, QuoteRepository, QuoteStatus};
final class DoctrineQuoteRepository implements QuoteRepository
{
    public function __construct(private readonly Connection $connection) {}
    public function get(QuoteId $id): Quote
    {
        $row = $this->connection->executeQuery(
            'SELECT id, inquiry_id, valid_until, status, pdf_path, notes, total_price_amount, total_price_currency
             FROM sales_quotes WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();
        if (!$row) { throw new QuoteNotFoundException($id->value()); }
        $phaseRows = $this->connection->executeQuery(
            'SELECT id, name, required_role, duration_days, daily_rate_amount, daily_rate_currency
             FROM sales_quote_phases WHERE quote_id = :id ORDER BY sort_order',
            ['id' => $id->value()],
        )->fetchAllAssociative();
        return $this->hydrate($row, $phaseRows);
    }
    public function findByInquiry(string $inquiryId): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id FROM sales_quotes WHERE inquiry_id = :iid ORDER BY created_at DESC',
            ['iid' => $inquiryId],
        )->fetchAllAssociative();
        return array_map(fn($row) => $this->get(QuoteId::fromString($row['id'])), $rows);
    }
    public function save(Quote $quote): void
    {
        $this->connection->executeStatement(
            'INSERT INTO sales_quotes (id, inquiry_id, valid_until, status, pdf_path, notes, total_price_amount, total_price_currency, created_at)
             VALUES (:id, :inquiry_id, :valid_until, :status, :pdf_path, :notes, :tpa, :tpc, NOW())
             ON CONFLICT (id) DO UPDATE SET
                valid_until = EXCLUDED.valid_until, status = EXCLUDED.status,
                pdf_path = EXCLUDED.pdf_path, notes = EXCLUDED.notes,
                total_price_amount = EXCLUDED.total_price_amount, total_price_currency = EXCLUDED.total_price_currency',
            [
                'id'         => $quote->id()->value(),
                'inquiry_id' => $quote->inquiryId()->value(),
                'valid_until'=> $quote->validUntil()->format('Y-m-d'),
                'status'     => $quote->status()->value,
                'pdf_path'   => $quote->pdfPath(),
                'notes'      => $quote->notes(),
                'tpa'        => $quote->totalPrice()->amount,
                'tpc'        => $quote->totalPrice()->currency,
            ],
        );
        $this->connection->executeStatement('DELETE FROM sales_quote_phases WHERE quote_id = :id', ['id' => $quote->id()->value()]);
        foreach (array_values($quote->phases()) as $i => $phase) {
            $this->connection->executeStatement(
                'INSERT INTO sales_quote_phases (id, quote_id, name, required_role, duration_days, daily_rate_amount, daily_rate_currency, subtotal_amount, subtotal_currency, sort_order)
                 VALUES (:id, :quote_id, :name, :role, :days, :dra, :drc, :sa, :sc, :sort)',
                [
                    'id'       => $phase->id()->value(),
                    'quote_id' => $quote->id()->value(),
                    'name'     => $phase->name(),
                    'role'     => $phase->requiredRole()->value,
                    'days'     => $phase->durationDays(),
                    'dra'      => $phase->dailyRate()->amount,
                    'drc'      => $phase->dailyRate()->currency,
                    'sa'       => $phase->subtotal->amount,
                    'sc'       => $phase->subtotal->currency,
                    'sort'     => $i,
                ],
            );
        }
    }
    private function hydrate(array $row, array $phaseRows): Quote
    {
        $phases = array_map(fn($p) => QuotePhase::reconstruct(
            QuotePhaseId::fromString($p['id']),
            $p['name'],
            SalesRole::from($p['required_role']),
            (int) $p['duration_days'],
            new Money((int) $p['daily_rate_amount'], $p['daily_rate_currency']),
        ), $phaseRows);
        return Quote::reconstruct(
            QuoteId::fromString($row['id']),
            InquiryId::fromString($row['inquiry_id']),
            new \DateTimeImmutable($row['valid_until']),
            QuoteStatus::from($row['status']),
            $row['pdf_path'],
            $row['notes'],
            $phases,
        );
    }
}
