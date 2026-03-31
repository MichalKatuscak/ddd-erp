<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Logout\LogoutCommand;
use Identity\Auth\Infrastructure\Http\Request\LogoutRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/logout', methods: ['POST'])]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] LogoutRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new LogoutCommand(
            refreshToken: $request->refresh_token,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
