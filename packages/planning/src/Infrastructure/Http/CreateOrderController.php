<?php
declare(strict_types=1);

namespace Planning\Infrastructure\Http;

use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Domain\OrderId;
use Planning\Security\PlanningPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning/orders', methods: ['POST'])]
#[IsGranted(PlanningPermission::ManageOrders->value)]
final class CreateOrderController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true) ?? [];
        $orderId = OrderId::generate()->value();

        $this->commandBus->dispatch(new CreateOrderCommand(
            orderId: $orderId,
            name: (string) ($data['name'] ?? ''),
            clientName: (string) ($data['client_name'] ?? ''),
            plannedStartDate: (string) ($data['planned_start_date'] ?? ''),
        ));

        return new JsonResponse(['id' => $orderId], Response::HTTP_CREATED);
    }
}
