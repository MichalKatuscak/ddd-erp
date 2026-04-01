<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;
interface InquiryRepository
{
    public function get(InquiryId $id): Inquiry;
    public function save(Inquiry $inquiry): void;
    /** @return \Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO[] */
    public function findAll(?string $status): array;
}
