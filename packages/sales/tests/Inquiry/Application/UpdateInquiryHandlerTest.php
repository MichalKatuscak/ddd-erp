<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Application\UpdateInquiry\{UpdateInquiryCommand, UpdateInquiryHandler};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class UpdateInquiryHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private UpdateInquiryHandler $handler;
    private string $inquiryId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $createHandler = new CreateInquiryHandler($this->repository, new SpyEventBus());
        $id = InquiryId::generate()->value();
        ($createHandler)(new CreateInquiryCommand($id, null, 'Stará firma', 'old@b.cz', 'Old', null, []));
        $this->inquiryId = $id;
        $this->handler = new UpdateInquiryHandler($this->repository);
    }
    public function test_updates_customer_name(): void
    {
        ($this->handler)(new UpdateInquiryCommand($this->inquiryId, null, 'Nová firma', 'new@b.cz', 'Nový popis', null, []));
        $inquiry = $this->repository->get(InquiryId::fromString($this->inquiryId));
        $this->assertSame('Nová firma', $inquiry->customerName());
    }
}
