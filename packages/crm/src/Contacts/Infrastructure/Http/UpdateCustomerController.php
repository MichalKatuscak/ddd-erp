<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerCommand;
use Crm\Contacts\Infrastructure\Http\Request\UpdateCustomerRequest;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/update-customer/{id}', methods: ['PUT'])]
#[IsGranted(ContactsPermission::UPDATE_CUSTOMER->value)]
final class UpdateCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateCustomerCommand(
            customerId: $id,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
