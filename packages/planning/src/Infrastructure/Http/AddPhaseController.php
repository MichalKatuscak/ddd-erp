<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Domain\PhaseId;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders/{orderId}/phases', methods: ['POST'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class AddPhaseController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(Request $request, string $orderId): JsonResponse
    {
        $data    = json_decode($request->getContent(), true) ?? [];
        $phaseId = PhaseId::generate()->value();

        $this->commandBus->dispatch(new AddPhaseCommand(
            orderId: $orderId,
            phaseId: $phaseId,
            name: (string) ($data['name'] ?? ''),
            requiredRole: (string) ($data['required_role'] ?? ''),
            requiredSkills: (array) ($data['required_skills'] ?? []),
            headcount: (int) ($data['headcount'] ?? 1),
            durationDays: (int) ($data['duration_days'] ?? 1),
            dependsOn: (array) ($data['depends_on'] ?? []),
        ));

        return new JsonResponse(['id' => $phaseId], Response::HTTP_CREATED);
    }
}
