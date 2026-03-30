<?php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class InMemoryUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];

    public function get(UserId $id): User
    {
        return $this->users[$id->value()]
            ?? throw new UserNotFoundException($id->value());
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
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
