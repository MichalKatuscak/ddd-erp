<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Application\GetQuoteDetail\{GetQuoteDetailHandler, GetQuoteDetailQuery};
use Sales\Quote\Domain\{QuoteId, QuoteStatus};
use PHPUnit\Framework\TestCase;
final class GetQuoteDetailHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private string $quoteId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $qid = QuoteId::generate()->value();
        (new CreateQuoteHandler($this->repository))(
            new CreateQuoteCommand($qid, InquiryId::generate()->value(), date('Y-m-d', strtotime('+30 days')), 'Notes')
        );
        $this->quoteId = $qid;
    }
    public function test_returns_quote_detail(): void
    {
        $handler = new GetQuoteDetailHandler($this->repository);
        $dto = ($handler)(new GetQuoteDetailQuery($this->quoteId));
        $this->assertSame($this->quoteId, $dto->id);
        $this->assertSame('draft', $dto->status);
        $this->assertSame('Notes', $dto->notes);
        $this->assertSame(0, $dto->totalPriceAmount);
        $this->assertIsArray($dto->phases);
    }
}
