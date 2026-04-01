<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Application\GetInquiryList\{GetInquiryListHandler, GetInquiryListQuery};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class GetInquiryListHandlerTest extends TestCase
{
    public function test_returns_all_inquiries(): void
    {
        $repository = new InMemoryInquiryRepository();
        $createHandler = new CreateInquiryHandler($repository, new SpyEventBus());
        ($createHandler)(new CreateInquiryCommand(InquiryId::generate()->value(), null, 'Firma A', 'a@b.cz', 'X', null, []));
        ($createHandler)(new CreateInquiryCommand(InquiryId::generate()->value(), null, 'Firma B', 'b@b.cz', 'Y', null, []));
        $result = (new GetInquiryListHandler($repository))(new GetInquiryListQuery());
        $this->assertCount(2, $result);
    }
}
