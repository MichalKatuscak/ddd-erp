<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
final readonly class InquiryListItemDTO
{
    public function __construct(
        public string  $id,
        public string  $customerName,
        public string  $description,
        public string  $status,
        public ?string $requestedDeadline,
        public string  $createdAt,
    ) {}
}
