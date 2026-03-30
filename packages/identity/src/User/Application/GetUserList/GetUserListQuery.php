<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

final readonly class GetUserListQuery
{
    public function __construct(public int $limit = 50, public int $offset = 0) {}
}
