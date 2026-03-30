<?php
// packages/identity/src/Auth/Infrastructure/Jwt/FirebaseJwtTokenService.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Jwt;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\User\Domain\UserId;

final class FirebaseJwtTokenService implements JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl,
    ) {}

    public function issueAccessToken(UserId $userId, array $permissions): string
    {
        $now = time();
        $payload = [
            'sub'         => $userId->value(),
            'permissions' => $permissions,
            'iat'         => $now,
            'exp'         => $now + $this->ttl,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $payload = (array) $decoded;
            // permissions may decode as array — normalize
            if (isset($payload['permissions'])) {
                $payload['permissions'] = array_values((array) $payload['permissions']);
            }
            return $payload;
        } catch (ExpiredException $e) {
            throw new InvalidTokenException('Token has expired');
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage());
        }
    }

    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }
}
