<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adapts Symfony Messenger to CommandBusInterface for async command handling.
 */
final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    public function dispatch(object $command): void
    {
        $this->commandBus->dispatch($command);
    }
}
