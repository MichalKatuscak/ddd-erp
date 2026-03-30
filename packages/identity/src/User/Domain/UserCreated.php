<?php
declare(strict_types=1);

namespace Identity\User\Domain;

use SharedKernel\Domain\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly UserEmail $email,
        public readonly UserName $name,
    ) {
        parent::__construct();
    }
}
