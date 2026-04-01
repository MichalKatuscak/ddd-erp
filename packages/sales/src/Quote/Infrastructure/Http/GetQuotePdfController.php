<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\GetQuoteDetail\{GetQuoteDetailHandler, GetQuoteDetailQuery};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/pdf', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class GetQuotePdfController extends AbstractController
{
    public function __construct(private readonly GetQuoteDetailHandler $handler) {}
    public function __invoke(string $inquiryId, string $quoteId): Response
    {
        $dto = ($this->handler)(new GetQuoteDetailQuery($quoteId));
        if ($dto->pdfPath === null || !file_exists($dto->pdfPath)) {
            return new Response(json_encode(['error' => 'PDF not generated yet. Call export-pdf first.']), 404, ['Content-Type' => 'application/json']);
        }
        return new BinaryFileResponse($dto->pdfPath);
    }
}
