<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\GetUserList\GetUserListQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users', methods: ['GET'])]
#[IsGranted(IdentityPermission::VIEW_USERS->value)]
final class GetUserListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetUserListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'       => $dto->id,
                'email'    => $dto->email,
                'name'     => $dto->fullName,
                'role_ids' => $dto->roleIds,
                'active'   => $dto->active,
            ],
            $result,
        ));
    }
}
