<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

final readonly class GetCustomerListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
