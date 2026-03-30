<?php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenId;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class RefreshAccessTokenHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JwtTokenService $jwtService,
    ) {}

    public function __invoke(RefreshAccessTokenQuery $query): RefreshResultDTO
    {
        $tokenHash = hash('sha256', $query->refreshToken);
        $existingToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($existingToken === null || !$existingToken->isValid()) {
            throw new InvalidTokenException('Invalid or expired refresh token');
        }

        $existingToken->revoke();
        $this->refreshTokenRepository->save($existingToken);

        $user = $this->userRepository->get($existingToken->userId());

        $permissions = [];
        foreach ($user->roleIds() as $roleId) {
            try {
                $role = $this->roleRepository->get($roleId);
                $permissions = array_merge($permissions, $role->permissions());
            } catch (\DomainException) {
                // Skip deleted roles
            }
        }
        $permissions = array_values(array_unique($permissions));

        $accessToken = $this->jwtService->issueAccessToken($user->id(), $permissions);

        $newRefreshPlaintext = $this->jwtService->generateRefreshToken();
        $newRefreshHash = hash('sha256', $newRefreshPlaintext);
        $newRefreshToken = new RefreshToken(
            RefreshTokenId::generate(),
            $user->id(),
            $newRefreshHash,
            new \DateTimeImmutable('+30 days'),
        );
        $this->refreshTokenRepository->save($newRefreshToken);

        return new RefreshResultDTO(
            accessToken: $accessToken,
            refreshToken: $newRefreshPlaintext,
            expiresIn: 900,
        );
    }
}
