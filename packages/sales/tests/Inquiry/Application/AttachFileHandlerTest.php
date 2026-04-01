<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\AttachFile\{AttachFileCommand, AttachFileHandler};
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class AttachFileHandlerTest extends TestCase
{
    public function test_attaches_file(): void
    {
        $repository = new InMemoryInquiryRepository();
        $id = InquiryId::generate()->value();
        (new CreateInquiryHandler($repository, new SpyEventBus()))(
            new CreateInquiryCommand($id, null, 'Firma', 'a@b.cz', 'X', null, [])
        );
        (new AttachFileHandler($repository))(new AttachFileCommand($id, 'sales/file.pdf', 'application/pdf', 'doc.pdf'));
        $inquiry = $repository->get(InquiryId::fromString($id));
        $this->assertCount(1, $inquiry->attachments());
        $this->assertSame('doc.pdf', $inquiry->attachments()[0]->originalName);
    }
}
