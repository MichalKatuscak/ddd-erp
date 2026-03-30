<?php
declare(strict_types=1);

namespace Identity\User\Application\DeactivateUser;

use Identity\User\Domain\UserId;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeactivateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeactivateUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));
        $user->deactivate();
        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
