<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

final readonly class CustomerDetailDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $registeredAt,
    ) {}
}
