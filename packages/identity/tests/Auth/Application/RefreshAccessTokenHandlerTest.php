<?php
// packages/identity/tests/Auth/Application/RefreshAccessTokenHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\Login\LoginHandler;
use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenHandler;
use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenQuery;
use Identity\Auth\Application\RefreshAccessToken\RefreshResultDTO;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\Auth\Infrastructure\Jwt\FirebaseJwtTokenService;
use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserPassword;
use PHPUnit\Framework\TestCase;

final class RefreshAccessTokenHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryRefreshTokenRepository $refreshTokenRepository;
    private \Identity\Tests\Role\Application\InMemoryRoleRepository $roleRepository;
    private FirebaseJwtTokenService $jwtService;
    private string $refreshToken;

    protected function setUp(): void
    {
        $this->userRepository         = new InMemoryUserRepository();
        $this->refreshTokenRepository = new InMemoryRefreshTokenRepository();
        $this->roleRepository         = new \Identity\Tests\Role\Application\InMemoryRoleRepository();
        $this->jwtService             = new FirebaseJwtTokenService('test-secret-key-at-least-32-chars-long!!', 900);

        $roleId = RoleId::generate();
        $role = Role::create($roleId, RoleName::fromString('crm-manager'), ['crm.contacts.view_customers']);
        $this->roleRepository->save($role);

        $userId = UserId::generate();
        $user = User::create($userId, UserEmail::fromString('jan@firma.cz'), UserPassword::fromPlaintext('SecurePass123!'), UserName::fromParts('Jan', 'Novák'));
        $user->assignRoles([$roleId]);
        $this->userRepository->save($user);

        $loginHandler = new LoginHandler($this->userRepository, $this->roleRepository, $this->refreshTokenRepository, $this->jwtService);
        $loginResult = ($loginHandler)(new LoginQuery('jan@firma.cz', 'SecurePass123!'));
        $this->refreshToken = $loginResult->refreshToken;
    }

    public function test_refresh_returns_new_tokens(): void
    {
        $handler = new RefreshAccessTokenHandler($this->userRepository, $this->roleRepository, $this->refreshTokenRepository, $this->jwtService);
        $result = ($handler)(new RefreshAccessTokenQuery($this->refreshToken));

        $this->assertInstanceOf(RefreshResultDTO::class, $result);
        $this->assertNotEmpty($result->accessToken);
        $this->assertNotEmpty($result->refreshToken);
        $this->assertSame(900, $result->expiresIn);

        // Old refresh token should no longer work (rotation)
        $this->expectException(InvalidTokenException::class);
        ($handler)(new RefreshAccessTokenQuery($this->refreshToken));
    }

    public function test_throws_on_invalid_refresh_token(): void
    {
        $handler = new RefreshAccessTokenHandler($this->userRepository, $this->roleRepository, $this->refreshTokenRepository, $this->jwtService);

        $this->expectException(InvalidTokenException::class);
        ($handler)(new RefreshAccessTokenQuery('nonexistent-token'));
    }
}
