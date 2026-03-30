<?php
declare(strict_types=1);

namespace Identity\User\Domain;

use SharedKernel\Domain\DomainEvent;

final class UserDeactivated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
    ) {
        parent::__construct();
    }
}
