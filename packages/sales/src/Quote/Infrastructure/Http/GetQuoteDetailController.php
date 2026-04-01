<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\GetQuoteDetail\GetQuoteDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class GetQuoteDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(string $inquiryId, string $quoteId): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetQuoteDetailQuery($quoteId));
        return new JsonResponse([
            'id'                   => $dto->id,
            'inquiry_id'           => $dto->inquiryId,
            'valid_until'          => $dto->validUntil,
            'status'               => $dto->status,
            'pdf_path'             => $dto->pdfPath,
            'notes'                => $dto->notes,
            'phases'               => array_map(fn($p) => [
                'id' => $p->id, 'name' => $p->name, 'required_role' => $p->requiredRole,
                'duration_days' => $p->durationDays,
                'daily_rate_amount' => $p->dailyRateAmount,
                'daily_rate_currency' => $p->dailyRateCurrency,
                'subtotal_amount' => $p->subtotalAmount,
                'subtotal_currency' => $p->subtotalCurrency,
            ], $dto->phases),
            'total_price_amount'   => $dto->totalPriceAmount,
            'total_price_currency' => $dto->totalPriceCurrency,
        ]);
    }
}
