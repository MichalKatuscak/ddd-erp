<?php
declare(strict_types=1);

namespace Planning\Order\Application\CreateOrderFromQuote;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Application\AddPhase\AddPhaseHandler;
use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Application\CreateOrder\CreateOrderHandler;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;
use Sales\Quote\Domain\QuoteAccepted;
use Sales\Quote\Domain\QuoteId;
use Sales\Quote\Domain\QuoteRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class CreateOrderFromQuoteHandler
{
    public function __construct(
        private readonly QuoteRepository    $quoteRepository,
        private readonly CreateOrderHandler $createOrderHandler,
        private readonly AddPhaseHandler    $addPhaseHandler,
    ) {}

    public function __invoke(QuoteAccepted $event): void
    {
        $quote = $this->quoteRepository->get(QuoteId::fromString($event->quoteId->value()));

        $orderId = OrderId::generate()->value();

        ($this->createOrderHandler)(new CreateOrderCommand(
            orderId: $orderId,
            name: 'Order from quote ' . $event->quoteId->value(),
            clientName: '',
            plannedStartDate: (new \DateTimeImmutable())->format('Y-m-d'),
        ));

        foreach ($quote->phases() as $phase) {
            ($this->addPhaseHandler)(new AddPhaseCommand(
                orderId: $orderId,
                phaseId: PhaseId::generate()->value(),
                name: $phase->name(),
                requiredRole: $phase->requiredRole()->value,
                requiredSkills: [],
                headcount: 1,
                durationDays: $phase->durationDays(),
                dependsOn: [],
            ));
        }
    }
}
