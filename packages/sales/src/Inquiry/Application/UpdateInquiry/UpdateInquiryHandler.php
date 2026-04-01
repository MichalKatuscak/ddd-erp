<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\UpdateInquiry;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository, RequiredRole, SalesRole};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class UpdateInquiryHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(UpdateInquiryCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            $command->requiredRoles,
        );
        $inquiry->update(
            $command->customerId, $command->customerName, $command->contactEmail,
            $command->description,
            $command->requestedDeadline ? new \DateTimeImmutable($command->requestedDeadline) : null,
            $roles,
        );
        $this->repository->save($inquiry);
    }
}
