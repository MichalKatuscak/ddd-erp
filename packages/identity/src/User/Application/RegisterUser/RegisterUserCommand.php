<?php
declare(strict_types=1);

namespace Identity\User\Application\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
    ) {}
}
