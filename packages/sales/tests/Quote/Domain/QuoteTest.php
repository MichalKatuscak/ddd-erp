<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Domain\{Money, Quote, QuoteId, QuotePhase, QuotePhaseId, QuoteStatus};
use Sales\Inquiry\Domain\SalesRole;
use PHPUnit\Framework\TestCase;
final class QuoteTest extends TestCase
{
    private QuoteId $id;
    private InquiryId $inquiryId;
    protected function setUp(): void
    {
        $this->id        = QuoteId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->inquiryId = InquiryId::fromString('018e8f2a-1234-7000-8000-000000000002');
    }
    public function test_creates_quote_in_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $this->assertSame(QuoteStatus::Draft, $quote->status());
        $this->assertEquals(new Money(0, 'CZK'), $quote->totalPrice());
    }
    public function test_adds_phase_and_computes_total(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $phase = new QuotePhase(QuotePhaseId::fromString('018e8f2a-1234-7000-8000-000000000003'), 'Backend', SalesRole::Backend, 10, new Money(10000, 'CZK'));
        $quote->addPhase($phase);
        $this->assertEquals(new Money(100000, 'CZK'), $quote->totalPrice());
    }
    public function test_can_send_from_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $quote->send();
        $this->assertSame(QuoteStatus::Sent, $quote->status());
    }
    public function test_cannot_accept_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $this->expectException(\DomainException::class);
        $quote->accept();
    }
    public function test_accept_emits_quote_accepted_event(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $quote->send();
        $quote->accept();
        $events = $quote->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\Sales\Quote\Domain\QuoteAccepted::class, $events[0]);
    }
}
