<?php
// packages/identity/tests/Auth/Domain/RefreshTokenTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Domain;

use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenId;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class RefreshTokenTest extends TestCase
{
    public function test_creates_valid_token(): void
    {
        $id     = RefreshTokenId::generate();
        $userId = UserId::generate();
        $hash   = hash('sha256', 'random-token');
        $expiresAt = new \DateTimeImmutable('+30 days');

        $token = new RefreshToken($id, $userId, $hash, $expiresAt);

        $this->assertTrue($token->id()->equals($id));
        $this->assertTrue($token->userId()->equals($userId));
        $this->assertSame($hash, $token->tokenHash());
        $this->assertTrue($token->isValid());
    }

    public function test_revoke_makes_token_invalid(): void
    {
        $token = new RefreshToken(
            RefreshTokenId::generate(),
            UserId::generate(),
            hash('sha256', 'random-token'),
            new \DateTimeImmutable('+30 days'),
        );

        $token->revoke();

        $this->assertFalse($token->isValid());
    }

    public function test_revoke_is_idempotent(): void
    {
        $token = new RefreshToken(
            RefreshTokenId::generate(),
            UserId::generate(),
            hash('sha256', 'random-token'),
            new \DateTimeImmutable('+30 days'),
        );

        $token->revoke();
        $firstRevokedAt = $token->revokedAt();

        $token->revoke(); // second call — no-op

        $this->assertSame($firstRevokedAt, $token->revokedAt());
    }

    public function test_expired_token_is_invalid(): void
    {
        $token = new RefreshToken(
            RefreshTokenId::generate(),
            UserId::generate(),
            hash('sha256', 'random-token'),
            new \DateTimeImmutable('-1 day'), // expired
        );

        $this->assertFalse($token->isValid());
    }
}
