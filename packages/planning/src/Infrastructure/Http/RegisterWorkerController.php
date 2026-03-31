<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Security\PlanningPermission;
use Planning\Worker\Application\RegisterWorker\RegisterWorkerCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/workers', methods: ['POST'])]
#[IsGranted(PlanningPermission::ManageWorkers->value)]
final class RegisterWorkerController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true) ?? [];
        $workerId = (string) ($data['user_id'] ?? '');

        $this->commandBus->dispatch(new RegisterWorkerCommand(
            workerId: $workerId,
            primaryRole: (string) ($data['primary_role'] ?? ''),
            skills: (array) ($data['skills'] ?? []),
        ));

        return new JsonResponse(['id' => $workerId], Response::HTTP_CREATED);
    }
}
