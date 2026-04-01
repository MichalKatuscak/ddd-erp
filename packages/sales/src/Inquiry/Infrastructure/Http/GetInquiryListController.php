<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\GetInquiryList\GetInquiryListQuery;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetInquiryListController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetInquiryListQuery($request->query->get('status')));
        return new JsonResponse(array_map(fn($i) => [
            'id'                 => $i->id,
            'customer_name'      => $i->customerName,
            'description'        => $i->description,
            'status'             => $i->status,
            'requested_deadline' => $i->requestedDeadline,
            'created_at'         => $i->createdAt,
        ], $items));
    }
}
