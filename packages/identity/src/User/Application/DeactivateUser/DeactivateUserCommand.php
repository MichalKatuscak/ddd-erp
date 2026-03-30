<?php
declare(strict_types=1);

namespace Identity\User\Application\DeactivateUser;

final readonly class DeactivateUserCommand
{
    public function __construct(public string $userId) {}
}
