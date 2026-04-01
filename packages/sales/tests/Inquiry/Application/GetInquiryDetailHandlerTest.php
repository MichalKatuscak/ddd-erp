<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Application\GetInquiryDetail\{GetInquiryDetailHandler, GetInquiryDetailQuery};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class GetInquiryDetailHandlerTest extends TestCase
{
    public function test_returns_inquiry_detail(): void
    {
        $repository = new InMemoryInquiryRepository();
        $id = InquiryId::generate()->value();
        (new CreateInquiryHandler($repository, new SpyEventBus()))(
            new CreateInquiryCommand($id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis projektu', null, [])
        );
        $dto = (new GetInquiryDetailHandler($repository))(new GetInquiryDetailQuery($id));
        $this->assertSame('Firma s.r.o.', $dto->customerName);
        $this->assertSame('new', $dto->status);
    }
}
