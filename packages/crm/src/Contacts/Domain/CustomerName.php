<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class CustomerName
{
    private function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
    ) {}

    public static function fromParts(string $firstName, string $lastName): self
    {
        if (trim($firstName) === '') {
            throw new \InvalidArgumentException('First name cannot be empty');
        }
        if (trim($lastName) === '') {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }
        return new self(trim($firstName), trim($lastName));
    }

    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function fullName(): string { return "{$this->firstName} {$this->lastName}"; }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName && $this->lastName === $other->lastName;
    }
}
