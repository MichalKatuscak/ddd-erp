<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Domain\UserId;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/register-user', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class RegisterUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $userId = UserId::generate()->value();

        $this->commandBus->dispatch(new RegisterUserCommand(
            userId: $userId,
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(['id' => $userId], Response::HTTP_CREATED);
    }
}
