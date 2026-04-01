<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;
final class InquiryNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Inquiry '$id' not found");
    }
}
