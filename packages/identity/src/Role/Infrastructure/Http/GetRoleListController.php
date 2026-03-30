<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\GetRoleList\GetRoleListQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles', methods: ['GET'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class GetRoleListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetRoleListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'          => $dto->id,
                'name'        => $dto->name,
                'permissions' => $dto->permissions,
            ],
            $result,
        ));
    }
}
