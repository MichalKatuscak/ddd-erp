<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
use SharedKernel\Domain\DomainEvent;
final class QuoteAccepted extends DomainEvent
{
    public function __construct(
        public readonly QuoteId   $quoteId,
        public readonly InquiryId $inquiryId,
    ) {
        parent::__construct();
    }
}
