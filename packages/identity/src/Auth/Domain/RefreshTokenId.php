<?php
// packages/identity/src/Auth/Domain/RefreshTokenId.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use Symfony\Component\Uid\Uuid;

final class RefreshTokenId
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid RefreshTokenId: '$value'");
        }
        return new self($value);
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
