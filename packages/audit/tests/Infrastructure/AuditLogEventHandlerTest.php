<?php
declare(strict_types=1);

namespace Audit\Tests\Infrastructure;

use Audit\Infrastructure\AuditLogEntry;
use Audit\Infrastructure\AuditLogEventHandler;
use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRegistered;
use Crm\Contacts\Domain\CustomerUpdated;
use Doctrine\ORM\EntityManagerInterface;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\Role\Domain\RolePermissionsUpdated;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserUpdated;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuditLogEventHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private Security $security;
    private AuditLogEventHandler $handler;

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->handler  = new AuditLogEventHandler($this->em, $this->security);
    }

    private function mockAuthenticatedUser(string $userId): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $this->security->method('getUser')->willReturn($user);
    }

    public function test_whenCustomerRegistered_persists_correct_entry(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenCustomerRegistered(new CustomerRegistered(
            customerId: CustomerId::fromString('01900000-0000-7000-8000-000000000001'),
            email: CustomerEmail::fromString('jan@firma.cz'),
            name: CustomerName::fromParts('Jan', 'Novak'),
        ));

        $this->assertInstanceOf(AuditLogEntry::class, $persisted);
        $this->assertSame('CustomerRegistered', $persisted->eventType());
        $this->assertSame('01900000-0000-7000-8000-000000000001', $persisted->aggregateId());
        $this->assertSame(['email' => 'jan@firma.cz', 'first_name' => 'Jan', 'last_name' => 'Novak'], $persisted->payload());
        $this->assertSame('user-abc-123', $persisted->performedBy());
    }

    public function test_whenCustomerUpdated_persists_correct_entry(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenCustomerUpdated(new CustomerUpdated(
            customerId: CustomerId::fromString('01900000-0000-7000-8000-000000000001'),
            email: CustomerEmail::fromString('novak@firma.cz'),
            name: CustomerName::fromParts('Karel', 'Novak'),
        ));

        $this->assertSame('CustomerUpdated', $persisted->eventType());
        $this->assertSame('01900000-0000-7000-8000-000000000001', $persisted->aggregateId());
        $this->assertSame(['email' => 'novak@firma.cz', 'first_name' => 'Karel', 'last_name' => 'Novak'], $persisted->payload());
    }

    public function test_whenUserCreated_persists_correct_entry(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenUserCreated(new UserCreated(
            userId: UserId::fromString('01900000-0000-7000-8000-000000000002'),
            email: UserEmail::fromString('user@firma.cz'),
            name: UserName::fromParts('Petra', 'Dvorak'),
        ));

        $this->assertSame('UserCreated', $persisted->eventType());
        $this->assertSame('01900000-0000-7000-8000-000000000002', $persisted->aggregateId());
        $this->assertSame(['email' => 'user@firma.cz', 'first_name' => 'Petra', 'last_name' => 'Dvorak'], $persisted->payload());
    }

    public function test_whenUserUpdated_persists_correct_entry(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenUserUpdated(new UserUpdated(
            userId: UserId::fromString('01900000-0000-7000-8000-000000000002'),
            email: UserEmail::fromString('updated@firma.cz'),
            name: UserName::fromParts('Petra', 'Horak'),
        ));

        $this->assertSame('UserUpdated', $persisted->eventType());
        $this->assertSame(['email' => 'updated@firma.cz', 'first_name' => 'Petra', 'last_name' => 'Horak'], $persisted->payload());
    }

    public function test_whenUserDeactivated_persists_empty_payload(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenUserDeactivated(new UserDeactivated(
            userId: UserId::fromString('01900000-0000-7000-8000-000000000002'),
        ));

        $this->assertSame('UserDeactivated', $persisted->eventType());
        $this->assertSame('01900000-0000-7000-8000-000000000002', $persisted->aggregateId());
        $this->assertSame([], $persisted->payload());
    }

    public function test_whenRoleAssignedToUser_persists_role_ids(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenRoleAssignedToUser(new RoleAssignedToUser(
            userId: UserId::fromString('01900000-0000-7000-8000-000000000002'),
            roleIds: [
                RoleId::fromString('01900000-0000-7000-8000-000000000003'),
                RoleId::fromString('01900000-0000-7000-8000-000000000004'),
            ],
        ));

        $this->assertSame('RoleAssignedToUser', $persisted->eventType());
        $this->assertSame([
            'role_ids' => [
                '01900000-0000-7000-8000-000000000003',
                '01900000-0000-7000-8000-000000000004',
            ],
        ], $persisted->payload());
    }

    public function test_whenRoleCreated_persists_name_and_permissions(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenRoleCreated(new RoleCreated(
            roleId: RoleId::fromString('01900000-0000-7000-8000-000000000003'),
            name: RoleName::fromString('editor'),
            permissions: ['crm.read', 'crm.write'],
        ));

        $this->assertSame('RoleCreated', $persisted->eventType());
        $this->assertSame('01900000-0000-7000-8000-000000000003', $persisted->aggregateId());
        $this->assertSame(['name' => 'editor', 'permissions' => ['crm.read', 'crm.write']], $persisted->payload());
    }

    public function test_whenRolePermissionsUpdated_persists_permissions(): void
    {
        $this->mockAuthenticatedUser('user-abc-123');
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenRolePermissionsUpdated(new RolePermissionsUpdated(
            roleId: RoleId::fromString('01900000-0000-7000-8000-000000000003'),
            permissions: ['crm.read'],
        ));

        $this->assertSame('RolePermissionsUpdated', $persisted->eventType());
        $this->assertSame(['permissions' => ['crm.read']], $persisted->payload());
    }

    public function test_performed_by_is_null_when_no_authenticated_user(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $persisted = null;
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (AuditLogEntry $e) use (&$persisted): void { $persisted = $e; });
        $this->em->expects($this->once())->method('flush');

        $this->handler->whenUserDeactivated(new UserDeactivated(
            userId: UserId::fromString('01900000-0000-7000-8000-000000000002'),
        ));

        $this->assertNull($persisted->performedBy());
    }
}
