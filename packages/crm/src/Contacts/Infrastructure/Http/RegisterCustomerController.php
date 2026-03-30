<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/register-customer', methods: ['POST'])]
#[IsGranted(ContactsPermission::CREATE_CUSTOMER->value)]
final class RegisterCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $customerId = CustomerId::generate()->value();

        $this->commandBus->dispatch(new RegisterCustomerCommand(
            customerId: $customerId,
            email: (string) ($data['email'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(['id' => $customerId], Response::HTTP_CREATED);
    }
}
