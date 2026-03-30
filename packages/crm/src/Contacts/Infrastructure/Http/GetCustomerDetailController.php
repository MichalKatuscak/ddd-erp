<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailQuery;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/customers/{id}', methods: ['GET'])]
#[IsGranted(ContactsPermission::VIEW_CUSTOMERS->value)]
final class GetCustomerDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $detail = $this->queryBus->dispatch(new GetCustomerDetailQuery($id));

        return new JsonResponse([
            'id'            => $detail->id,
            'email'         => $detail->email,
            'first_name'    => $detail->firstName,
            'last_name'     => $detail->lastName,
            'registered_at' => $detail->registeredAt,
        ]);
    }
}
