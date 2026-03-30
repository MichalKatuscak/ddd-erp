<?php

declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JwtTokenService $jwtService,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        try {
            $payload = $this->jwtService->validateAccessToken($token);
        } catch (InvalidTokenException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        $userId = $payload['sub'] ?? '';
        $permissions = $payload['permissions'] ?? [];

        $roles = array_map(
            fn(string $p) => 'ROLE_' . strtoupper(str_replace('.', '_', $p)),
            $permissions,
        );

        return new SelfValidatingPassport(
            new UserBadge($userId, fn(string $id) => new SecurityUser(
                userId: $id,
                email: '',
                roles: array_values($roles),
            )),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
