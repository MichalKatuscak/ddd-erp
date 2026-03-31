<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use Identity\User\Infrastructure\Http\Request\AssignRolesToUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/assign-roles/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class AssignRolesToUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] AssignRolesToUserRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new AssignRolesToUserCommand(
            userId: $id,
            roleIds: $request->role_ids ?? [],
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
