<?php
// packages/identity/tests/Auth/Application/LogoutHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\Login\LoginHandler;
use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Application\Logout\LogoutCommand;
use Identity\Auth\Application\Logout\LogoutHandler;
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

final class LogoutHandlerTest extends TestCase
{
    private InMemoryRefreshTokenRepository $refreshTokenRepository;
    private string $refreshToken;

    protected function setUp(): void
    {
        $userRepository               = new InMemoryUserRepository();
        $this->refreshTokenRepository = new InMemoryRefreshTokenRepository();
        $roleRepository               = new \Identity\Tests\Role\Application\InMemoryRoleRepository();
        $jwtService                   = new FirebaseJwtTokenService('test-secret-key-at-least-32-chars-long!!', 900);

        $roleId = RoleId::generate();
        $role = Role::create($roleId, RoleName::fromString('crm-manager'), ['crm.contacts.view_customers']);
        $roleRepository->save($role);

        $userId = UserId::generate();
        $user = User::create($userId, UserEmail::fromString('jan@firma.cz'), UserPassword::fromPlaintext('SecurePass123!'), UserName::fromParts('Jan', 'Novák'));
        $user->assignRoles([$roleId]);
        $userRepository->save($user);

        $loginHandler = new LoginHandler($userRepository, $roleRepository, $this->refreshTokenRepository, $jwtService);
        $loginResult = ($loginHandler)(new LoginQuery('jan@firma.cz', 'SecurePass123!'));
        $this->refreshToken = $loginResult->refreshToken;
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $handler = new LogoutHandler($this->refreshTokenRepository);
        ($handler)(new LogoutCommand($this->refreshToken));

        $tokenHash = hash('sha256', $this->refreshToken);
        $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);
        $this->assertNotNull($token);
        $this->assertFalse($token->isValid());
    }
}
