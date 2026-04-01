<?php
declare(strict_types=1);
namespace Sales\Quote\Application\UpdateQuotePhase;
use Sales\Inquiry\Domain\SalesRole;
use Sales\Quote\Domain\{Money, QuoteId, QuotePhaseId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class UpdateQuotePhaseHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(UpdateQuotePhaseCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->updatePhase(
            QuotePhaseId::fromString($command->phaseId),
            $command->name,
            SalesRole::from($command->requiredRole),
            $command->durationDays,
            new Money($command->dailyRateAmount, $command->dailyRateCurrency),
        );
        $this->repository->save($quote);
    }
}
