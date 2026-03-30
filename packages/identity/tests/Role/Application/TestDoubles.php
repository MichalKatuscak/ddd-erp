<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var Role[] */
    private array $roles = [];

    public function get(RoleId $id): Role
    {
        return $this->roles[$id->value()]
            ?? throw new RoleNotFoundException($id->value());
    }

    public function save(Role $role): void
    {
        $this->roles[$role->id()->value()] = $role;
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}

final class SpyEventBus implements EventBusInterface
{
    /** @var DomainEvent[] */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
