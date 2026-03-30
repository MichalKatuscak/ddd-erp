<?php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

final readonly class RefreshAccessTokenQuery
{
    public function __construct(public string $refreshToken) {}
}
