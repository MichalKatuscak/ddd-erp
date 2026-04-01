<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\UpdateQuotePhase\UpdateQuotePhaseCommand;
use Sales\Quote\Infrastructure\Http\Request\UpdateQuotePhaseRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/phases/{phaseId}', methods: ['PUT'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class UpdateQuotePhaseController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId, string $phaseId, #[MapRequestPayload] UpdateQuotePhaseRequest $req): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateQuotePhaseCommand($quoteId, $phaseId, $req->name, $req->required_role, $req->duration_days, $req->daily_rate_amount, $req->daily_rate_currency));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
