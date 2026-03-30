<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/refresh-token', methods: ['POST'])]
final class RefreshAccessTokenController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        /** @var \Identity\Auth\Application\RefreshAccessToken\RefreshResultDTO $result */
        $result = $this->queryBus->dispatch(new RefreshAccessTokenQuery(
            refreshToken: (string) ($data['refresh_token'] ?? ''),
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
