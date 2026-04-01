<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetInquiryDetailHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(GetInquiryDetailQuery $query): InquiryDetailDTO
    {
        $inquiry = $this->repository->get(InquiryId::fromString($query->inquiryId));
        return new InquiryDetailDTO(
            id: $inquiry->id()->value(),
            customerId: $inquiry->customerId(),
            customerName: $inquiry->customerName(),
            contactEmail: $inquiry->contactEmail(),
            description: $inquiry->description(),
            requestedDeadline: $inquiry->requestedDeadline()?->format('Y-m-d'),
            requiredRoles: array_map(
                fn($r) => ['role' => $r->role->value, 'skills' => $r->skills],
                $inquiry->requiredRoles(),
            ),
            attachments: array_map(
                fn($a) => ['id' => $a->id, 'path' => $a->path, 'mimeType' => $a->mimeType, 'originalName' => $a->originalName],
                $inquiry->attachments(),
            ),
            status: $inquiry->status()->value,
            createdAt: $inquiry->createdAt()->format('c'),
        );
    }
}
