<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\UpdateInquiry\UpdateInquiryCommand;
use Sales\Inquiry\Infrastructure\Http\Request\UpdateInquiryRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}', methods: ['PUT'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class UpdateInquiryController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $id, #[MapRequestPayload] UpdateInquiryRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateInquiryCommand(
            $id, $request->customer_id, $request->customer_name, $request->contact_email,
            $request->description, $request->requested_deadline, $request->required_roles,
        ));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
