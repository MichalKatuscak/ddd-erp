<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\UpdateCustomer;

final readonly class UpdateCustomerCommand
{
    public function __construct(
        public string $customerId,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
