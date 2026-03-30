<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\GetCurrentUser\GetCurrentUserQuery;
use Identity\Auth\Infrastructure\Security\SecurityUser;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/me', methods: ['GET'])]
final class GetCurrentUserController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        /** @var \Identity\Auth\Application\GetCurrentUser\CurrentUserDTO $result */
        $result = $this->queryBus->dispatch(new GetCurrentUserQuery(
            userId: $securityUser->getUserIdentifier(),
        ));

        return new JsonResponse([
            'id'          => $result->id,
            'email'       => $result->email,
            'first_name'  => $result->firstName,
            'last_name'   => $result->lastName,
            'permissions' => $result->permissions,
        ]);
    }
}
