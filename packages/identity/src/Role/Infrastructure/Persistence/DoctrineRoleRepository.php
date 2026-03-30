<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RoleRepository;

final class DoctrineRoleRepository implements RoleRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function get(RoleId $id): Role
    {
        $role = $this->entityManager->find(Role::class, $id);
        if ($role === null) {
            throw new RoleNotFoundException($id->value());
        }
        return $role;
    }

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}
