<?php
declare(strict_types=1);

namespace Planning\Tests\Worker\Application;

use Planning\Worker\Application\RegisterWorker\RegisterWorkerCommand;
use Planning\Worker\Application\RegisterWorker\RegisterWorkerHandler;
use Planning\Worker\Application\UpdateWorkerSkills\UpdateWorkerSkillsCommand;
use Planning\Worker\Application\UpdateWorkerSkills\UpdateWorkerSkillsHandler;
use Planning\Worker\Domain\WorkerId;
use Planning\Worker\Domain\WorkerRole;
use PHPUnit\Framework\TestCase;

final class RegisterWorkerHandlerTest extends TestCase
{
    private InMemoryWorkerRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryWorkerRepository();
    }

    public function test_registers_worker(): void
    {
        $id = '019577a0-0000-7000-8000-000000000010';
        (new RegisterWorkerHandler($this->repo))
            (new RegisterWorkerCommand($id, 'backend', ['PHP', 'Symfony']));

        $worker = $this->repo->get(WorkerId::fromString($id));
        $this->assertSame(WorkerRole::Backend, $worker->primaryRole());
        $this->assertSame(['PHP', 'Symfony'], $worker->skills());
    }

    public function test_updates_worker_skills(): void
    {
        $id = '019577a0-0000-7000-8000-000000000010';
        (new RegisterWorkerHandler($this->repo))
            (new RegisterWorkerCommand($id, 'backend', ['PHP']));

        (new UpdateWorkerSkillsHandler($this->repo))
            (new UpdateWorkerSkillsCommand($id, 'backend', ['PHP', 'PostgreSQL', 'Symfony']));

        $worker = $this->repo->get(WorkerId::fromString($id));
        $this->assertSame(['PHP', 'PostgreSQL', 'Symfony'], $worker->skills());
    }
}
