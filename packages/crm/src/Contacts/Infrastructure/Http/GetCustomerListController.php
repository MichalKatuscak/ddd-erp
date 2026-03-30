<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\GetCustomerList\GetCustomerListQuery;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/customers', methods: ['GET'])]
#[IsGranted(ContactsPermission::VIEW_CUSTOMERS->value)]
final class GetCustomerListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetCustomerListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($item) => [
                'id'            => $item->id,
                'email'         => $item->email,
                'full_name'     => $item->fullName,
                'registered_at' => $item->registeredAt,
            ],
            $items,
        ));
    }
}
