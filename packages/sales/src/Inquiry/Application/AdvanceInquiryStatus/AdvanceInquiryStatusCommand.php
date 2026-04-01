<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AdvanceInquiryStatus;
final readonly class AdvanceInquiryStatusCommand
{
    public function __construct(
        public string  $inquiryId,
        public ?string $targetStatus,
    ) {}
}
