<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetRoleListHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return RoleListItemDTO[] */
    public function __invoke(GetRoleListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, name, permissions
             FROM identity_roles
             ORDER BY name ASC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new RoleListItemDTO(
                id: $row['id'],
                name: $row['name'],
                permissions: json_decode($row['permissions'], true),
            ),
            $rows,
        );
    }
}
