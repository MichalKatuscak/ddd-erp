<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Login\LoginQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        /** @var \Identity\Auth\Application\Login\LoginResultDTO $result */
        $result = $this->queryBus->dispatch(new LoginQuery(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
