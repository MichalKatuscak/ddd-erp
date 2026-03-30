<?php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\Role\Domain\RoleId;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserHandler;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class AssignRolesToUserHandlerTest extends TestCase
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

    public function test_assigns_roles_to_user(): void
    {
        $roleId1 = RoleId::generate()->value();
        $roleId2 = RoleId::generate()->value();

        $handler = new AssignRolesToUserHandler($this->repository, $this->eventBus);
        ($handler)(new AssignRolesToUserCommand(
            userId: $this->existingUserId,
            roleIds: [$roleId1, $roleId2],
        ));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $roleIds = $user->roleIds();
        $this->assertCount(2, $roleIds);
        $this->assertSame($roleId1, $roleIds[0]->value());
        $this->assertSame($roleId2, $roleIds[1]->value());
    }

    public function test_dispatches_role_assigned_event(): void
    {
        $handler = new AssignRolesToUserHandler($this->repository, $this->eventBus);
        ($handler)(new AssignRolesToUserCommand(
            userId: $this->existingUserId,
            roleIds: [RoleId::generate()->value()],
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RoleAssignedToUser::class, $this->eventBus->dispatched[0]);
    }
}
