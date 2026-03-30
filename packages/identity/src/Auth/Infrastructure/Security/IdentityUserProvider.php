<?php

declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final class IdentityUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $row = $this->connection->executeQuery(
            'SELECT u.id, u.email, u.role_ids
             FROM identity_users u
             WHERE u.id = :id AND u.active = true',
            ['id' => $identifier],
        )->fetchAssociative();

        if ($row === false) {
            throw new UserNotFoundException("User '$identifier' not found or inactive");
        }

        $roleIds = json_decode($row['role_ids'], true);
        $permissions = [];

        if (!empty($roleIds)) {
            $roleRows = $this->connection->executeQuery(
                'SELECT permissions FROM identity_roles WHERE id IN (?)',
                [$roleIds],
                [\Doctrine\DBAL\ArrayParameterType::STRING],
            )->fetchAllAssociative();

            foreach ($roleRows as $roleRow) {
                $rolePermissions = json_decode($roleRow['permissions'], true);
                $permissions = array_merge($permissions, $rolePermissions);
            }
        }

        $permissions = array_unique($permissions);

        $roles = array_map(
            fn(string $p) => 'ROLE_' . strtoupper(str_replace('.', '_', $p)),
            $permissions,
        );

        return new SecurityUser(
            userId: $row['id'],
            email: $row['email'],
            roles: array_values($roles),
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }
}
