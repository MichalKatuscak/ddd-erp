<?php
declare(strict_types=1);

namespace Identity\User\Application\UpdateUser;

use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));
        $user->update(
            UserEmail::fromString($command->email),
            UserName::fromParts($command->firstName, $command->lastName),
        );
        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
