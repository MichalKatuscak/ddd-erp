<?php
declare(strict_types=1);

namespace Planning\Worker\Application\UpdateWorkerSkills;

use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerRepository;
use Planning\Worker\Domain\WorkerRole;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateWorkerSkillsHandler
{
    public function __construct(private readonly WorkerRepository $repository) {}

    public function __invoke(UpdateWorkerSkillsCommand $command): void
    {
        $worker = $this->repository->get(WorkerId::fromString($command->workerId));
        $worker->updatePrimaryRole(WorkerRole::from($command->primaryRole));
        $worker->updateSkills($command->skills);
        $this->repository->save($worker);
    }
}
