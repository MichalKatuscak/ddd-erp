<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\GetUserDetail\GetUserDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/{id}', methods: ['GET'])]
#[IsGranted(IdentityPermission::VIEW_USERS->value)]
final class GetUserDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        /** @var \Identity\User\Application\GetUserDetail\UserDetailDTO $result */
        $result = $this->queryBus->dispatch(new GetUserDetailQuery(userId: $id));

        return new JsonResponse([
            'id'         => $result->id,
            'email'      => $result->email,
            'first_name' => $result->firstName,
            'last_name'  => $result->lastName,
            'role_ids'   => $result->roleIds,
            'active'     => $result->active,
            'created_at' => $result->createdAt,
        ]);
    }
}
