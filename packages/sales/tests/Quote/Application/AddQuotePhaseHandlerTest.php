<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\{InquiryId, SalesRole};
use Sales\Quote\Application\AddQuotePhase\{AddQuotePhaseCommand, AddQuotePhaseHandler};
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Domain\{Money, QuoteId, QuotePhaseId};
use PHPUnit\Framework\TestCase;
final class AddQuotePhaseHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private string $quoteId;
    private AddQuotePhaseHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $qid = QuoteId::generate()->value();
        (new CreateQuoteHandler($this->repository))(new CreateQuoteCommand($qid, InquiryId::generate()->value(), date('Y-m-d', strtotime('+30 days')), ''));
        $this->quoteId = $qid;
        $this->handler = new AddQuotePhaseHandler($this->repository);
    }
    public function test_adds_phase_and_updates_total(): void
    {
        $pid = QuotePhaseId::generate()->value();
        ($this->handler)(new AddQuotePhaseCommand($this->quoteId, $pid, 'Backend', 'backend', 5, 10000, 'CZK'));
        $quote = $this->repository->get(QuoteId::fromString($this->quoteId));
        $this->assertCount(1, $quote->phases());
        $this->assertSame(50000, $quote->totalPrice()->amount);
    }
}
