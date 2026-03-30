<?php
// packages/identity/src/Auth/Domain/RefreshToken.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use Identity\User\Domain\UserId;

final class RefreshToken
{
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(
        private readonly RefreshTokenId $id,
        private readonly UserId $userId,
        private readonly string $tokenHash,
        private readonly \DateTimeImmutable $expiresAt,
    ) {}

    public function revoke(): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return $this->revokedAt === null && $this->expiresAt > new \DateTimeImmutable();
    }

    public function id(): RefreshTokenId { return $this->id; }
    public function userId(): UserId { return $this->userId; }
    public function tokenHash(): string { return $this->tokenHash; }
    public function expiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function revokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
}
