<?php
// packages/identity/tests/Auth/Application/JwtTokenServiceTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\Auth\Infrastructure\Jwt\FirebaseJwtTokenService;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class JwtTokenServiceTest extends TestCase
{
    private JwtTokenService $service;

    protected function setUp(): void
    {
        $this->service = new FirebaseJwtTokenService(
            secret: 'test-secret-key-at-least-32-chars-long!!',
            ttl: 900,
        );
    }

    public function test_issues_and_validates_access_token(): void
    {
        $userId = UserId::generate();
        $permissions = ['crm.contacts.view_customers', 'identity.users.manage'];

        $token = $this->service->issueAccessToken($userId, $permissions);

        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));

        $payload = $this->service->validateAccessToken($token);

        $this->assertSame($userId->value(), $payload['sub']);
        $this->assertSame($permissions, $payload['permissions']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_validates_token_contains_correct_expiry(): void
    {
        $userId = UserId::generate();
        $token = $this->service->issueAccessToken($userId, []);

        $payload = $this->service->validateAccessToken($token);

        $expectedExpiry = $payload['iat'] + 900;
        $this->assertSame($expectedExpiry, $payload['exp']);
    }

    public function test_throws_on_invalid_token(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->service->validateAccessToken('invalid.token.here');
    }

    public function test_throws_on_expired_token(): void
    {
        $service = new FirebaseJwtTokenService(
            secret: 'test-secret-key-at-least-32-chars-long!!',
            ttl: -1,
        );

        $token = $service->issueAccessToken(UserId::generate(), []);

        $this->expectException(InvalidTokenException::class);
        $this->service->validateAccessToken($token);
    }

    public function test_throws_on_wrong_secret(): void
    {
        $token = $this->service->issueAccessToken(UserId::generate(), []);

        $otherService = new FirebaseJwtTokenService(
            secret: 'different-secret-key-at-least-32-chars!!',
            ttl: 900,
        );

        $this->expectException(InvalidTokenException::class);
        $otherService->validateAccessToken($token);
    }

    public function test_generates_refresh_token(): void
    {
        $token1 = $this->service->generateRefreshToken();
        $token2 = $this->service->generateRefreshToken();

        $this->assertNotSame($token1, $token2);
        $this->assertSame(128, strlen($token1));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token1);
    }
}
