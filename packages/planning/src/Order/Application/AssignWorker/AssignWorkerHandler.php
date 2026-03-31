<?php
declare(strict_types=1);

namespace Planning\Order\Application\AssignWorker;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderRepository;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\WorkerAllocation;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final class AssignWorkerHandler
{
    public function __construct(
        private readonly OrderRepository  $orderRepository,
        private readonly WorkerRepository $workerRepository,
    ) {}

    public function __invoke(AssignWorkerCommand $command): void
    {
        $order  = $this->orderRepository->get(OrderId::fromString($command->orderId));
        $worker = $this->workerRepository->get(WorkerId::fromString($command->workerId));

        // Find phase to get its scheduled dates
        $phase = null;
        foreach ($order->phases() as $p) {
            if ($p->id()->value() === $command->phaseId) {
                $phase = $p;
                break;
            }
        }
        if ($phase === null) {
            throw new \DomainException("Phase not found: '{$command->phaseId}'");
        }
        if ($phase->startDate() === null || $phase->endDate() === null) {
            throw new \DomainException('Phase has not been scheduled yet');
        }

        // Validate worker capacity (throws OverAllocationException if over 100%)
        $worker->addAllocation(new WorkerAllocation(
            id: (string) Uuid::v7(),
            orderId: $order->id(),
            phaseId: $phase->id(),
            allocationPercent: $command->allocationPercent,
            startDate: $phase->startDate(),
            endDate: $phase->endDate(),
        ));

        // Record assignment on order
        $order->assignWorker($phase->id(), $command->workerId, $command->allocationPercent);

        $this->workerRepository->save($worker);
        $this->orderRepository->save($order);
    }
}
