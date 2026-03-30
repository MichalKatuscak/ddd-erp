<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\QueryBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Adapts Symfony Messenger to QueryBusInterface, extracting handler results via HandledStamp.
 */
final class MessengerQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {}

    public function dispatch(object $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        /** @var HandledStamp|null $stamp */
        $stamp = $envelope->last(HandledStamp::class);
        return $stamp?->getResult();
    }
}
