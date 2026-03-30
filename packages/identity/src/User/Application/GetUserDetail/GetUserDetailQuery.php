<?php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

final readonly class GetUserDetailQuery
{
    public function __construct(public string $userId) {}
}
