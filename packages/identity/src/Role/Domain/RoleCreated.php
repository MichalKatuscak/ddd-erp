<?php
declare(strict_types=1);

namespace Identity\Role\Domain;

use SharedKernel\Domain\DomainEvent;

final class RoleCreated extends DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly RoleName $name,
        /** @var string[] */
        public readonly array $permissions,
    ) {
        parent::__construct();
    }
}
