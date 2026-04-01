<?php
declare(strict_types=1);
namespace Sales\Quote\Application\RejectQuote;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class RejectQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(RejectQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->reject();
        $this->repository->save($quote);
    }
}
