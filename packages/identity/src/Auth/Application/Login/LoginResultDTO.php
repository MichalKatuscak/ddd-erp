<?php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

final readonly class LoginResultDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {}
}
