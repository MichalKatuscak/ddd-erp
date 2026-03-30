<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class CustomerNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Customer not found: '$id'");
    }
}
