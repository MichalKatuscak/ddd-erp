<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
final readonly class InquiryDetailDTO
{
    /** @param array<array{role:string,skills:string[]}> $requiredRoles
     *  @param array<array{id:string,path:string,mimeType:string,originalName:string}> $attachments */
    public function __construct(
        public string  $id,
        public ?string $customerId,
        public string  $customerName,
        public string  $contactEmail,
        public string  $description,
        public ?string $requestedDeadline,
        public array   $requiredRoles,
        public array   $attachments,
        public string  $status,
        public string  $createdAt,
    ) {}
}
