<?php
declare(strict_types=1);

namespace Identity\User\Domain;

interface UserRepository
{
    /** @throws UserNotFoundException */
    public function get(UserId $id): User;

    public function findByEmail(UserEmail $email): ?User;

    public function save(User $user): void;

    public function nextIdentity(): UserId;
}
