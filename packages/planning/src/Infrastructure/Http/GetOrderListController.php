<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\GetOrderList\GetOrderListQuery;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders', methods: ['GET'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class GetOrderListController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetOrderListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'                 => $dto->id,
                'name'               => $dto->name,
                'client_name'        => $dto->clientName,
                'planned_start_date' => $dto->plannedStartDate,
                'status'             => $dto->status,
                'phase_count'        => $dto->phaseCount,
            ],
            $result,
        ));
    }
}
