<?php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

final readonly class GetCurrentUserQuery
{
    public function __construct(public string $userId) {}
}
