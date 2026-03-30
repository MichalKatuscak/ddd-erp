<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

use Crm\Contacts\Domain\CustomerNotFoundException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCustomerDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetCustomerDetailQuery $query): CustomerDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, registered_at
             FROM crm_customers
             WHERE id = :id',
            ['id' => $query->customerId],
        )->fetchAssociative();

        if ($row === false) {
            throw new CustomerNotFoundException($query->customerId);
        }

        return new CustomerDetailDTO(
            id: $row['id'],
            email: $row['email'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            registeredAt: $row['registered_at'],
        );
    }
}
