<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

final readonly class GetRoleDetailQuery
{
    public function __construct(
        public string $roleId,
    ) {}
}
