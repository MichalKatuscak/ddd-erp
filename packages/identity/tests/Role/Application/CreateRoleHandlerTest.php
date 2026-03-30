<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Application\CreateRole\CreateRoleHandler;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use PHPUnit\Framework\TestCase;

final class CreateRoleHandlerTest extends TestCase
{
    private InMemoryRoleRepository $repository;
    private SpyEventBus $eventBus;
    private CreateRoleHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRoleRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new CreateRoleHandler($this->repository, $this->eventBus);
    }

    public function test_creates_role_and_persists(): void
    {
        $roleId = RoleId::generate()->value();
        $command = new CreateRoleCommand(
            roleId: $roleId,
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers', 'crm.contacts.create_customer'],
        );

        ($this->handler)($command);

        $role = $this->repository->get(RoleId::fromString($roleId));
        $this->assertSame('crm-manager', $role->name()->value());
        $this->assertSame(['crm.contacts.view_customers', 'crm.contacts.create_customer'], $role->permissions());
    }

    public function test_dispatches_role_created_event(): void
    {
        $command = new CreateRoleCommand(
            roleId: RoleId::generate()->value(),
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers'],
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RoleCreated::class, $this->eventBus->dispatched[0]);
    }
}
