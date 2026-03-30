<?php
declare(strict_types=1);

namespace Identity\Role\Application\UpdateRolePermissions;

use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateRolePermissionsHandler
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateRolePermissionsCommand $command): void
    {
        $role = $this->repository->get(RoleId::fromString($command->roleId));

        $role->updatePermissions($command->permissions);

        $this->repository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
