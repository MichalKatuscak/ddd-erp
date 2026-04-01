<?php
declare(strict_types=1);

namespace Sales\Tests\Inquiry\Domain;

use Sales\Inquiry\Domain\Inquiry;
use Sales\Inquiry\Domain\InquiryCreated;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Inquiry\Domain\InquiryStatus;
use Sales\Inquiry\Domain\InvalidStatusTransitionException;
use Sales\Inquiry\Domain\RequiredRole;
use Sales\Inquiry\Domain\Attachment;
use Sales\Inquiry\Domain\SalesRole;
use PHPUnit\Framework\TestCase;

final class InquiryTest extends TestCase
{
    private InquiryId $id;

    protected function setUp(): void
    {
        $this->id = InquiryId::fromString('018e8f2a-1234-7000-8000-000000000001');
    }

    public function test_creates_inquiry_with_new_status(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []);
        $this->assertSame(InquiryStatus::New, $inquiry->status());
        $this->assertSame('Firma s.r.o.', $inquiry->customerName());
    }

    public function test_creation_emits_inquiry_created_event(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []);
        $events = $inquiry->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InquiryCreated::class, $events[0]);
    }

    public function test_advances_status_linearly(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $inquiry->pullDomainEvents();
        $inquiry->advanceStatus(null);
        $this->assertSame(InquiryStatus::InProgress, $inquiry->status());
    }

    public function test_can_mark_as_won_from_quoted(): void
    {
        $inquiry = Inquiry::reconstruct($this->id, null, 'Firma', 'a@b.cz', 'X', null, [], [], InquiryStatus::Quoted, new \DateTimeImmutable());
        $inquiry->advanceStatus('won');
        $this->assertSame(InquiryStatus::Won, $inquiry->status());
    }

    public function test_throws_on_invalid_transition(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $this->expectException(InvalidStatusTransitionException::class);
        $inquiry->advanceStatus('won');
    }

    public function test_adds_attachment(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $inquiry->addAttachment(new Attachment('path/to/file.pdf', 'application/pdf', 'file.pdf'));
        $this->assertCount(1, $inquiry->attachments());
    }
}
