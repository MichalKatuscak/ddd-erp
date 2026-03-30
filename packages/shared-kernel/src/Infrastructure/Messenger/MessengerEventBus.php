<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
