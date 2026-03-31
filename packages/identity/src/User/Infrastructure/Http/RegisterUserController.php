<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Domain\UserId;
use Identity\User\Infrastructure\Http\Request\RegisterUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/register-user', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class RegisterUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] RegisterUserRequest $request): JsonResponse
    {
        $userId = UserId::generate()->value();

        $this->commandBus->dispatch(new RegisterUserCommand(
            userId: $userId,
            email: $request->email,
            password: $request->password,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(['id' => $userId], Response::HTTP_CREATED);
    }
}
