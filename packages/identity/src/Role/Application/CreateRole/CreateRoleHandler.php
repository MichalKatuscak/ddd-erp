<?php
declare(strict_types=1);

namespace Identity\Role\Application\CreateRole;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateRoleHandler
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateRoleCommand $command): void
    {
        $role = Role::create(
            RoleId::fromString($command->roleId),
            RoleName::fromString($command->name),
            $command->permissions,
        );

        $this->repository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
