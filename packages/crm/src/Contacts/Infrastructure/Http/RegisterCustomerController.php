<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Infrastructure\Http\Request\RegisterCustomerRequest;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/register-customer', methods: ['POST'])]
#[IsGranted(ContactsPermission::CREATE_CUSTOMER->value)]
final class RegisterCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] RegisterCustomerRequest $request): JsonResponse
    {
        $customerId = CustomerId::generate()->value();

        $this->commandBus->dispatch(new RegisterCustomerCommand(
            customerId: $customerId,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(['id' => $customerId], Response::HTTP_CREATED);
    }
}
