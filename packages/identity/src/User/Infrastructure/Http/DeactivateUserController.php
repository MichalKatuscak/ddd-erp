<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\DeactivateUser\DeactivateUserCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/deactivate-user/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class DeactivateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeactivateUserCommand(userId: $id));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
