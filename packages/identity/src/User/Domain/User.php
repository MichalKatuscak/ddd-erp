<?php
declare(strict_types=1);

namespace Identity\User\Domain;

use Identity\Role\Domain\RoleId;
use SharedKernel\Domain\AggregateRoot;

final class User extends AggregateRoot
{
    /** @var string[] internally stored as raw UUID strings */
    private array $roleIds = [];

    private function __construct(
        private readonly UserId $id,
        private UserEmail $email,
        private UserPassword $password,
        private UserName $name,
        private bool $active,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        UserId $id,
        UserEmail $email,
        UserPassword $password,
        UserName $name,
    ): self {
        $user = new self($id, $email, $password, $name, true, new \DateTimeImmutable());
        $user->recordEvent(new UserCreated($id, $email, $name));
        return $user;
    }

    public function update(UserEmail $email, UserName $name): void
    {
        $this->email = $email;
        $this->name  = $name;
        $this->recordEvent(new UserUpdated($this->id, $email, $name));
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->recordEvent(new UserDeactivated($this->id));
    }

    /** @param RoleId[] $roleIds */
    public function assignRoles(array $roleIds): void
    {
        $this->roleIds = array_map(fn(RoleId $id) => $id->value(), $roleIds);
        $this->recordEvent(new RoleAssignedToUser($this->id, $roleIds));
    }

    public function id(): UserId { return $this->id; }
    public function email(): UserEmail { return $this->email; }
    public function password(): UserPassword { return $this->password; }
    public function name(): UserName { return $this->name; }
    public function isActive(): bool { return $this->active; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return RoleId[] */
    public function roleIds(): array
    {
        return array_map(fn(string $id) => RoleId::fromString($id), $this->roleIds);
    }
}
