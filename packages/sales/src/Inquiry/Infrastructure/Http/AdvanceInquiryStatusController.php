<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand;
use Sales\Inquiry\Infrastructure\Http\Request\AdvanceInquiryStatusRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}/commands/advance-status', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class AdvanceInquiryStatusController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $id, #[MapRequestPayload] AdvanceInquiryStatusRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new AdvanceInquiryStatusCommand($id, $request->target_status));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
