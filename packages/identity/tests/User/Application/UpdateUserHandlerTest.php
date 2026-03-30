<?php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Application\UpdateUser\UpdateUserCommand;
use Identity\User\Application\UpdateUser\UpdateUserHandler;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserUpdated;
use PHPUnit\Framework\TestCase;

final class UpdateUserHandlerTest extends TestCase
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

    public function test_updates_user_email_and_name(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateUserCommand(
            userId: $this->existingUserId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $this->assertSame('petr@firma.cz', $user->email()->value());
        $this->assertSame('Petr', $user->name()->firstName());
    }

    public function test_dispatches_user_updated_event(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateUserCommand(
            userId: $this->existingUserId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserUpdated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_when_user_not_found(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new UpdateUserCommand(
            userId: '018e8f2a-0000-7000-8000-000000000099',
            email: 'x@x.cz',
            firstName: 'X',
            lastName: 'Y',
        ));
    }
}
