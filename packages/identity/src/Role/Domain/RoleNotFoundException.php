<?php
declare(strict_types=1);

namespace Identity\Role\Domain;

final class RoleNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Role not found: '$id'");
    }
}
