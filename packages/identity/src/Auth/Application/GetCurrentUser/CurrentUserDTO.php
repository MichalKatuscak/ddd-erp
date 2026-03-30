<?php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

final readonly class CurrentUserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        /** @var string[] */
        public array $permissions,
    ) {}
}
