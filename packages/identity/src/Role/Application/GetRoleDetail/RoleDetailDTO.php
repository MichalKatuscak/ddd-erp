<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

final readonly class RoleDetailDTO
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
