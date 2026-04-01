<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\GetInquiryDetail\GetInquiryDetailQuery;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetInquiryDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(string $id): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetInquiryDetailQuery($id));
        return new JsonResponse([
            'id'                 => $dto->id,
            'customer_id'        => $dto->customerId,
            'customer_name'      => $dto->customerName,
            'contact_email'      => $dto->contactEmail,
            'description'        => $dto->description,
            'requested_deadline' => $dto->requestedDeadline,
            'required_roles'     => $dto->requiredRoles,
            'attachments'        => $dto->attachments,
            'status'             => $dto->status,
            'created_at'         => $dto->createdAt,
        ]);
    }
}
