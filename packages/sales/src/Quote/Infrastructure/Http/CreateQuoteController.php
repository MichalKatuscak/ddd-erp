<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\CreateQuote\CreateQuoteCommand;
use Sales\Quote\Domain\QuoteId;
use Sales\Quote\Infrastructure\Http\Request\CreateQuoteRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class CreateQuoteController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, #[MapRequestPayload] CreateQuoteRequest $request): JsonResponse
    {
        $id = QuoteId::generate()->value();
        $this->commandBus->dispatch(new CreateQuoteCommand($id, $inquiryId, $request->valid_until, $request->notes));
        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }
}
