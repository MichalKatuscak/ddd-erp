<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Application\CreateRole\CreateRoleHandler;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsCommand;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsHandler;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RolePermissionsUpdated;
use PHPUnit\Framework\TestCase;

final class UpdateRolePermissionsHandlerTest extends TestCase
{
    private InMemoryRoleRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingRoleId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRoleRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingRoleId = RoleId::generate()->value();
        $createHandler = new CreateRoleHandler($this->repository, $this->eventBus);
        ($createHandler)(new CreateRoleCommand(
            roleId: $this->existingRoleId,
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers'],
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_updates_role_permissions(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: $this->existingRoleId,
            permissions: ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'],
        ));

        $role = $this->repository->get(RoleId::fromString($this->existingRoleId));
        $this->assertSame(
            ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'],
            $role->permissions(),
        );
    }

    public function test_dispatches_role_permissions_updated_event(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: $this->existingRoleId,
            permissions: ['crm.contacts.view_customers'],
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RolePermissionsUpdated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_when_role_not_found(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: '018e8f2a-0000-7000-8000-000000000099',
            permissions: [],
        ));
    }
}
