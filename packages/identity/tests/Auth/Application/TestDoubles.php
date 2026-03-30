<?php
// packages/identity/tests/Auth/Application/TestDoubles.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;

final class InMemoryRefreshTokenRepository implements RefreshTokenRepository
{
    /** @var RefreshToken[] keyed by tokenHash */
    private array $tokens = [];

    public function findByTokenHash(string $hash): ?RefreshToken
    {
        return $this->tokens[$hash] ?? null;
    }

    public function save(RefreshToken $token): void
    {
        $this->tokens[$token->tokenHash()] = $token;
    }
}

final class InMemoryUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];

    public function get(UserId $id): User
    {
        return $this->users[$id->value()]
            ?? throw new UserNotFoundException($id->value());
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
