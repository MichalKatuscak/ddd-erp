# Identity & Access BC — DDD + Symfony Design Spec

**Datum:** 2026-03-30
**Status:** Schváleno
**Navazuje na:** CRM Contacts MVP (`2026-03-30-erp-ddd-design.md`)

> **DŮLEŽITÉ:** Implementace se striktně řídí pravidly příručky [DDD v Symfony](https://ddd-v-symfony.katuscak.cz/).

---

## 1. Přehled

Identity & Access je samostatný Bounded Context implementovaný jako Composer package `packages/identity`. Odpovídá za autentizaci (JWT), správu uživatelů a správu rolí s oprávněními.

Nahrazuje dočasné in-memory `security.yaml` řešení z CRM MVP. Po implementaci budou všechny ostatní BC (CRM, Projekty, …) ověřovat požadavky přes Bearer token.

---

## 2. Fyzická struktura

```
packages/identity/
├── composer.json
└── src/
    ├── User/
    │   ├── Domain/
    │   │   ├── User.php                    # Aggregate Root
    │   │   ├── UserId.php                  # Value Object
    │   │   ├── UserEmail.php               # Value Object
    │   │   ├── UserPassword.php            # Value Object (bcrypt hash)
    │   │   ├── UserName.php                # Value Object (firstName, lastName)
    │   │   ├── UserCreated.php             # Domain Event
    │   │   ├── UserUpdated.php             # Domain Event
    │   │   ├── UserDeactivated.php         # Domain Event
    │   │   ├── RoleAssignedToUser.php      # Domain Event
    │   │   ├── UserRepository.php          # Interface (Port)
    │   │   └── UserNotFoundException.php
    │   ├── Application/
    │   │   ├── RegisterUser/
    │   │   │   ├── RegisterUserCommand.php
    │   │   │   └── RegisterUserHandler.php
    │   │   ├── UpdateUser/
    │   │   │   ├── UpdateUserCommand.php
    │   │   │   └── UpdateUserHandler.php
    │   │   ├── DeactivateUser/
    │   │   │   ├── DeactivateUserCommand.php
    │   │   │   └── DeactivateUserHandler.php
    │   │   ├── AssignRolesToUser/
    │   │   │   ├── AssignRolesToUserCommand.php
    │   │   │   └── AssignRolesToUserHandler.php
    │   │   ├── GetUserList/
    │   │   │   ├── GetUserListQuery.php
    │   │   │   ├── GetUserListHandler.php
    │   │   │   └── UserListItemDTO.php
    │   │   └── GetUserDetail/
    │   │       ├── GetUserDetailQuery.php
    │   │       ├── GetUserDetailHandler.php
    │   │       └── UserDetailDTO.php
    │   └── Infrastructure/
    │       ├── Persistence/
    │       │   └── DoctrineUserRepository.php
    │       ├── Doctrine/
    │       │   ├── User.orm.xml
    │       │   ├── UserName.orm.xml         # Embeddable
    │       │   └── Type/
    │       │       ├── UserIdType.php
    │       │       └── UserEmailType.php
    │       └── Http/
    │           ├── GetUserListController.php
    │           └── GetUserDetailController.php
    ├── Role/
    │   ├── Domain/
    │   │   ├── Role.php                    # Aggregate Root
    │   │   ├── RoleId.php                  # Value Object
    │   │   ├── RoleName.php                # Value Object (unikátní slug)
    │   │   ├── RoleCreated.php             # Domain Event
    │   │   ├── RolePermissionsUpdated.php  # Domain Event
    │   │   ├── RoleRepository.php          # Interface
    │   │   └── RoleNotFoundException.php
    │   ├── Application/
    │   │   ├── CreateRole/
    │   │   │   ├── CreateRoleCommand.php
    │   │   │   └── CreateRoleHandler.php
    │   │   ├── UpdateRolePermissions/
    │   │   │   ├── UpdateRolePermissionsCommand.php
    │   │   │   └── UpdateRolePermissionsHandler.php
    │   │   ├── GetRoleList/
    │   │   │   ├── GetRoleListQuery.php
    │   │   │   ├── GetRoleListHandler.php
    │   │   │   └── RoleListItemDTO.php
    │   │   └── GetRoleDetail/
    │   │       ├── GetRoleDetailQuery.php
    │   │       ├── GetRoleDetailHandler.php
    │   │       └── RoleDetailDTO.php
    │   └── Infrastructure/
    │       ├── Persistence/
    │       │   └── DoctrineRoleRepository.php
    │       ├── Doctrine/
    │       │   ├── Role.orm.xml
    │       │   └── Type/
    │       │       └── RoleIdType.php
    │       └── Http/
    │           ├── CreateRoleController.php
    │           ├── UpdateRolePermissionsController.php
    │           ├── GetRoleListController.php
    │           └── GetRoleDetailController.php
    └── Auth/
        ├── Domain/
        │   ├── RefreshToken.php            # Entita (ne Aggregate Root)
        │   ├── RefreshTokenId.php
        │   └── RefreshTokenRepository.php
        ├── Application/
        │   ├── Login/
        │   │   ├── LoginCommand.php
        │   │   └── LoginHandler.php        # vydá access + refresh token
        │   ├── RefreshAccessToken/
        │   │   ├── RefreshAccessTokenCommand.php
        │   │   └── RefreshAccessTokenHandler.php
        │   ├── Logout/
        │   │   ├── LogoutCommand.php
        │   │   └── LogoutHandler.php       # revokuje refresh token
        │   └── GetCurrentUser/
        │       ├── GetCurrentUserQuery.php
        │       ├── GetCurrentUserHandler.php
        │       └── CurrentUserDTO.php
        └── Infrastructure/
            ├── Jwt/
            │   └── JwtTokenService.php     # wraps firebase/php-jwt
            ├── Persistence/
            │   └── DoctrineRefreshTokenRepository.php
            ├── Doctrine/
            │   └── RefreshToken.orm.xml
            ├── Security/
            │   ├── IdentityUserProvider.php   # UserProviderInterface
            │   └── JwtAuthenticator.php       # AuthenticatorInterface
            └── Http/
                ├── LoginController.php
                ├── RefreshAccessTokenController.php
                └── LogoutController.php
```

---

## 3. Doménový model

### 3.1 User Aggregate

```php
final class User extends AggregateRoot
{
    private function __construct(
        private readonly UserId    $id,
        private UserEmail          $email,
        private UserPassword       $password,   // bcrypt hash
        private UserName           $name,
        private array              $roleIds,    // RoleId[]
        private bool               $active,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(UserId $id, UserEmail $email, UserPassword $password, UserName $name): self
    public function update(UserEmail $email, UserName $name): void
    public function deactivate(): void
    public function assignRoles(array $roleIds): void   // nahradí celý seznam
}
```

`UserPassword` validuje minimální délku a ukládá pouze hash (nikdy plaintext). Hash se provede v konstruktoru přes `password_hash()`.

### 3.2 Role Aggregate

```php
final class Role extends AggregateRoot
{
    private function __construct(
        private readonly RoleId $id,
        private RoleName        $name,       // unikátní slug, např. "crm-manager"
        private array           $permissions, // string[] — hodnoty z *Permission enumů
    ) {}

    public static function create(RoleId $id, RoleName $name, array $permissions): self
    public function updatePermissions(array $permissions): void
}
```

### 3.3 RefreshToken

Jednoduchá entita, ne Aggregate Root — nemá vlastní byznys logiku, pouze persistuje stav tokenu.

```php
final class RefreshToken
{
    public function __construct(
        private readonly RefreshTokenId      $id,
        private readonly UserId              $userId,
        private readonly string              $tokenHash,  // SHA-256 hash tokenu
        private readonly \DateTimeImmutable  $expiresAt,
        private ?\DateTimeImmutable          $revokedAt = null,
    ) {}

    public function revoke(): void
    public function isValid(): bool  // !revoked && expiresAt > now
}
```

---

## 4. JWT

### 4.1 JwtTokenService

```php
final class JwtTokenService
{
    // Konfigurace z .env: JWT_SECRET, JWT_TTL (defaultně 900 = 15 min)

    public function issueAccessToken(UserId $userId, array $permissions): string
    // Payload: {sub: userId, permissions: [...], iat, exp}

    public function validateAccessToken(string $token): array
    // Vrátí payload nebo vyhodí InvalidTokenException

    public function generateRefreshToken(): string
    // Náhodný 64-byte hex string (plaintext — DB uloží SHA-256 hash)
}
```

Závislost: `firebase/php-jwt` (MIT licence, žádný Symfony bundle).

### 4.2 Token flow

```
POST /api/identity/commands/login
  → LoginHandler
      → ověří email + password
      → JwtTokenService::issueAccessToken()  → access_token (JWT, 15 min)
      → JwtTokenService::generateRefreshToken() → refresh_token (hex string)
      → uloží SHA-256(refresh_token) do DB s expiresAt = now + 30 dní
      → vrátí {access_token, refresh_token, expires_in: 900}

POST /api/identity/commands/refresh-token
  → RefreshAccessTokenHandler
      → načte RefreshToken z DB podle SHA-256(token)
      → ověří isValid()
      → vydá nový access_token
      → rotace refresh tokenu: revokuje starý, vydá nový (vždy povinné)

POST /api/identity/commands/logout
  → LogoutHandler
      → revokuje RefreshToken v DB
```

---

## 5. Symfony Security integrace

### 5.1 IdentityUserProvider

Implementuje `UserProviderInterface`. Načítá uživatele z DB podle `user_id` z JWT payloadu. Vrací `SecurityUser` (lightweight DTO implementující `UserInterface`) s permissions jako roles.

### 5.2 JwtAuthenticator

Implementuje `AuthenticatorInterface`:
1. Extrahuje `Authorization: Bearer <token>` hlavičku
2. Validuje JWT přes `JwtTokenService`
3. Načte uživatele přes `IdentityUserProvider`
4. Naplní Security context

### 5.3 security.yaml po migraci

```yaml
security:
    providers:
        identity:
            id: Identity\Auth\Infrastructure\Security\IdentityUserProvider

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern: ^/api/identity/commands/login
            security: false
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Identity\Auth\Infrastructure\Security\JwtAuthenticator

    access_control:
        - { path: ^/api/identity/commands/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/identity/commands/refresh-token, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

`ContactsVoter` zůstane beze změny — permissions jsou stejné stringy, jen se teď berou z JWT místo z in-memory rolí.

---

## 6. HTTP API

### Auth
```
POST /api/identity/commands/login              → {access_token, refresh_token, expires_in}
POST /api/identity/commands/refresh-token      → {access_token, expires_in}
POST /api/identity/commands/logout             → 204
GET  /api/identity/me                          → {id, email, name, permissions[]}
```

### Users (vyžaduje oprávnění `identity.users.*`)
```
POST /api/identity/users/commands/register-user        → 201 {id}
PUT  /api/identity/users/commands/update-user/{id}     → 204
POST /api/identity/users/commands/deactivate-user/{id} → 204
POST /api/identity/users/commands/assign-roles/{id}    → 204
GET  /api/identity/users                               → [{id, email, name, roles[], active}]
GET  /api/identity/users/{id}
```

### Roles (vyžaduje oprávnění `identity.roles.*`)
```
POST /api/identity/roles/commands/create-role               → 201 {id}
PUT  /api/identity/roles/commands/update-role-permissions/{id} → 204
GET  /api/identity/roles                                    → [{id, name, permissions[]}]
GET  /api/identity/roles/{id}
```

---

## 7. Oprávnění

```php
enum IdentityPermission: string
{
    case MANAGE_USERS = 'identity.users.manage';
    case MANAGE_ROLES = 'identity.roles.manage';
    case VIEW_USERS   = 'identity.users.view';
}
```

Seed data (migration): uživatel `admin@erp.local` / `changeme` s rolí `super-admin` obsahující všechna dostupná oprávnění ze všech BC.

---

## 8. Databázové tabulky

```sql
identity_users         (id, email, password_hash, first_name, last_name, active, created_at)
identity_user_roles    (user_id, role_id)            -- join tabulka
identity_roles         (id, name, permissions)        -- permissions jako JSON array
identity_refresh_tokens (id, user_id, token_hash, expires_at, revoked_at)
```

`permissions` sloupec je PostgreSQL `jsonb` — jednoduché, bez potřeby extra tabulky pro permission-role mapping.

---

## 9. Testování

**Unit testy** (`packages/identity/tests/`):
- `UserTest` — create, update, deactivate, assignRoles + domain events
- `RoleTest` — create, updatePermissions
- `UserPasswordTest` — hash se liší od plaintextu, verify funguje
- `JwtTokenServiceTest` — issue + validate, expired token vyhodí výjimku

**Integrační testy** (handlers + DB):
- `LoginHandlerTest` — správné credentials → token; špatné → 401
- `RegisterUserHandlerTest` — uživatel persistován, email unikátní

**E2E testy** (HTTP):
- Login → dostanu JWT → zavolám `/api/crm/contacts/customers` s Bearer → 200
- Expirovaný token → 401
- Chybějící token → 401

---

## 10. Composer package

```json
{
    "name": "ddd-erp/identity",
    "require": {
        "php": "^8.4",
        "ddd-erp/shared-kernel": "*",
        "doctrine/orm": "^3.0",
        "doctrine/doctrine-bundle": "^2.0",
        "symfony/security-bundle": "^7.0 || ^8.0",
        "symfony/uid": "^7.0 || ^8.0",
        "firebase/php-jwt": "^6.0"
    }
}
```
