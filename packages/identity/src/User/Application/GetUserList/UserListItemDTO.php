<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

final readonly class UserListItemDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        /** @var string[] */
        public array $roleIds,
        public bool $active,
    ) {}
}
