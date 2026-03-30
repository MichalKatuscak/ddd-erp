<?php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserEmail
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: '$value'");
        }
        return new self($normalized);
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
