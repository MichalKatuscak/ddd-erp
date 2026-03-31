<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\GetOrderDetail\GetOrderDetailQuery;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders/{id}', methods: ['GET'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class GetOrderDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}

    public function __invoke(string $id): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetOrderDetailQuery($id));

        return new JsonResponse([
            'id'                 => $dto->id,
            'name'               => $dto->name,
            'client_name'        => $dto->clientName,
            'planned_start_date' => $dto->plannedStartDate,
            'status'             => $dto->status,
            'phases'             => array_map(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'required_role'   => $p->requiredRole,
                'required_skills' => $p->requiredSkills,
                'headcount'       => $p->headcount,
                'duration_days'   => $p->durationDays,
                'depends_on'      => $p->dependsOn,
                'start_date'      => $p->startDate,
                'end_date'        => $p->endDate,
                'assignments'     => $p->assignments,
            ], $dto->phases),
        ]);
    }
}
