<?php
declare(strict_types=1);

namespace Planning\Order\Application\ScheduleOrder;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ScheduleOrderHandler
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function __invoke(ScheduleOrderCommand $command): void
    {
        $order = $this->repository->get(OrderId::fromString($command->orderId));
        $order->schedule();
        $this->repository->save($order);
    }
}
