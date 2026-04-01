<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
use Sales\Inquiry\Domain\InquiryRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetInquiryListHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    /** @return InquiryListItemDTO[] */
    public function __invoke(GetInquiryListQuery $query): array
    {
        return $this->repository->findAll($query->status);
    }
}
