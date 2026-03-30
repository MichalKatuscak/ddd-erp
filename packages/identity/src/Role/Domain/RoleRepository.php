<?php
declare(strict_types=1);

namespace Identity\Role\Domain;

interface RoleRepository
{
    /** @throws RoleNotFoundException */
    public function get(RoleId $id): Role;

    public function save(Role $role): void;

    public function nextIdentity(): RoleId;
}
