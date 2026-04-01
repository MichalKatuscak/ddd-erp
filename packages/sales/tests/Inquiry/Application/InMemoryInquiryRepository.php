<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Domain\{Inquiry, InquiryId, InquiryNotFoundException, InquiryRepository};
final class InMemoryInquiryRepository implements InquiryRepository
{
    private array $items = [];
    public function get(InquiryId $id): Inquiry
    {
        return $this->items[$id->value()] ?? throw new InquiryNotFoundException($id->value());
    }
    public function save(Inquiry $inquiry): void
    {
        $this->items[$inquiry->id()->value()] = $inquiry;
    }
    /** @return \Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO[] */
    public function findAll(?string $status): array
    {
        $items = array_values($this->items);
        if ($status !== null) {
            $items = array_filter($items, fn($i) => $i->status()->value === $status);
        }
        return array_map(fn($i) => new \Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO(
            $i->id()->value(), $i->customerName(), $i->description(),
            $i->status()->value, $i->requestedDeadline()?->format('Y-m-d'), $i->createdAt()->format('c'),
        ), array_values($items));
    }
}
