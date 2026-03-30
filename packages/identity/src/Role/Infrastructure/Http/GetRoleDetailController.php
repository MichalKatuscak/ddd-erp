<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\GetRoleDetail\GetRoleDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/{id}', methods: ['GET'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class GetRoleDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        /** @var \Identity\Role\Application\GetRoleDetail\RoleDetailDTO $result */
        $result = $this->queryBus->dispatch(new GetRoleDetailQuery(roleId: $id));

        return new JsonResponse([
            'id'          => $result->id,
            'name'        => $result->name,
            'permissions' => $result->permissions,
        ]);
    }
}
