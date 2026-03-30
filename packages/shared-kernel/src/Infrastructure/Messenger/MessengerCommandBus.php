<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

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
