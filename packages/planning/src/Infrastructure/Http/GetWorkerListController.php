<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Security\PlanningPermission;
use Planning\Worker\Application\GetWorkerList\GetWorkerListQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/workers', methods: ['GET'])]
#[IsGranted(PlanningPermission::ManageWorkers->value)]
final class GetWorkerListController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetWorkerListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'                         => $dto->id,
                'name'                       => $dto->name,
                'primary_role'               => $dto->primaryRole,
                'skills'                     => $dto->skills,
                'current_allocation_percent' => $dto->currentAllocationPercent,
            ],
            $result,
        ));
    }
}
