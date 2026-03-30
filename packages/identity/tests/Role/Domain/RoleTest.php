<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Domain;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\Role\Domain\RolePermissionsUpdated;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_creates_role_with_permissions(): void
    {
        $id = RoleId::generate();
        $name = RoleName::fromString('crm-manager');
        $permissions = ['crm.contacts.view_customers', 'crm.contacts.create_customer'];

        $role = Role::create($id, $name, $permissions);

        $this->assertTrue($role->id()->equals($id));
        $this->assertTrue($role->name()->equals($name));
        $this->assertSame($permissions, $role->permissions());
    }

    public function test_create_records_role_created_event(): void
    {
        $role = Role::create(RoleId::generate(), RoleName::fromString('crm-manager'), ['crm.contacts.view_customers']);
        $events = $role->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleCreated::class, $events[0]);
    }

    public function test_update_permissions(): void
    {
        $role = Role::create(RoleId::generate(), RoleName::fromString('crm-manager'), ['crm.contacts.view_customers']);
        $role->pullDomainEvents();

        $newPermissions = ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'];
        $role->updatePermissions($newPermissions);

        $this->assertSame($newPermissions, $role->permissions());
        $events = $role->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RolePermissionsUpdated::class, $events[0]);
    }
}
