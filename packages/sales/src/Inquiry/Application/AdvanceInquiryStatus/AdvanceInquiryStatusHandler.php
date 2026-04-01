<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AdvanceInquiryStatus;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AdvanceInquiryStatusHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(AdvanceInquiryStatusCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $inquiry->advanceStatus($command->targetStatus);
        $this->repository->save($inquiry);
    }
}
