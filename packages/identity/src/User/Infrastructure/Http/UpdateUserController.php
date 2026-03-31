<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\UpdateUser\UpdateUserCommand;
use Identity\User\Infrastructure\Http\Request\UpdateUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/update-user/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] UpdateUserRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateUserCommand(
            userId: $id,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
