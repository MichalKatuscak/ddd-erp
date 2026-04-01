<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\CreateInquiry;
final readonly class CreateInquiryCommand
{
    /** @param array<array{role: string, skills: string[]}> $requiredRoles */
    public function __construct(
        public string  $inquiryId,
        public ?string $customerId,
        public string  $customerName,
        public string  $contactEmail,
        public string  $description,
        public ?string $requestedDeadline,
        public array   $requiredRoles,
    ) {}
}
