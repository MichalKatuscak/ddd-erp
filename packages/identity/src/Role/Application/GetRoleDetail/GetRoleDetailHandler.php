<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

use Doctrine\DBAL\Connection;
use Identity\Role\Domain\RoleNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetRoleDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetRoleDetailQuery $query): RoleDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, name, permissions FROM identity_roles WHERE id = :id',
            ['id' => $query->roleId],
        )->fetchAssociative();

        if ($row === false) {
            throw new RoleNotFoundException($query->roleId);
        }

        return new RoleDetailDTO(
            id: $row['id'],
            name: $row['name'],
            permissions: json_decode($row['permissions'], true),
        );
    }
}
