<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\GetPhaseSuggestions\GetPhaseSuggestionsQuery;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders/{orderId}/phases/{phaseId}/suggestions', methods: ['GET'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class GetPhaseSuggestionsController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}

    public function __invoke(string $orderId, string $phaseId): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetPhaseSuggestionsQuery($orderId, $phaseId));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'                => $dto->id,
                'name'              => $dto->name,
                'primary_role'      => $dto->primaryRole,
                'skills'            => $dto->skills,
                'available_percent' => $dto->availablePercent,
            ],
            $result,
        ));
    }
}
