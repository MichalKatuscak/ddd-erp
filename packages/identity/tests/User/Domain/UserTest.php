<?php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\Role\Domain\RoleId;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\User;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserPassword;
use Identity\User\Domain\UserUpdated;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function createUser(): User
    {
        return User::create(
            UserId::generate(),
            UserEmail::fromString('jan@firma.cz'),
            UserPassword::fromPlaintext('SecurePass123!'),
            UserName::fromParts('Jan', 'Novák'),
        );
    }

    public function test_creates_user(): void
    {
        $id       = UserId::generate();
        $email    = UserEmail::fromString('jan@firma.cz');
        $password = UserPassword::fromPlaintext('SecurePass123!');
        $name     = UserName::fromParts('Jan', 'Novák');

        $user = User::create($id, $email, $password, $name);

        $this->assertTrue($user->id()->equals($id));
        $this->assertTrue($user->email()->equals($email));
        $this->assertTrue($user->name()->equals($name));
        $this->assertTrue($user->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->createdAt());
    }

    public function test_create_records_user_created_event(): void
    {
        $user = $this->createUser();
        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function test_update_email_and_name(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $newEmail = UserEmail::fromString('petr@firma.cz');
        $newName  = UserName::fromParts('Petr', 'Svoboda');
        $user->update($newEmail, $newName);

        $this->assertTrue($user->email()->equals($newEmail));
        $this->assertTrue($user->name()->equals($newName));

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[0]);
    }

    public function test_deactivate(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $user->deactivate();

        $this->assertFalse($user->isActive());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserDeactivated::class, $events[0]);
    }

    public function test_assign_roles(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $roleId1 = RoleId::generate();
        $roleId2 = RoleId::generate();
        $user->assignRoles([$roleId1, $roleId2]);

        $roleIds = $user->roleIds();
        $this->assertCount(2, $roleIds);
        $this->assertTrue($roleIds[0]->equals($roleId1));
        $this->assertTrue($roleIds[1]->equals($roleId2));

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleAssignedToUser::class, $events[0]);
    }

    public function test_assign_roles_replaces_existing(): void
    {
        $user = $this->createUser();
        $user->assignRoles([RoleId::generate(), RoleId::generate()]);
        $user->pullDomainEvents();

        $newRoleId = RoleId::generate();
        $user->assignRoles([$newRoleId]);

        $roleIds = $user->roleIds();
        $this->assertCount(1, $roleIds);
        $this->assertTrue($roleIds[0]->equals($newRoleId));
    }

    public function test_deactivate_is_idempotent(): void
    {
        $user = $this->createUser();
        $user->deactivate();
        $user->pullDomainEvents(); // clear

        $user->deactivate(); // second call — no-op

        $this->assertFalse($user->isActive());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events);
    }

    public function test_password_verification(): void
    {
        $user = $this->createUser();
        $this->assertTrue($user->password()->verify('SecurePass123!'));
        $this->assertFalse($user->password()->verify('WrongPassword'));
    }
}
