<?php
declare(strict_types=1);

namespace Identity\Role\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Role extends AggregateRoot
{
    private function __construct(
        private readonly RoleId $id,
        private RoleName $name,
        /** @var string[] */
        private array $permissions,
    ) {}

    /** @param string[] $permissions */
    public static function create(RoleId $id, RoleName $name, array $permissions): self
    {
        $role = new self($id, $name, array_values($permissions));
        $role->recordEvent(new RoleCreated($id, $name, $role->permissions));
        return $role;
    }

    /** @param string[] $permissions */
    public function updatePermissions(array $permissions): void
    {
        $this->permissions = array_values($permissions);
        $this->recordEvent(new RolePermissionsUpdated($this->id, $this->permissions));
    }

    public function id(): RoleId { return $this->id; }
    public function name(): RoleName { return $this->name; }
    /** @return string[] */
    public function permissions(): array { return $this->permissions; }
}
