<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

use Doctrine\DBAL\Connection;
use Identity\User\Domain\UserNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserDetailHandler
{
    public function __construct(private readonly Connection $connection) {}

    public function __invoke(GetUserDetailQuery $query): UserDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, role_ids, active, created_at FROM identity_users WHERE id = :id',
            ['id' => $query->userId],
        )->fetchAssociative();

        if ($row === false) {
            throw new UserNotFoundException($query->userId);
        }

        return new UserDetailDTO(
            id: $row['id'],
            email: $row['email'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            roleIds: json_decode($row['role_ids'], true),
            active: (bool) $row['active'],
            createdAt: $row['created_at'],
        );
    }
}
