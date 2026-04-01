<?php
declare(strict_types=1);
namespace Sales\Quote\Application\CreateQuote;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Domain\{Quote, QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class CreateQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(CreateQuoteCommand $command): void
    {
        $quote = Quote::create(
            QuoteId::fromString($command->quoteId),
            InquiryId::fromString($command->inquiryId),
            new \DateTimeImmutable($command->validUntil),
            $command->notes,
        );
        $this->repository->save($quote);
    }
}
