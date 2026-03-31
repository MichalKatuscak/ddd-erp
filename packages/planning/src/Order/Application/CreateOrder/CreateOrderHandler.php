<?php
declare(strict_types=1);

namespace Planning\Order\Application\CreateOrder;

use Planning\Order\Domain\Order;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateOrderHandler
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function __invoke(CreateOrderCommand $command): void
    {
        $order = Order::create(
            OrderId::fromString($command->orderId),
            $command->name,
            $command->clientName,
            new \DateTimeImmutable($command->plannedStartDate),
        );
        $this->repository->save($order);
    }
}
