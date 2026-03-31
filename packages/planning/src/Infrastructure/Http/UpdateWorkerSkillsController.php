<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Security\PlanningPermission;
use Planning\Worker\Application\UpdateWorkerSkills\UpdateWorkerSkillsCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/workers/{workerId}/skills', methods: ['PUT'])]
#[IsGranted(PlanningPermission::ManageWorkers->value)]
final class UpdateWorkerSkillsController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(Request $request, string $workerId): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new UpdateWorkerSkillsCommand(
            workerId: $workerId,
            primaryRole: (string) ($data['primary_role'] ?? ''),
            skills: (array) ($data['skills'] ?? []),
        ));

        return new JsonResponse(null, 204);
    }
}
