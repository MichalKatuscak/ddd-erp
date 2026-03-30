<?php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

final readonly class RefreshResultDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {}
}
