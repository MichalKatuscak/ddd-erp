<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
final readonly class GetInquiryDetailQuery
{
    public function __construct(public string $inquiryId) {}
}
