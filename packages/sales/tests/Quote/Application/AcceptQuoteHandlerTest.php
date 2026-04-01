<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Application\AcceptQuote\{AcceptQuoteCommand, AcceptQuoteHandler};
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Application\SendQuote\{SendQuoteCommand, SendQuoteHandler};
use Sales\Quote\Domain\{QuoteAccepted, QuoteId, QuoteStatus};
use Sales\Tests\Inquiry\Application\{SpyCommandBus, SpyEventBus};
use PHPUnit\Framework\TestCase;
final class AcceptQuoteHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private SpyEventBus $eventBus;
    private SpyCommandBus $commandBus;
    private string $quoteId;
    private string $inquiryId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $this->eventBus   = new SpyEventBus();
        $this->commandBus = new SpyCommandBus();
        $qid = QuoteId::generate()->value();
        $iid = InquiryId::generate()->value();
        (new CreateQuoteHandler($this->repository))(new CreateQuoteCommand($qid, $iid, date('Y-m-d', strtotime('+30 days')), ''));
        (new SendQuoteHandler($this->repository))(new SendQuoteCommand($qid));
        $this->quoteId   = $qid;
        $this->inquiryId = $iid;
    }
    public function test_accepts_quote_and_emits_event(): void
    {
        $handler = new AcceptQuoteHandler($this->repository, $this->eventBus, $this->commandBus);
        ($handler)(new AcceptQuoteCommand($this->quoteId));
        $quote = $this->repository->get(QuoteId::fromString($this->quoteId));
        $this->assertSame(QuoteStatus::Accepted, $quote->status());
        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(QuoteAccepted::class, $this->eventBus->dispatched[0]);
    }
    public function test_advances_inquiry_to_won(): void
    {
        $handler = new AcceptQuoteHandler($this->repository, $this->eventBus, $this->commandBus);
        ($handler)(new AcceptQuoteCommand($this->quoteId));
        $this->assertCount(1, $this->commandBus->dispatched);
        $cmd = $this->commandBus->dispatched[0];
        $this->assertInstanceOf(\Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand::class, $cmd);
        $this->assertSame('won', $cmd->targetStatus);
        $this->assertSame($this->inquiryId, $cmd->inquiryId);
    }
}
