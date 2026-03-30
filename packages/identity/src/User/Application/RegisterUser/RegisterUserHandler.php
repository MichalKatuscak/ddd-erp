<?php
declare(strict_types=1);

namespace Identity\User\Application\RegisterUser;

use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserPassword;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = UserEmail::fromString($command->email);
        if ($this->repository->findByEmail($email) !== null) {
            throw new \DomainException("User with email '{$command->email}' is already registered");
        }

        $user = User::create(
            UserId::fromString($command->userId),
            $email,
            UserPassword::fromPlaintext($command->password),
            UserName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
