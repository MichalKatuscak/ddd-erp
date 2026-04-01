<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\AddQuotePhase\AddQuotePhaseCommand;
use Sales\Quote\Domain\QuotePhaseId;
use Sales\Quote\Infrastructure\Http\Request\AddQuotePhaseRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/phases', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class AddQuotePhaseController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId, #[MapRequestPayload] AddQuotePhaseRequest $req): JsonResponse
    {
        $pid = QuotePhaseId::generate()->value();
        $this->commandBus->dispatch(new AddQuotePhaseCommand($quoteId, $pid, $req->name, $req->required_role, $req->duration_days, $req->daily_rate_amount, $req->daily_rate_currency));
        return new JsonResponse(['id' => $pid], Response::HTTP_CREATED);
    }
}
