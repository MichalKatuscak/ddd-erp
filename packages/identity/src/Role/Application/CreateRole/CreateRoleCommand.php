<?php
declare(strict_types=1);

namespace Identity\Role\Application\CreateRole;

final readonly class CreateRoleCommand
{
    public function __construct(
        public string $roleId,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
