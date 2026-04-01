<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\AdvanceInquiryStatus\{AdvanceInquiryStatusCommand, AdvanceInquiryStatusHandler};
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Domain\{InquiryId, InquiryStatus, InvalidStatusTransitionException};
use PHPUnit\Framework\TestCase;
final class AdvanceInquiryStatusHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private AdvanceInquiryStatusHandler $handler;
    private string $inquiryId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $id = InquiryId::generate()->value();
        (new CreateInquiryHandler($this->repository, new SpyEventBus()))(
            new CreateInquiryCommand($id, null, 'Firma', 'a@b.cz', 'X', null, [])
        );
        $this->inquiryId = $id;
        $this->handler = new AdvanceInquiryStatusHandler($this->repository);
    }
    public function test_advances_linearly(): void
    {
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null));
        $this->assertSame(InquiryStatus::InProgress, $this->repository->get(InquiryId::fromString($this->inquiryId))->status());
    }
    public function test_sets_terminal_status(): void
    {
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null)); // new→in_progress
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null)); // in_progress→quoted
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, 'won'));
        $this->assertSame(InquiryStatus::Won, $this->repository->get(InquiryId::fromString($this->inquiryId))->status());
    }
    public function test_throws_on_invalid_transition(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, 'won'));
    }
}
