<?php
declare(strict_types=1);

namespace Identity\User\Application\AssignRolesToUser;

final readonly class AssignRolesToUserCommand
{
    public function __construct(
        public string $userId,
        /** @var string[] UUID strings */
        public array $roleIds,
    ) {}
}
