<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\CreateInquiry\CreateInquiryCommand;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Inquiry\Infrastructure\Http\Request\CreateInquiryRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class CreateInquiryController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(#[MapRequestPayload] CreateInquiryRequest $request): JsonResponse
    {
        $id = InquiryId::generate()->value();
        $this->commandBus->dispatch(new CreateInquiryCommand(
            inquiryId: $id,
            customerId: $request->customer_id,
            customerName: $request->customer_name,
            contactEmail: $request->contact_email,
            description: $request->description,
            requestedDeadline: $request->requested_deadline,
            requiredRoles: $request->required_roles,
        ));
        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }
}
