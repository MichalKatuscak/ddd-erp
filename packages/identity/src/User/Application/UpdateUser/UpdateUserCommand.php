<?php
declare(strict_types=1);

namespace Identity\User\Application\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
