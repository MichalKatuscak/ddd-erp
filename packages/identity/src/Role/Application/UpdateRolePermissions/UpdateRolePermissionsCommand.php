<?php
declare(strict_types=1);

namespace Identity\Role\Application\UpdateRolePermissions;

final readonly class UpdateRolePermissionsCommand
{
    public function __construct(
        public string $roleId,
        /** @var string[] */
        public array $permissions,
    ) {}
}
