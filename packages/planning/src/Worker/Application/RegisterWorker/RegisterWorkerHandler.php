<?php
declare(strict_types=1);

namespace Planning\Worker\Application\RegisterWorker;

use Planning\Worker\Domain\Worker;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerRepository;
use Planning\Worker\Domain\WorkerRole;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterWorkerHandler
{
    public function __construct(private readonly WorkerRepository $repository) {}

    public function __invoke(RegisterWorkerCommand $command): void
    {
        $worker = Worker::register(
            WorkerId::fromString($command->workerId),
            WorkerRole::from($command->primaryRole),
            $command->skills,
        );
        $this->repository->save($worker);
    }
}
