<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function get(UserId $id): User
    {
        $user = $this->entityManager->find(User::class, $id);
        if ($user === null) {
            throw new UserNotFoundException($id->value());
        }
        return $user;
    }

    public function findByEmail(UserEmail $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
