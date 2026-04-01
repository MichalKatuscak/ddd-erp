<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AddQuotePhase;
use Sales\Inquiry\Domain\SalesRole;
use Sales\Quote\Domain\{Money, QuoteId, QuotePhase, QuotePhaseId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AddQuotePhaseHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(AddQuotePhaseCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->addPhase(new QuotePhase(
            QuotePhaseId::fromString($command->phaseId),
            $command->name,
            SalesRole::from($command->requiredRole),
            $command->durationDays,
            new Money($command->dailyRateAmount, $command->dailyRateCurrency),
        ));
        $this->repository->save($quote);
    }
}
