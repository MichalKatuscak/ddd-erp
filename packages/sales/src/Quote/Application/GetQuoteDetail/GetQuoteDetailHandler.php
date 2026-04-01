<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetQuoteDetailHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(GetQuoteDetailQuery $query): QuoteDetailDTO
    {
        $quote = $this->repository->get(QuoteId::fromString($query->quoteId));
        $phases = array_map(fn($p) => new QuotePhaseDTO(
            $p->id()->value(), $p->name(), $p->requiredRole()->value,
            $p->durationDays(), $p->dailyRate()->amount, $p->dailyRate()->currency,
            $p->subtotal->amount, $p->subtotal->currency,
        ), $quote->phases());
        return new QuoteDetailDTO(
            id: $quote->id()->value(),
            inquiryId: $quote->inquiryId()->value(),
            validUntil: $quote->validUntil()->format('Y-m-d'),
            status: $quote->status()->value,
            pdfPath: $quote->pdfPath(),
            notes: $quote->notes(),
            phases: $phases,
            totalPriceAmount: $quote->totalPrice()->amount,
            totalPriceCurrency: $quote->totalPrice()->currency,
        );
    }
}
