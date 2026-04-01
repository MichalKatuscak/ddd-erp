<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AttachFile;
use Sales\Inquiry\Domain\{Attachment, InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AttachFileHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(AttachFileCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $inquiry->addAttachment(new Attachment($command->storedPath, $command->mimeType, $command->originalName));
        $this->repository->save($inquiry);
    }
}
