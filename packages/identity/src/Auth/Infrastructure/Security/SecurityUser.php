<?php

declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface
{
    /**
     * @param string[] $roles ROLE_* formatted strings
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly array $roles,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->userId;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }
}
