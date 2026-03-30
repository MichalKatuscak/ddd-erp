<?php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new RegisterUserHandler($this->repository, $this->eventBus);
    }

    public function test_registers_user_and_persists(): void
    {
        $userId = UserId::generate()->value();
        $command = new RegisterUserCommand(
            userId: $userId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $user = $this->repository->get(UserId::fromString($userId));
        $this->assertSame('jan@firma.cz', $user->email()->value());
        $this->assertSame('Jan', $user->name()->firstName());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->password()->verify('SecurePass123!'));
    }

    public function test_dispatches_user_created_event(): void
    {
        $command = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserCreated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_on_duplicate_email(): void
    {
        $command1 = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );
        ($this->handler)($command1);

        $command2 = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'AnotherPass123!',
            firstName: 'Jiný',
            lastName: 'Jan',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already registered');
        ($this->handler)($command2);
    }
}
