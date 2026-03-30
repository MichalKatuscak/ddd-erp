<?php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenId;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class LoginHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JwtTokenService $jwtService,
    ) {}

    public function __invoke(LoginQuery $query): LoginResultDTO
    {
        $user = $this->userRepository->findByEmail(UserEmail::fromString($query->email));

        if ($user === null || !$user->password()->verify($query->password)) {
            throw new \DomainException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new \DomainException('User account is deactivated');
        }

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

        $refreshTokenPlaintext = $this->jwtService->generateRefreshToken();
        $refreshTokenHash = hash('sha256', $refreshTokenPlaintext);
        $refreshToken = new RefreshToken(
            RefreshTokenId::generate(),
            $user->id(),
            $refreshTokenHash,
            new \DateTimeImmutable('+30 days'),
        );
        $this->refreshTokenRepository->save($refreshToken);

        return new LoginResultDTO(
            accessToken: $accessToken,
            refreshToken: $refreshTokenPlaintext,
            expiresIn: 900,
        );
    }
}
