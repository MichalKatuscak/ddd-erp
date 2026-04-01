<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
final readonly class GetInquiryListQuery
{
    public function __construct(public ?string $status = null) {}
}
