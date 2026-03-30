<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/assign-roles/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class AssignRolesToUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new AssignRolesToUserCommand(
            userId: $id,
            roleIds: (array) ($data['role_ids'] ?? []),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
