<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

final readonly class GetCustomerDetailQuery
{
    public function __construct(
        public string $customerId,
    ) {}
}
