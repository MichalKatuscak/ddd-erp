<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use SharedKernel\Domain\DomainEvent;

final class CustomerUpdated extends DomainEvent
{
    public function __construct(
        public readonly CustomerId $customerId,
        public readonly CustomerEmail $email,
        public readonly CustomerName $name,
    ) {
        parent::__construct();
    }
}
