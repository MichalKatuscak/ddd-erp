<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenRepository;

final class DoctrineRefreshTokenRepository implements RefreshTokenRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findByTokenHash(string $hash): ?RefreshToken
    {
        return $this->entityManager
            ->getRepository(RefreshToken::class)
            ->findOneBy(['tokenHash' => $hash]);
    }

    public function save(RefreshToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }
}
