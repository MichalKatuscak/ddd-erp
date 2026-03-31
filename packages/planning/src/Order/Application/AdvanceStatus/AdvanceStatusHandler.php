<?php
declare(strict_types=1);

namespace Planning\Order\Application\AdvanceStatus;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AdvanceStatusHandler
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function __invoke(AdvanceStatusCommand $command): void
    {
        $order = $this->repository->get(OrderId::fromString($command->orderId));
        $order->advanceStatus();
        $this->repository->save($order);
    }
}
