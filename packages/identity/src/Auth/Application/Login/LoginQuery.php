<?php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

final readonly class LoginQuery
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
