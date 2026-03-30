<?php
// packages/identity/tests/Auth/Application/LoginHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\Login\LoginHandler;
use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Application\Login\LoginResultDTO;
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

final class LoginHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryRefreshTokenRepository $refreshTokenRepository;
    private \Identity\Tests\Role\Application\InMemoryRoleRepository $roleRepository;
    private FirebaseJwtTokenService $jwtService;
    private LoginHandler $handler;
    private string $userId;
    private string $roleId;

    protected function setUp(): void
    {
        $this->userRepository         = new InMemoryUserRepository();
        $this->refreshTokenRepository = new InMemoryRefreshTokenRepository();
        $this->roleRepository         = new \Identity\Tests\Role\Application\InMemoryRoleRepository();
        $this->jwtService             = new FirebaseJwtTokenService('test-secret-key-at-least-32-chars-long!!', 900);

        $this->roleId = RoleId::generate()->value();
        $role = Role::create(
            RoleId::fromString($this->roleId),
            RoleName::fromString('crm-manager'),
            ['crm.contacts.view_customers', 'crm.contacts.create_customer'],
        );
        $this->roleRepository->save($role);

        $this->userId = UserId::generate()->value();
        $user = User::create(
            UserId::fromString($this->userId),
            UserEmail::fromString('jan@firma.cz'),
            UserPassword::fromPlaintext('SecurePass123!'),
            UserName::fromParts('Jan', 'Novák'),
        );
        $user->assignRoles([RoleId::fromString($this->roleId)]);
        $this->userRepository->save($user);

        $this->handler = new LoginHandler(
            $this->userRepository,
            $this->roleRepository,
            $this->refreshTokenRepository,
            $this->jwtService,
        );
    }

    public function test_login_returns_tokens(): void
    {
        $result = ($this->handler)(new LoginQuery(
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
        ));

        $this->assertInstanceOf(LoginResultDTO::class, $result);
        $this->assertNotEmpty($result->accessToken);
        $this->assertNotEmpty($result->refreshToken);
        $this->assertSame(900, $result->expiresIn);

        $payload = $this->jwtService->validateAccessToken($result->accessToken);
        $this->assertSame($this->userId, $payload['sub']);
        $this->assertContains('crm.contacts.view_customers', $payload['permissions']);
        $this->assertContains('crm.contacts.create_customer', $payload['permissions']);
    }

    public function test_login_throws_on_wrong_email(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid credentials');
        ($this->handler)(new LoginQuery(
            email: 'nonexistent@firma.cz',
            password: 'SecurePass123!',
        ));
    }

    public function test_login_throws_on_wrong_password(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid credentials');
        ($this->handler)(new LoginQuery(
            email: 'jan@firma.cz',
            password: 'WrongPassword123!',
        ));
    }

    public function test_login_throws_when_user_inactive(): void
    {
        $user = $this->userRepository->get(UserId::fromString($this->userId));
        $user->deactivate();
        $this->userRepository->save($user);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('deactivated');
        ($this->handler)(new LoginQuery(
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
        ));
    }
}
