<?php
declare(strict_types=1);
namespace Sales\Quote\Application\SendQuote;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class SendQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(SendQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->send();
        $this->repository->save($quote);
    }
}
