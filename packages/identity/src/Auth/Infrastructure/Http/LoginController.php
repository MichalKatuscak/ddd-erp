<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Infrastructure\Http\Request\LoginRequest;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(#[MapRequestPayload] LoginRequest $request): JsonResponse
    {
        /** @var \Identity\Auth\Application\Login\LoginResultDTO $result */
        $result = $this->queryBus->dispatch(new LoginQuery(
            email: $request->email,
            password: $request->password,
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
