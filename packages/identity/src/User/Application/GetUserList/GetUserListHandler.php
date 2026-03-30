<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserListHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return UserListItemDTO[] */
    public function __invoke(GetUserListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, role_ids, active FROM identity_users ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new UserListItemDTO(
                id: $row['id'],
                email: $row['email'],
                fullName: $row['first_name'] . ' ' . $row['last_name'],
                roleIds: json_decode($row['role_ids'], true),
                active: (bool) $row['active'],
            ),
            $rows,
        );
    }
}
