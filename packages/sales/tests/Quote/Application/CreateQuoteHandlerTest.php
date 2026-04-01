<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Domain\{QuoteId, QuoteStatus};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class CreateQuoteHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private CreateQuoteHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $this->handler    = new CreateQuoteHandler($this->repository);
    }
    public function test_creates_quote_in_draft(): void
    {
        $qid = QuoteId::generate()->value();
        $iid = InquiryId::generate()->value();
        ($this->handler)(new CreateQuoteCommand($qid, $iid, date('Y-m-d', strtotime('+30 days')), ''));
        $quote = $this->repository->get(QuoteId::fromString($qid));
        $this->assertSame(QuoteStatus::Draft, $quote->status());
        $this->assertSame($iid, $quote->inquiryId()->value());
    }
}
