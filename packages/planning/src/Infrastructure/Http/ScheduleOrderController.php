<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\ScheduleOrder\ScheduleOrderCommand;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders/{orderId}/commands/schedule', methods: ['POST'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class ScheduleOrderController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $this->commandBus->dispatch(new ScheduleOrderCommand($orderId));
        return new JsonResponse(null, 204);
    }
}
