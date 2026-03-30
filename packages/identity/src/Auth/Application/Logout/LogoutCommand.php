<?php
declare(strict_types=1);

namespace Identity\Auth\Application\Logout;

final readonly class LogoutCommand
{
    public function __construct(public string $refreshToken) {}
}
