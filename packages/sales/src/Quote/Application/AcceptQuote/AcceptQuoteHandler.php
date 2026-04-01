<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AcceptQuote;
use Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use SharedKernel\Application\{CommandBusInterface, EventBusInterface};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AcceptQuoteHandler
{
    public function __construct(
        private readonly QuoteRepository    $repository,
        private readonly EventBusInterface  $eventBus,
        private readonly CommandBusInterface $commandBus,
    ) {}
    public function __invoke(AcceptQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->accept();
        $this->repository->save($quote);
        foreach ($quote->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
        $this->commandBus->dispatch(new AdvanceInquiryStatusCommand(
            $quote->inquiryId()->value(), 'won',
        ));
    }
}
