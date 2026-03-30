<?php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

final readonly class GetRoleListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
