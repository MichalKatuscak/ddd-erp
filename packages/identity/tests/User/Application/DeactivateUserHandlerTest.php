<?php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\DeactivateUser\DeactivateUserCommand;
use Identity\User\Application\DeactivateUser\DeactivateUserHandler;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class DeactivateUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingUserId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingUserId = UserId::generate()->value();
        $registerHandler = new RegisterUserHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterUserCommand(
            userId: $this->existingUserId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_deactivates_user(): void
    {
        $handler = new DeactivateUserHandler($this->repository, $this->eventBus);
        ($handler)(new DeactivateUserCommand(userId: $this->existingUserId));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $this->assertFalse($user->isActive());
    }

    public function test_dispatches_user_deactivated_event(): void
    {
        $handler = new DeactivateUserHandler($this->repository, $this->eventBus);
        ($handler)(new DeactivateUserCommand(userId: $this->existingUserId));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserDeactivated::class, $this->eventBus->dispatched[0]);
    }
}
