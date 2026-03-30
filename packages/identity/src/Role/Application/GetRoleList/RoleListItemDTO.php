<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

final readonly class RoleListItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
