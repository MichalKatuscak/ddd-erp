<?php
declare(strict_types=1);

namespace Identity\User\Application\AssignRolesToUser;

use Identity\Role\Domain\RoleId;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AssignRolesToUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(AssignRolesToUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));
        $roleIds = array_map(fn(string $id) => RoleId::fromString($id), $command->roleIds);
        $user->assignRoles($roleIds);
        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
