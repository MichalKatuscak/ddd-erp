<?php

declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Security\PlanningPermission;
use Planning\Worker\Application\GetWorkerDetail\GetWorkerDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/workers/{id}', methods: ['GET'])]
#[IsGranted(PlanningPermission::ManageWorkers->value)]
final class GetWorkerDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}

    public function __invoke(string $id): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetWorkerDetailQuery($id));

        if ($dto === null) {
            return new JsonResponse(['error' => 'Worker not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id'           => $dto->id,
            'name'         => $dto->name,
            'primary_role' => $dto->primaryRole,
            'skills'       => $dto->skills,
            'allocations'  => array_map(fn($a) => [
                'order_id'           => $a->orderId,
                'order_name'         => $a->orderName,
                'phase_id'           => $a->phaseId,
                'phase_name'         => $a->phaseName,
                'allocation_percent' => $a->allocationPercent,
                'start_date'         => $a->startDate,
                'end_date'           => $a->endDate,
            ], $dto->allocations),
        ]);
    }
}
