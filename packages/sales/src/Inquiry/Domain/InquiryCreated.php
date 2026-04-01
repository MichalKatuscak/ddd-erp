<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;

use SharedKernel\Domain\DomainEvent;

final class InquiryCreated extends DomainEvent
{
    public function __construct(public readonly InquiryId $inquiryId)
    {
        parent::__construct();
    }
}
