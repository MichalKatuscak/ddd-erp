<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCustomerListHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return CustomerListItemDTO[] */
    public function __invoke(GetCustomerListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, registered_at
             FROM crm_customers
             ORDER BY registered_at DESC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new CustomerListItemDTO(
                id: $row['id'],
                email: $row['email'],
                fullName: $row['first_name'] . ' ' . $row['last_name'],
                registeredAt: $row['registered_at'],
            ),
            $rows,
        );
    }
}
