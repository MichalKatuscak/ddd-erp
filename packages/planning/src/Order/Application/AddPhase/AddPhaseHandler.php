<?php
declare(strict_types=1);

namespace Planning\Order\Application\AddPhase;

use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\OrderRepository;
use Planning\Order\Domain\Phase;
use Planning\Order\Domain\PhaseId;
use Planning\Worker\Domain\WorkerRole;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AddPhaseHandler
{
    public function __construct(private readonly OrderRepository $repository) {}

    public function __invoke(AddPhaseCommand $command): void
    {
        $order = $this->repository->get(OrderId::fromString($command->orderId));
        $phase = Phase::create(
            PhaseId::fromString($command->phaseId),
            $command->name,
            WorkerRole::from($command->requiredRole),
            $command->requiredSkills,
            $command->headcount,
            $command->durationDays,
            $command->dependsOn,
        );
        $order->addPhase($phase);
        $this->repository->save($order);
    }
}
