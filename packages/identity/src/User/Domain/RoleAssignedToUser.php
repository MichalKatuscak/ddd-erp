<?php
declare(strict_types=1);

namespace Identity\User\Domain;

use Identity\Role\Domain\RoleId;
use SharedKernel\Domain\DomainEvent;

final class RoleAssignedToUser extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        /** @var RoleId[] */
        public readonly array $roleIds,
    ) {
        parent::__construct();
    }
}
