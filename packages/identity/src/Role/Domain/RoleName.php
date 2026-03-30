<?php
declare(strict_types=1);

namespace Identity\Role\Domain;

final class RoleName
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $trimmed)) {
            throw new \InvalidArgumentException("Invalid role name slug: '$value'");
        }
        return new self($trimmed);
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
