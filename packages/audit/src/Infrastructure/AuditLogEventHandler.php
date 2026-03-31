<?php
declare(strict_types=1);

namespace Audit\Infrastructure;

use Crm\Contacts\Domain\CustomerRegistered;
use Crm\Contacts\Domain\CustomerUpdated;
use Doctrine\ORM\EntityManagerInterface;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RolePermissionsUpdated;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserUpdated;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

final class AuditLogEventHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenCustomerRegistered(CustomerRegistered $event): void
    {
        $this->persist('CustomerRegistered', $event->customerId->value(), [
            'email'      => $event->email->value(),
            'first_name' => $event->name->firstName(),
            'last_name'  => $event->name->lastName(),
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenCustomerUpdated(CustomerUpdated $event): void
    {
        $this->persist('CustomerUpdated', $event->customerId->value(), [
            'email'      => $event->email->value(),
            'first_name' => $event->name->firstName(),
            'last_name'  => $event->name->lastName(),
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenUserCreated(UserCreated $event): void
    {
        $this->persist('UserCreated', $event->userId->value(), [
            'email'      => $event->email->value(),
            'first_name' => $event->name->firstName(),
            'last_name'  => $event->name->lastName(),
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenUserUpdated(UserUpdated $event): void
    {
        $this->persist('UserUpdated', $event->userId->value(), [
            'email'      => $event->email->value(),
            'first_name' => $event->name->firstName(),
            'last_name'  => $event->name->lastName(),
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenUserDeactivated(UserDeactivated $event): void
    {
        $this->persist('UserDeactivated', $event->userId->value(), [], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenRoleAssignedToUser(RoleAssignedToUser $event): void
    {
        $this->persist('RoleAssignedToUser', $event->userId->value(), [
            'role_ids' => array_map(fn(RoleId $id) => $id->value(), $event->roleIds),
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenRoleCreated(RoleCreated $event): void
    {
        $this->persist('RoleCreated', $event->roleId->value(), [
            'name'        => $event->name->value(),
            'permissions' => $event->permissions,
        ], $event->occurredAt);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function whenRolePermissionsUpdated(RolePermissionsUpdated $event): void
    {
        $this->persist('RolePermissionsUpdated', $event->roleId->value(), [
            'permissions' => $event->permissions,
        ], $event->occurredAt);
    }

    private function persist(
        string $eventType,
        string $aggregateId,
        array $payload,
        \DateTimeImmutable $occurredAt,
    ): void {
        $user  = $this->security->getUser();
        $entry = new AuditLogEntry(
            id: (string) Uuid::v7(),
            eventType: $eventType,
            aggregateId: $aggregateId,
            payload: $payload,
            performedBy: $user?->getUserIdentifier(),
            occurredAt: $occurredAt,
        );
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
