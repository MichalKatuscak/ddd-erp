<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\AssignWorker\AssignWorkerCommand;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders/{orderId}/phases/{phaseId}/assignments', methods: ['POST'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class AssignWorkerController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(Request $request, string $orderId, string $phaseId): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new AssignWorkerCommand(
            orderId: $orderId,
            phaseId: $phaseId,
            workerId: (string) ($data['worker_id'] ?? ''),
            allocationPercent: (int) ($data['allocation_percent'] ?? 100),
        ));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
