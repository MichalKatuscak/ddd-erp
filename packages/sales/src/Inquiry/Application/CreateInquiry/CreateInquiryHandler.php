<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\CreateInquiry;
use Sales\Inquiry\Domain\{Inquiry, InquiryId, InquiryRepository, RequiredRole, SalesRole};
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class CreateInquiryHandler
{
    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly EventBusInterface  $eventBus,
    ) {}
    public function __invoke(CreateInquiryCommand $command): void
    {
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            $command->requiredRoles,
        );
        $inquiry = Inquiry::create(
            InquiryId::fromString($command->inquiryId),
            $command->customerId,
            $command->customerName,
            $command->contactEmail,
            $command->description,
            $command->requestedDeadline ? new \DateTimeImmutable($command->requestedDeadline) : null,
            $roles,
        );
        $this->repository->save($inquiry);
        foreach ($inquiry->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
