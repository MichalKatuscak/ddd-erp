<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\RejectQuote\RejectQuoteCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/commands/reject', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class RejectQuoteController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId): JsonResponse
    {
        $this->commandBus->dispatch(new RejectQuoteCommand($quoteId));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
