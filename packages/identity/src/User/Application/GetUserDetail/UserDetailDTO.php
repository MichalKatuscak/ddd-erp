<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

final readonly class UserDetailDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        /** @var string[] */
        public array $roleIds,
        public bool $active,
        public string $createdAt,
    ) {}
}
