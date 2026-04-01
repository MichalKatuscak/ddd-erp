<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Domain\{InquiryCreated, InquiryId, InquiryStatus};
use PHPUnit\Framework\TestCase;
final class CreateInquiryHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private SpyEventBus $eventBus;
    private CreateInquiryHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new CreateInquiryHandler($this->repository, $this->eventBus);
    }
    public function test_creates_inquiry(): void
    {
        $id = InquiryId::generate()->value();
        ($this->handler)(new CreateInquiryCommand($id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []));
        $inquiry = $this->repository->get(InquiryId::fromString($id));
        $this->assertSame('Firma s.r.o.', $inquiry->customerName());
        $this->assertSame(InquiryStatus::New, $inquiry->status());
    }
    public function test_dispatches_inquiry_created_event(): void
    {
        ($this->handler)(new CreateInquiryCommand(InquiryId::generate()->value(), null, 'Firma', 'a@b.cz', 'X', null, []));
        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(InquiryCreated::class, $this->eventBus->dispatched[0]);
    }
}
