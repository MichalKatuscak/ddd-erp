<?php
// packages/identity/src/Auth/Application/JwtTokenService.php
declare(strict_types=1);

namespace Identity\Auth\Application;

use Identity\Auth\Domain\InvalidTokenException;
use Identity\User\Domain\UserId;

interface JwtTokenService
{
    /**
     * @param string[] $permissions
     * @return string JWT token
     */
    public function issueAccessToken(UserId $userId, array $permissions): string;

    /**
     * @return array{sub: string, permissions: string[], iat: int, exp: int}
     * @throws InvalidTokenException
     */
    public function validateAccessToken(string $token): array;

    /**
     * @return string Random 64-byte hex string (plaintext — DB stores SHA-256 hash)
     */
    public function generateRefreshToken(): string;
}
