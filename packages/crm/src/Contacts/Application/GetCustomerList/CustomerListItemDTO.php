<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

final readonly class CustomerListItemDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        public string $registeredAt,
    ) {}
}
