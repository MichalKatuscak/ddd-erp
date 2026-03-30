# Identity & Access BC — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementovat Identity & Access Bounded Context — JWT autentizaci, správu uživatelů a rolí s oprávněními. Nahradit dočasné in-memory `security.yaml` řešení z CRM MVP. Po implementaci budou všechny BC ověřovat požadavky přes Bearer token.

**Architecture:** Composer package `packages/identity/` se třemi sub-namespaces: `User\` (aggregate + CRUD), `Role\` (aggregate + permissions), `Auth\` (JWT login/refresh/logout + Symfony Security). Dodržuje stejné DDD/CQRS vzory jako CRM Contacts BC.

**Tech Stack:** PHP 8.4, Symfony 7.x, Doctrine ORM 3.x (XML mapping), Symfony Messenger (3 busy), PostgreSQL 16, PHPUnit 11, firebase/php-jwt, symfony/uid

**Referenční příručka:** https://ddd-v-symfony.katuscak.cz/ — všechna architektonická rozhodnutí se řídí touto příručkou.

---

## Přehled souborů

```
packages/identity/
├── composer.json
├── phpunit.xml
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
    │           ├── RegisterUserController.php
    │           ├── UpdateUserController.php
    │           ├── DeactivateUserController.php
    │           ├── AssignRolesToUserController.php
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
        │   ├── RefreshTokenId.php          # Value Object
        │   ├── RefreshTokenRepository.php  # Interface
        │   └── InvalidTokenException.php
        ├── Application/
        │   ├── Login/
        │   │   ├── LoginQuery.php
        │   │   ├── LoginHandler.php
        │   │   └── LoginResultDTO.php
        │   ├── RefreshAccessToken/
        │   │   ├── RefreshAccessTokenQuery.php
        │   │   ├── RefreshAccessTokenHandler.php
        │   │   └── RefreshResultDTO.php
        │   ├── Logout/
        │   │   ├── LogoutCommand.php
        │   │   └── LogoutHandler.php
        │   ├── GetCurrentUser/
        │   │   ├── GetCurrentUserQuery.php
        │   │   ├── GetCurrentUserHandler.php
        │   │   └── CurrentUserDTO.php
        │   └── JwtTokenService.php         # Interface (Port)
        └── Infrastructure/
            ├── Jwt/
            │   └── FirebaseJwtTokenService.php
            ├── Persistence/
            │   └── DoctrineRefreshTokenRepository.php
            ├── Doctrine/
            │   ├── RefreshToken.orm.xml
            │   └── Type/
            │       └── RefreshTokenIdType.php
            ├── Security/
            │   ├── SecurityUser.php
            │   ├── IdentityUserProvider.php
            │   └── JwtAuthenticator.php
            └── Http/
                ├── LoginController.php
                ├── RefreshAccessTokenController.php
                ├── LogoutController.php
                └── GetCurrentUserController.php

config/
├── packages/
│   ├── doctrine.yaml          # Modify: add identity mappings + types
│   └── security.yaml          # Rewrite: JWT authenticator
├── routes/
│   └── identity.yaml          # Create
└── services.yaml              # Modify: add identity services

tests/
└── Identity/
    └── E2e/
        └── AuthFlowTest.php   # E2E: login → JWT → CRM endpoint
```

---

## Task 1: Identity Composer package setup

**Files:**
- Create: `packages/identity/composer.json`
- Create: `packages/identity/phpunit.xml`
- Modify: `composer.json` (root — add path repository + require)

- [ ] **Step 1: Vytvoř packages/identity/composer.json**

```json
{
    "name": "ddd-erp/identity",
    "type": "library",
    "description": "Identity & Access Bounded Context — Users, Roles, JWT Auth",
    "require": {
        "php": "^8.4",
        "ddd-erp/shared-kernel": "*",
        "doctrine/orm": "^3.0",
        "doctrine/doctrine-bundle": "^2.0",
        "symfony/security-bundle": "^7.0",
        "symfony/uid": "^7.0",
        "firebase/php-jwt": "^6.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Identity\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Identity\\Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 2: Vytvoř packages/identity/phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Identity">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 3: Přidej path repository a require do root composer.json**

Edituj `composer.json` — přidej do sekce `repositories` a `require`:

```json
{
    "repositories": [
        {"type": "path", "url": "./packages/shared-kernel", "options": {"symlink": true}},
        {"type": "path", "url": "./packages/crm", "options": {"symlink": true}},
        {"type": "path", "url": "./packages/identity", "options": {"symlink": true}}
    ],
    "require": {
        "ddd-erp/identity": "*",
        "firebase/php-jwt": "^6.0"
    }
}
```

Pozn.: `firebase/php-jwt` musí být i v root `require`, protože Composer path repository s `symlink: true` neinstaluje sub-dependence automaticky.

- [ ] **Step 4: Vytvoř adresářovou strukturu**

```bash
mkdir -p packages/identity/src/{User/{Domain,Application,Infrastructure/{Persistence,Doctrine/Type,Http}},Role/{Domain,Application,Infrastructure/{Persistence,Doctrine/Type,Http}},Auth/{Domain,Application/{Login,RefreshAccessToken,Logout,GetCurrentUser},Infrastructure/{Jwt,Persistence,Doctrine/Type,Security,Http}}}
mkdir -p packages/identity/tests/{User/{Domain,Application},Role/{Domain,Application},Auth/{Domain,Application}}
```

- [ ] **Step 5: Nainstaluj packages**

```bash
docker compose run --rm app composer update
```

Očekáváno: `packages/identity` je symlinknuté do `vendor/ddd-erp/identity`, `firebase/php-jwt` nainstalován.

- [ ] **Step 6: Nainstaluj PHPUnit pro identity package**

```bash
cd packages/identity && composer install
```

Očekáváno: `vendor/` adresář vytvořen v `packages/identity/`.

- [ ] **Step 7: Ověř autoload**

```bash
docker compose run --rm app php -r "echo class_exists('Identity\User\Domain\User') ? 'EXISTS' : 'NOT YET'; echo PHP_EOL;"
```

Očekáváno: `NOT YET` (třída ještě neexistuje, ale žádný autoload error).

- [ ] **Step 8: Commit**

```bash
git add packages/identity/composer.json packages/identity/phpunit.xml composer.json composer.lock
git commit -m "feat(identity): Composer package setup s path repository"
```

---

## Task 2: Role Domain (TDD) — RoleId, RoleName, Role, events, RoleRepository interface

**Files:**
- Create: `packages/identity/tests/Role/Domain/RoleIdTest.php`
- Create: `packages/identity/tests/Role/Domain/RoleNameTest.php`
- Create: `packages/identity/tests/Role/Domain/RoleTest.php`
- Create: `packages/identity/src/Role/Domain/RoleId.php`
- Create: `packages/identity/src/Role/Domain/RoleName.php`
- Create: `packages/identity/src/Role/Domain/Role.php`
- Create: `packages/identity/src/Role/Domain/RoleCreated.php`
- Create: `packages/identity/src/Role/Domain/RolePermissionsUpdated.php`
- Create: `packages/identity/src/Role/Domain/RoleRepository.php`
- Create: `packages/identity/src/Role/Domain/RoleNotFoundException.php`

- [ ] **Step 1: Napiš failing test pro RoleId**

```php
<?php
// packages/identity/tests/Role/Domain/RoleIdTest.php
declare(strict_types=1);

namespace Identity\Tests\Role\Domain;

use Identity\Role\Domain\RoleId;
use PHPUnit\Framework\TestCase;

final class RoleIdTest extends TestCase
{
    public function test_generates_unique_ids(): void
    {
        $id1 = RoleId::generate();
        $id2 = RoleId::generate();

        $this->assertNotEquals($id1->value(), $id2->value());
    }

    public function test_creates_from_valid_string(): void
    {
        $id = RoleId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', $id->value());
    }

    public function test_throws_on_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleId::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $id1 = RoleId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $id2 = RoleId::fromString('018e8f2a-1234-7000-8000-000000000001');

        $this->assertTrue($id1->equals($id2));
    }

    public function test_to_string(): void
    {
        $id = RoleId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', (string) $id);
    }
}
```

- [ ] **Step 2: Napiš failing test pro RoleName**

```php
<?php
// packages/identity/tests/Role/Domain/RoleNameTest.php
declare(strict_types=1);

namespace Identity\Tests\Role\Domain;

use Identity\Role\Domain\RoleName;
use PHPUnit\Framework\TestCase;

final class RoleNameTest extends TestCase
{
    public function test_creates_from_valid_slug(): void
    {
        $name = RoleName::fromString('crm-manager');
        $this->assertSame('crm-manager', $name->value());
    }

    public function test_throws_on_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('');
    }

    public function test_throws_on_whitespace_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('   ');
    }

    public function test_throws_on_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoleName::fromString('CRM Manager!');
    }

    public function test_equality(): void
    {
        $name1 = RoleName::fromString('crm-manager');
        $name2 = RoleName::fromString('crm-manager');

        $this->assertTrue($name1->equals($name2));
    }

    public function test_to_string(): void
    {
        $name = RoleName::fromString('super-admin');
        $this->assertSame('super-admin', (string) $name);
    }
}
```

- [ ] **Step 3: Napiš failing test pro Role**

```php
<?php
// packages/identity/tests/Role/Domain/RoleTest.php
declare(strict_types=1);

namespace Identity\Tests\Role\Domain;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\Role\Domain\RolePermissionsUpdated;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_creates_role_with_permissions(): void
    {
        $id   = RoleId::generate();
        $name = RoleName::fromString('crm-manager');
        $permissions = ['crm.contacts.view_customers', 'crm.contacts.create_customer'];

        $role = Role::create($id, $name, $permissions);

        $this->assertTrue($role->id()->equals($id));
        $this->assertTrue($role->name()->equals($name));
        $this->assertSame($permissions, $role->permissions());
    }

    public function test_create_records_role_created_event(): void
    {
        $role = Role::create(
            RoleId::generate(),
            RoleName::fromString('crm-manager'),
            ['crm.contacts.view_customers'],
        );

        $events = $role->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleCreated::class, $events[0]);
    }

    public function test_update_permissions(): void
    {
        $role = Role::create(
            RoleId::generate(),
            RoleName::fromString('crm-manager'),
            ['crm.contacts.view_customers'],
        );
        $role->pullDomainEvents(); // clear creation event

        $newPermissions = ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'];
        $role->updatePermissions($newPermissions);

        $this->assertSame($newPermissions, $role->permissions());

        $events = $role->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RolePermissionsUpdated::class, $events[0]);
    }
}
```

- [ ] **Step 4: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Role/Domain/ --testdox
```

Očekáváno: `Error: Class "Identity\Role\Domain\RoleId" not found`

- [ ] **Step 5: Implementuj RoleId**

```php
<?php
// packages/identity/src/Role/Domain/RoleId.php
declare(strict_types=1);

namespace Identity\Role\Domain;

use Symfony\Component\Uid\Uuid;

final class RoleId
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid RoleId: '$value'");
        }
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 6: Implementuj RoleName**

```php
<?php
// packages/identity/src/Role/Domain/RoleName.php
declare(strict_types=1);

namespace Identity\Role\Domain;

final class RoleName
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $trimmed)) {
            throw new \InvalidArgumentException("Invalid role name slug: '$value'");
        }
        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 7: Implementuj RoleCreated event**

```php
<?php
// packages/identity/src/Role/Domain/RoleCreated.php
declare(strict_types=1);

namespace Identity\Role\Domain;

use SharedKernel\Domain\DomainEvent;

final class RoleCreated extends DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly RoleName $name,
        /** @var string[] */
        public readonly array $permissions,
    ) {
        parent::__construct();
    }
}
```

- [ ] **Step 8: Implementuj RolePermissionsUpdated event**

```php
<?php
// packages/identity/src/Role/Domain/RolePermissionsUpdated.php
declare(strict_types=1);

namespace Identity\Role\Domain;

use SharedKernel\Domain\DomainEvent;

final class RolePermissionsUpdated extends DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        /** @var string[] */
        public readonly array $permissions,
    ) {
        parent::__construct();
    }
}
```

- [ ] **Step 9: Implementuj RoleNotFoundException**

```php
<?php
// packages/identity/src/Role/Domain/RoleNotFoundException.php
declare(strict_types=1);

namespace Identity\Role\Domain;

final class RoleNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Role not found: '$id'");
    }
}
```

- [ ] **Step 10: Implementuj Role Aggregate**

```php
<?php
// packages/identity/src/Role/Domain/Role.php
declare(strict_types=1);

namespace Identity\Role\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Role extends AggregateRoot
{
    private function __construct(
        private readonly RoleId $id,
        private RoleName $name,
        /** @var string[] */
        private array $permissions,
    ) {}

    /** @param string[] $permissions */
    public static function create(RoleId $id, RoleName $name, array $permissions): self
    {
        $role = new self($id, $name, array_values($permissions));
        $role->recordEvent(new RoleCreated($id, $name, $role->permissions));
        return $role;
    }

    /** @param string[] $permissions */
    public function updatePermissions(array $permissions): void
    {
        $this->permissions = array_values($permissions);
        $this->recordEvent(new RolePermissionsUpdated($this->id, $this->permissions));
    }

    public function id(): RoleId { return $this->id; }
    public function name(): RoleName { return $this->name; }
    /** @return string[] */
    public function permissions(): array { return $this->permissions; }
}
```

- [ ] **Step 11: Implementuj RoleRepository interface**

```php
<?php
// packages/identity/src/Role/Domain/RoleRepository.php
declare(strict_types=1);

namespace Identity\Role\Domain;

interface RoleRepository
{
    /** @throws RoleNotFoundException */
    public function get(RoleId $id): Role;

    public function save(Role $role): void;

    public function nextIdentity(): RoleId;
}
```

- [ ] **Step 12: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Role/Domain/ --testdox
```

Očekáváno:
```
Identity\Tests\Role\Domain\RoleIdTest
 ✔ Generates unique ids
 ✔ Creates from valid string
 ✔ Throws on invalid string
 ✔ Equality
 ✔ To string

Identity\Tests\Role\Domain\RoleNameTest
 ✔ Creates from valid slug
 ✔ Throws on empty string
 ✔ Throws on whitespace only
 ✔ Throws on invalid characters
 ✔ Equality
 ✔ To string

Identity\Tests\Role\Domain\RoleTest
 ✔ Creates role with permissions
 ✔ Create records role created event
 ✔ Update permissions
```

- [ ] **Step 13: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): Role aggregate — RoleId, RoleName, Role, events, RoleRepository interface"
```

---

## Task 3: User Domain (TDD) — UserId, UserEmail, UserPassword, UserName, User, events, UserRepository interface

**Files:**
- Create: `packages/identity/tests/User/Domain/UserIdTest.php`
- Create: `packages/identity/tests/User/Domain/UserEmailTest.php`
- Create: `packages/identity/tests/User/Domain/UserPasswordTest.php`
- Create: `packages/identity/tests/User/Domain/UserNameTest.php`
- Create: `packages/identity/tests/User/Domain/UserTest.php`
- Create: `packages/identity/src/User/Domain/UserId.php`
- Create: `packages/identity/src/User/Domain/UserEmail.php`
- Create: `packages/identity/src/User/Domain/UserPassword.php`
- Create: `packages/identity/src/User/Domain/UserName.php`
- Create: `packages/identity/src/User/Domain/User.php`
- Create: `packages/identity/src/User/Domain/UserCreated.php`
- Create: `packages/identity/src/User/Domain/UserUpdated.php`
- Create: `packages/identity/src/User/Domain/UserDeactivated.php`
- Create: `packages/identity/src/User/Domain/RoleAssignedToUser.php`
- Create: `packages/identity/src/User/Domain/UserRepository.php`
- Create: `packages/identity/src/User/Domain/UserNotFoundException.php`

- [ ] **Step 1: Napiš failing test pro UserId**

```php
<?php
// packages/identity/tests/User/Domain/UserIdTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    public function test_generates_unique_ids(): void
    {
        $id1 = UserId::generate();
        $id2 = UserId::generate();

        $this->assertNotEquals($id1->value(), $id2->value());
    }

    public function test_creates_from_valid_string(): void
    {
        $id = UserId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', $id->value());
    }

    public function test_throws_on_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserId::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $id1 = UserId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $id2 = UserId::fromString('018e8f2a-1234-7000-8000-000000000001');

        $this->assertTrue($id1->equals($id2));
    }

    public function test_to_string(): void
    {
        $id = UserId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', (string) $id);
    }
}
```

- [ ] **Step 2: Napiš failing test pro UserEmail**

```php
<?php
// packages/identity/tests/User/Domain/UserEmailTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserEmail;
use PHPUnit\Framework\TestCase;

final class UserEmailTest extends TestCase
{
    public function test_creates_from_valid_email(): void
    {
        $email = UserEmail::fromString('Admin@ERP.Local');
        $this->assertSame('admin@erp.local', $email->value());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserEmail::fromString('not-an-email');
    }

    public function test_equality(): void
    {
        $email1 = UserEmail::fromString('admin@erp.local');
        $email2 = UserEmail::fromString('ADMIN@ERP.LOCAL');

        $this->assertTrue($email1->equals($email2));
    }

    public function test_to_string(): void
    {
        $email = UserEmail::fromString('admin@erp.local');
        $this->assertSame('admin@erp.local', (string) $email);
    }
}
```

- [ ] **Step 3: Napiš failing test pro UserPassword**

```php
<?php
// packages/identity/tests/User/Domain/UserPasswordTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserPassword;
use PHPUnit\Framework\TestCase;

final class UserPasswordTest extends TestCase
{
    public function test_hashes_plaintext_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');

        // Hash se liší od plaintextu
        $this->assertNotSame('SecurePass123!', $password->hash());
        // Hash začíná bcrypt prefixem
        $this->assertStringStartsWith('$2y$', $password->hash());
    }

    public function test_verify_correct_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');

        $this->assertTrue($password->verify('SecurePass123!'));
    }

    public function test_verify_wrong_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');

        $this->assertFalse($password->verify('WrongPassword'));
    }

    public function test_throws_on_too_short_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserPassword::fromPlaintext('short');
    }

    public function test_creates_from_existing_hash(): void
    {
        $original = UserPassword::fromPlaintext('SecurePass123!');
        $restored = UserPassword::fromHash($original->hash());

        $this->assertTrue($restored->verify('SecurePass123!'));
    }
}
```

- [ ] **Step 4: Napiš failing test pro UserName**

```php
<?php
// packages/identity/tests/User/Domain/UserNameTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserName;
use PHPUnit\Framework\TestCase;

final class UserNameTest extends TestCase
{
    public function test_creates_from_parts(): void
    {
        $name = UserName::fromParts('Jan', 'Novák');
        $this->assertSame('Jan', $name->firstName());
        $this->assertSame('Novák', $name->lastName());
        $this->assertSame('Jan Novák', $name->fullName());
    }

    public function test_throws_on_empty_first_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserName::fromParts('', 'Novák');
    }

    public function test_throws_on_empty_last_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserName::fromParts('Jan', '  ');
    }

    public function test_equality(): void
    {
        $name1 = UserName::fromParts('Jan', 'Novák');
        $name2 = UserName::fromParts('Jan', 'Novák');

        $this->assertTrue($name1->equals($name2));
    }
}
```

- [ ] **Step 5: Napiš failing test pro User aggregate**

```php
<?php
// packages/identity/tests/User/Domain/UserTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\Role\Domain\RoleId;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\User;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserPassword;
use Identity\User\Domain\UserUpdated;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function createUser(): User
    {
        return User::create(
            UserId::generate(),
            UserEmail::fromString('jan@firma.cz'),
            UserPassword::fromPlaintext('SecurePass123!'),
            UserName::fromParts('Jan', 'Novák'),
        );
    }

    public function test_creates_user(): void
    {
        $id       = UserId::generate();
        $email    = UserEmail::fromString('jan@firma.cz');
        $password = UserPassword::fromPlaintext('SecurePass123!');
        $name     = UserName::fromParts('Jan', 'Novák');

        $user = User::create($id, $email, $password, $name);

        $this->assertTrue($user->id()->equals($id));
        $this->assertTrue($user->email()->equals($email));
        $this->assertTrue($user->name()->equals($name));
        $this->assertTrue($user->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->createdAt());
    }

    public function test_create_records_user_created_event(): void
    {
        $user = $this->createUser();

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function test_update_email_and_name(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents(); // clear creation event

        $newEmail = UserEmail::fromString('petr@firma.cz');
        $newName  = UserName::fromParts('Petr', 'Svoboda');
        $user->update($newEmail, $newName);

        $this->assertTrue($user->email()->equals($newEmail));
        $this->assertTrue($user->name()->equals($newName));

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[0]);
    }

    public function test_deactivate(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $user->deactivate();

        $this->assertFalse($user->isActive());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserDeactivated::class, $events[0]);
    }

    public function test_assign_roles(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $roleId1 = RoleId::generate();
        $roleId2 = RoleId::generate();
        $user->assignRoles([$roleId1, $roleId2]);

        $roleIds = $user->roleIds();
        $this->assertCount(2, $roleIds);
        $this->assertTrue($roleIds[0]->equals($roleId1));
        $this->assertTrue($roleIds[1]->equals($roleId2));

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleAssignedToUser::class, $events[0]);
    }

    public function test_assign_roles_replaces_existing(): void
    {
        $user = $this->createUser();
        $user->assignRoles([RoleId::generate(), RoleId::generate()]);
        $user->pullDomainEvents();

        $newRoleId = RoleId::generate();
        $user->assignRoles([$newRoleId]);

        $roleIds = $user->roleIds();
        $this->assertCount(1, $roleIds);
        $this->assertTrue($roleIds[0]->equals($newRoleId));
    }

    public function test_password_verification(): void
    {
        $user = $this->createUser();

        $this->assertTrue($user->password()->verify('SecurePass123!'));
        $this->assertFalse($user->password()->verify('WrongPassword'));
    }
}
```

- [ ] **Step 6: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/User/Domain/ --testdox
```

Očekáváno: `Error: Class "Identity\User\Domain\UserId" not found`

- [ ] **Step 7: Implementuj UserId**

```php
<?php
// packages/identity/src/User/Domain/UserId.php
declare(strict_types=1);

namespace Identity\User\Domain;

use Symfony\Component\Uid\Uuid;

final class UserId
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid UserId: '$value'");
        }
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 8: Implementuj UserEmail**

```php
<?php
// packages/identity/src/User/Domain/UserEmail.php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserEmail
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: '$value'");
        }
        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 9: Implementuj UserPassword**

```php
<?php
// packages/identity/src/User/Domain/UserPassword.php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserPassword
{
    private const int MIN_LENGTH = 8;

    private function __construct(
        private readonly string $hash,
    ) {}

    public static function fromPlaintext(string $plaintext): self
    {
        if (mb_strlen($plaintext) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password must be at least %d characters', self::MIN_LENGTH),
            );
        }
        return new self(password_hash($plaintext, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function verify(string $plaintext): bool
    {
        return password_verify($plaintext, $this->hash);
    }
}
```

- [ ] **Step 10: Implementuj UserName**

```php
<?php
// packages/identity/src/User/Domain/UserName.php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserName
{
    private function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
    ) {}

    public static function fromParts(string $firstName, string $lastName): self
    {
        if (trim($firstName) === '') {
            throw new \InvalidArgumentException('First name cannot be empty');
        }
        if (trim($lastName) === '') {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }
        return new self(trim($firstName), trim($lastName));
    }

    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function fullName(): string { return "{$this->firstName} {$this->lastName}"; }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName && $this->lastName === $other->lastName;
    }
}
```

- [ ] **Step 11: Implementuj domain events**

```php
<?php
// packages/identity/src/User/Domain/UserCreated.php
declare(strict_types=1);

namespace Identity\User\Domain;

use SharedKernel\Domain\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly UserEmail $email,
        public readonly UserName $name,
    ) {
        parent::__construct();
    }
}
```

```php
<?php
// packages/identity/src/User/Domain/UserUpdated.php
declare(strict_types=1);

namespace Identity\User\Domain;

use SharedKernel\Domain\DomainEvent;

final class UserUpdated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly UserEmail $email,
        public readonly UserName $name,
    ) {
        parent::__construct();
    }
}
```

```php
<?php
// packages/identity/src/User/Domain/UserDeactivated.php
declare(strict_types=1);

namespace Identity\User\Domain;

use SharedKernel\Domain\DomainEvent;

final class UserDeactivated extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
    ) {
        parent::__construct();
    }
}
```

```php
<?php
// packages/identity/src/User/Domain/RoleAssignedToUser.php
declare(strict_types=1);

namespace Identity\User\Domain;

use Identity\Role\Domain\RoleId;
use SharedKernel\Domain\DomainEvent;

final class RoleAssignedToUser extends DomainEvent
{
    public function __construct(
        public readonly UserId $userId,
        /** @var RoleId[] */
        public readonly array $roleIds,
    ) {
        parent::__construct();
    }
}
```

- [ ] **Step 12: Implementuj UserNotFoundException**

```php
<?php
// packages/identity/src/User/Domain/UserNotFoundException.php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("User not found: '$id'");
    }
}
```

- [ ] **Step 13: Implementuj User Aggregate**

```php
<?php
// packages/identity/src/User/Domain/User.php
declare(strict_types=1);

namespace Identity\User\Domain;

use Identity\Role\Domain\RoleId;
use SharedKernel\Domain\AggregateRoot;

final class User extends AggregateRoot
{
    /** @var string[] interně uloženo jako raw UUID stringy */
    private array $roleIds = [];

    private function __construct(
        private readonly UserId $id,
        private UserEmail $email,
        private UserPassword $password,
        private UserName $name,
        private bool $active,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        UserId $id,
        UserEmail $email,
        UserPassword $password,
        UserName $name,
    ): self {
        $user = new self($id, $email, $password, $name, true, new \DateTimeImmutable());
        $user->recordEvent(new UserCreated($id, $email, $name));
        return $user;
    }

    public function update(UserEmail $email, UserName $name): void
    {
        $this->email = $email;
        $this->name  = $name;
        $this->recordEvent(new UserUpdated($this->id, $email, $name));
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->recordEvent(new UserDeactivated($this->id));
    }

    /** @param RoleId[] $roleIds */
    public function assignRoles(array $roleIds): void
    {
        $this->roleIds = array_map(fn(RoleId $id) => $id->value(), $roleIds);
        $this->recordEvent(new RoleAssignedToUser($this->id, $roleIds));
    }

    public function id(): UserId { return $this->id; }
    public function email(): UserEmail { return $this->email; }
    public function password(): UserPassword { return $this->password; }
    public function name(): UserName { return $this->name; }
    public function isActive(): bool { return $this->active; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return RoleId[] */
    public function roleIds(): array
    {
        return array_map(fn(string $id) => RoleId::fromString($id), $this->roleIds);
    }
}
```

- [ ] **Step 14: Implementuj UserRepository interface**

```php
<?php
// packages/identity/src/User/Domain/UserRepository.php
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
```

- [ ] **Step 15: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/User/Domain/ --testdox
```

Očekáváno:
```
Identity\Tests\User\Domain\UserIdTest
 ✔ Generates unique ids
 ✔ Creates from valid string
 ✔ Throws on invalid string
 ✔ Equality
 ✔ To string

Identity\Tests\User\Domain\UserEmailTest
 ✔ Creates from valid email
 ✔ Throws on invalid email
 ✔ Equality
 ✔ To string

Identity\Tests\User\Domain\UserPasswordTest
 ✔ Hashes plaintext password
 ✔ Verify correct password
 ✔ Verify wrong password
 ✔ Throws on too short password
 ✔ Creates from existing hash

Identity\Tests\User\Domain\UserNameTest
 ✔ Creates from parts
 ✔ Throws on empty first name
 ✔ Throws on empty last name
 ✔ Equality

Identity\Tests\User\Domain\UserTest
 ✔ Creates user
 ✔ Create records user created event
 ✔ Update email and name
 ✔ Deactivate
 ✔ Assign roles
 ✔ Assign roles replaces existing
 ✔ Password verification
```

- [ ] **Step 16: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): User aggregate — UserId, UserEmail, UserPassword, UserName, events, UserRepository interface"
```

---

## Task 4: RefreshToken entity + RefreshTokenRepository interface

**Files:**
- Create: `packages/identity/tests/Auth/Domain/RefreshTokenTest.php`
- Create: `packages/identity/src/Auth/Domain/RefreshToken.php`
- Create: `packages/identity/src/Auth/Domain/RefreshTokenId.php`
- Create: `packages/identity/src/Auth/Domain/RefreshTokenRepository.php`
- Create: `packages/identity/src/Auth/Domain/InvalidTokenException.php`

- [ ] **Step 1: Napiš failing test**

```php
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
```

- [ ] **Step 2: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Domain/RefreshTokenTest.php --testdox
```

Očekáváno: `Error: Class "Identity\Auth\Domain\RefreshToken" not found`

- [ ] **Step 3: Implementuj RefreshTokenId**

```php
<?php
// packages/identity/src/Auth/Domain/RefreshTokenId.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use Symfony\Component\Uid\Uuid;

final class RefreshTokenId
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid RefreshTokenId: '$value'");
        }
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 4: Implementuj RefreshToken entity**

```php
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
```

- [ ] **Step 5: Implementuj InvalidTokenException**

```php
<?php
// packages/identity/src/Auth/Domain/InvalidTokenException.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

final class InvalidTokenException extends \DomainException
{
    public function __construct(string $message = 'Invalid or expired token')
    {
        parent::__construct($message);
    }
}
```

- [ ] **Step 6: Implementuj RefreshTokenRepository interface**

```php
<?php
// packages/identity/src/Auth/Domain/RefreshTokenRepository.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

interface RefreshTokenRepository
{
    public function findByTokenHash(string $hash): ?RefreshToken;

    public function save(RefreshToken $token): void;
}
```

- [ ] **Step 7: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Domain/ --testdox
```

Očekáváno:
```
Identity\Tests\Auth\Domain\RefreshTokenTest
 ✔ Creates valid token
 ✔ Revoke makes token invalid
 ✔ Expired token is invalid
```

- [ ] **Step 8: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): RefreshToken entity, RefreshTokenId, InvalidTokenException, RefreshTokenRepository interface"
```

---

## Task 5: IdentityPermission enum

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Security/IdentityPermission.php`

- [ ] **Step 1: Implementuj IdentityPermission enum**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Security/IdentityPermission.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

enum IdentityPermission: string
{
    case MANAGE_USERS = 'identity.users.manage';
    case MANAGE_ROLES = 'identity.roles.manage';
    case VIEW_USERS   = 'identity.users.view';
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/identity/src/Auth/Infrastructure/Security/IdentityPermission.php
git commit -m "feat(identity): IdentityPermission enum"
```

---

## Task 6: JwtTokenService (TDD) — firebase/php-jwt wrapper

**Files:**
- Create: `packages/identity/tests/Auth/Application/JwtTokenServiceTest.php`
- Create: `packages/identity/src/Auth/Application/JwtTokenService.php`
- Create: `packages/identity/src/Auth/Infrastructure/Jwt/FirebaseJwtTokenService.php`

- [ ] **Step 1: Napiš failing test**

```php
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
            ttl: 900, // 15 minutes
        );
    }

    public function test_issues_and_validates_access_token(): void
    {
        $userId = UserId::generate();
        $permissions = ['crm.contacts.view_customers', 'identity.users.manage'];

        $token = $this->service->issueAccessToken($userId, $permissions);

        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token)); // JWT has 3 parts

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
        // Vytvoř service s TTL=0 (okamžitě expiruje)
        $service = new FirebaseJwtTokenService(
            secret: 'test-secret-key-at-least-32-chars-long!!',
            ttl: -1, // already expired
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
        // 64 bytes = 128 hex characters
        $this->assertSame(128, strlen($token1));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token1);
    }
}
```

- [ ] **Step 2: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Application/JwtTokenServiceTest.php --testdox
```

Očekáváno: `Error: Class "Identity\Auth\Application\JwtTokenService" not found`

- [ ] **Step 3: Implementuj JwtTokenService interface (port)**

```php
<?php
// packages/identity/src/Auth/Application/JwtTokenService.php
declare(strict_types=1);

namespace Identity\Auth\Application;

use Identity\Auth\Domain\InvalidTokenException;
use Identity\User\Domain\UserId;

interface JwtTokenService
{
    /**
     * @param string[] $permissions
     * @return string JWT token
     */
    public function issueAccessToken(UserId $userId, array $permissions): string;

    /**
     * @return array{sub: string, permissions: string[], iat: int, exp: int}
     * @throws InvalidTokenException
     */
    public function validateAccessToken(string $token): array;

    /**
     * @return string Random 64-byte hex string (plaintext — DB stores SHA-256 hash)
     */
    public function generateRefreshToken(): string;
}
```

- [ ] **Step 4: Implementuj FirebaseJwtTokenService**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Jwt/FirebaseJwtTokenService.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Jwt;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\User\Domain\UserId;

final class FirebaseJwtTokenService implements JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl,
    ) {}

    public function issueAccessToken(UserId $userId, array $permissions): string
    {
        $now = time();
        $payload = [
            'sub'         => $userId->value(),
            'permissions' => $permissions,
            'iat'         => $now,
            'exp'         => $now + $this->ttl,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new InvalidTokenException('Token has expired');
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage());
        }
    }

    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }
}
```

- [ ] **Step 5: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Application/JwtTokenServiceTest.php --testdox
```

Očekáváno:
```
Identity\Tests\Auth\Application\JwtTokenServiceTest
 ✔ Issues and validates access token
 ✔ Validates token contains correct expiry
 ✔ Throws on invalid token
 ✔ Throws on expired token
 ✔ Throws on wrong secret
 ✔ Generates refresh token
```

- [ ] **Step 6: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): JwtTokenService interface + FirebaseJwtTokenService implementace"
```

---

## Task 7: Doctrine types + XML mapping

**Files:**
- Create: `packages/identity/src/User/Infrastructure/Doctrine/Type/UserIdType.php`
- Create: `packages/identity/src/User/Infrastructure/Doctrine/Type/UserEmailType.php`
- Create: `packages/identity/src/Role/Infrastructure/Doctrine/Type/RoleIdType.php`
- Create: `packages/identity/src/Auth/Infrastructure/Doctrine/Type/RefreshTokenIdType.php`
- Create: `packages/identity/src/User/Infrastructure/Doctrine/User.orm.xml`
- Create: `packages/identity/src/User/Infrastructure/Doctrine/UserName.orm.xml`
- Create: `packages/identity/src/Role/Infrastructure/Doctrine/Role.orm.xml`
- Create: `packages/identity/src/Auth/Infrastructure/Doctrine/RefreshToken.orm.xml`

- [ ] **Step 1: Implementuj UserIdType**

```php
<?php
// packages/identity/src/User/Infrastructure/Doctrine/Type/UserIdType.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\User\Domain\UserId;

final class UserIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserId
    {
        if ($value === null) {
            return null;
        }
        return UserId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof UserId ? $value->value() : (string) $value;
    }
}
```

- [ ] **Step 2: Implementuj UserEmailType**

```php
<?php
// packages/identity/src/User/Infrastructure/Doctrine/Type/UserEmailType.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\User\Domain\UserEmail;

final class UserEmailType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserEmail
    {
        if ($value === null) {
            return null;
        }
        return UserEmail::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof UserEmail ? $value->value() : (string) $value;
    }
}
```

- [ ] **Step 3: Implementuj RoleIdType**

```php
<?php
// packages/identity/src/Role/Infrastructure/Doctrine/Type/RoleIdType.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Role\Domain\RoleId;

final class RoleIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RoleId
    {
        if ($value === null) {
            return null;
        }
        return RoleId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RoleId ? $value->value() : (string) $value;
    }
}
```

- [ ] **Step 4: Implementuj RefreshTokenIdType**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Doctrine/Type/RefreshTokenIdType.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Auth\Domain\RefreshTokenId;

final class RefreshTokenIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RefreshTokenId
    {
        if ($value === null) {
            return null;
        }
        return RefreshTokenId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RefreshTokenId ? $value->value() : (string) $value;
    }
}
```

- [ ] **Step 5: Vytvoř User.orm.xml mapping**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- packages/identity/src/User/Infrastructure/Doctrine/User.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="Identity\User\Domain\User" table="identity_users">

        <id name="id" type="user_id" column="id">
            <generator strategy="NONE"/>
        </id>

        <field name="email" type="user_email" column="email" length="255" nullable="false" unique="true"/>

        <field name="password" type="string" column="password_hash" length="255" nullable="false"/>

        <embedded name="name" class="Identity\User\Domain\UserName" use-column-prefix="false"/>

        <field name="roleIds" type="json" column="role_ids" nullable="false"/>

        <field name="active" type="boolean" column="active" nullable="false"/>

        <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>

    </entity>

</doctrine-mapping>
```

Pozn.: Pole `password` je mapováno jako `string` (ne custom type) — `UserPassword` bude konvertován ručně v repository (persist `$user->password()->hash()`, load `UserPassword::fromHash()`). Alternativně lze Doctrine property accessor, ale tady zachováme jednoduchost: Doctrine mappe přímo privátní `$password` field jako string. **Upozornění:** pro správné mapování `password` fieldu na `UserPassword` objekt potřebujeme custom Doctrine type nebo lifecycle callback. Jednodušší řešení: mapovat `password` jako custom type `user_password`:

Nahraď v User.orm.xml:

```xml
        <field name="password" type="user_password" column="password_hash" length="255" nullable="false"/>
```

A přidej UserPasswordType:

```php
<?php
// packages/identity/src/User/Infrastructure/Doctrine/Type/UserPasswordType.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\User\Domain\UserPassword;

final class UserPasswordType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserPassword
    {
        if ($value === null) {
            return null;
        }
        return UserPassword::fromHash((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof UserPassword ? $value->hash() : (string) $value;
    }
}
```

- [ ] **Step 6: Vytvoř UserName.orm.xml embeddable mapping**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- packages/identity/src/User/Infrastructure/Doctrine/UserName.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="Identity\User\Domain\UserName">
        <field name="firstName" type="string" column="first_name" length="100" nullable="false"/>
        <field name="lastName"  type="string" column="last_name"  length="100" nullable="false"/>
    </embeddable>

</doctrine-mapping>
```

- [ ] **Step 7: Vytvoř Role.orm.xml mapping**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- packages/identity/src/Role/Infrastructure/Doctrine/Role.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="Identity\Role\Domain\Role" table="identity_roles">

        <id name="id" type="role_id" column="id">
            <generator strategy="NONE"/>
        </id>

        <field name="name" type="string" column="name" length="100" nullable="false" unique="true"/>

        <field name="permissions" type="json" column="permissions" nullable="false"/>

    </entity>

</doctrine-mapping>
```

Pozn.: `name` sloupec je mapován jako `string` (ne custom type) — `RoleName` VO potřebuje custom type pro správnou hydraci. Přidej RoleNameType:

```php
<?php
// packages/identity/src/Role/Infrastructure/Doctrine/Type/RoleNameType.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Role\Domain\RoleName;

final class RoleNameType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RoleName
    {
        if ($value === null) {
            return null;
        }
        return RoleName::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RoleName ? $value->value() : (string) $value;
    }
}
```

Aktualizuj Role.orm.xml — `name` field s custom typem:

```xml
        <field name="name" type="role_name" column="name" length="100" nullable="false" unique="true"/>
```

- [ ] **Step 8: Vytvoř RefreshToken.orm.xml mapping**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- packages/identity/src/Auth/Infrastructure/Doctrine/RefreshToken.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="Identity\Auth\Domain\RefreshToken" table="identity_refresh_tokens">

        <id name="id" type="refresh_token_id" column="id">
            <generator strategy="NONE"/>
        </id>

        <field name="userId" type="user_id" column="user_id" nullable="false"/>

        <field name="tokenHash" type="string" column="token_hash" length="64" nullable="false"/>

        <field name="expiresAt" type="datetime_immutable" column="expires_at" nullable="false"/>

        <field name="revokedAt" type="datetime_immutable" column="revoked_at" nullable="true"/>

    </entity>

</doctrine-mapping>
```

- [ ] **Step 9: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): Doctrine DBAL types + XML mapping pro User, Role, RefreshToken"
```

---

## Task 8: Doctrine repositories + services.yaml

**Files:**
- Create: `packages/identity/src/User/Infrastructure/Persistence/DoctrineUserRepository.php`
- Create: `packages/identity/src/Role/Infrastructure/Persistence/DoctrineRoleRepository.php`
- Create: `packages/identity/src/Auth/Infrastructure/Persistence/DoctrineRefreshTokenRepository.php`
- Modify: `config/packages/doctrine.yaml`
- Modify: `config/services.yaml`
- Modify: `.env`

- [ ] **Step 1: Implementuj DoctrineUserRepository**

```php
<?php
// packages/identity/src/User/Infrastructure/Persistence/DoctrineUserRepository.php
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
```

- [ ] **Step 2: Implementuj DoctrineRoleRepository**

```php
<?php
// packages/identity/src/Role/Infrastructure/Persistence/DoctrineRoleRepository.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RoleRepository;

final class DoctrineRoleRepository implements RoleRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function get(RoleId $id): Role
    {
        $role = $this->entityManager->find(Role::class, $id);
        if ($role === null) {
            throw new RoleNotFoundException($id->value());
        }
        return $role;
    }

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}
```

- [ ] **Step 3: Implementuj DoctrineRefreshTokenRepository**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Persistence/DoctrineRefreshTokenRepository.php
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
```

- [ ] **Step 4: Uprav config/packages/doctrine.yaml — přidej identity mapping + types**

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        server_version: '16'
        types:
            customer_id:      Crm\Contacts\Infrastructure\Doctrine\Type\CustomerIdType
            customer_email:   Crm\Contacts\Infrastructure\Doctrine\Type\CustomerEmailType
            user_id:          Identity\User\Infrastructure\Doctrine\Type\UserIdType
            user_email:       Identity\User\Infrastructure\Doctrine\Type\UserEmailType
            user_password:    Identity\User\Infrastructure\Doctrine\Type\UserPasswordType
            role_id:          Identity\Role\Infrastructure\Doctrine\Type\RoleIdType
            role_name:        Identity\Role\Infrastructure\Doctrine\Type\RoleNameType
            refresh_token_id: Identity\Auth\Infrastructure\Doctrine\Type\RefreshTokenIdType

    orm:
        auto_generate_proxy_classes: true
        enable_native_lazy_objects: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        mappings:
            CrmContacts:
                type: xml
                dir: '%kernel.project_dir%/packages/crm/src/Contacts/Infrastructure/Doctrine'
                prefix: 'Crm\Contacts\Domain'
                is_bundle: false
            IdentityUser:
                type: xml
                dir: '%kernel.project_dir%/packages/identity/src/User/Infrastructure/Doctrine'
                prefix: 'Identity\User\Domain'
                is_bundle: false
            IdentityRole:
                type: xml
                dir: '%kernel.project_dir%/packages/identity/src/Role/Infrastructure/Doctrine'
                prefix: 'Identity\Role\Domain'
                is_bundle: false
            IdentityAuth:
                type: xml
                dir: '%kernel.project_dir%/packages/identity/src/Auth/Infrastructure/Doctrine'
                prefix: 'Identity\Auth\Domain'
                is_bundle: false
```

- [ ] **Step 5: Uprav config/services.yaml — přidej identity services**

Přidej na konec existujícího `config/services.yaml`:

```yaml
    # Identity package — autoconfigure handlery a services
    Identity\:
        resource: '../packages/identity/src/'
        exclude:
            - '../packages/identity/src/*/Domain/'
            - '../packages/identity/src/*/Infrastructure/Doctrine/Type/'

    Identity\User\Domain\UserRepository:
        alias: Identity\User\Infrastructure\Persistence\DoctrineUserRepository

    Identity\Role\Domain\RoleRepository:
        alias: Identity\Role\Infrastructure\Persistence\DoctrineRoleRepository

    Identity\Auth\Domain\RefreshTokenRepository:
        alias: Identity\Auth\Infrastructure\Persistence\DoctrineRefreshTokenRepository

    Identity\Auth\Application\JwtTokenService:
        alias: Identity\Auth\Infrastructure\Jwt\FirebaseJwtTokenService

    Identity\Auth\Infrastructure\Jwt\FirebaseJwtTokenService:
        arguments:
            $secret: '%env(JWT_SECRET)%'
            $ttl: '%env(int:JWT_TTL)%'
```

- [ ] **Step 6: Přidej JWT env proměnné do .env**

Přidej na konec `.env`:

```ini
JWT_SECRET=change_me_in_production_at_least_32_characters
JWT_TTL=900
```

- [ ] **Step 7: Ověř container se sestaví**

```bash
docker compose run --rm app php bin/console debug:container --env=dev 2>&1 | head -20
```

Očekáváno: žádná chyba, výpis service kontejneru.

- [ ] **Step 8: Ověř Doctrine mapping**

```bash
docker compose run --rm app php bin/console doctrine:mapping:info
```

Očekáváno: výpis všech mappovaných entit včetně `Identity\User\Domain\User`, `Identity\Role\Domain\Role`, `Identity\Auth\Domain\RefreshToken`.

- [ ] **Step 9: Commit**

```bash
git add packages/identity/ config/ .env
git commit -m "feat(identity): Doctrine repositories, services.yaml, doctrine.yaml konfigurace"
```

---

## Task 9: Database migration + seed (admin user + super-admin role)

**Files:**
- Create: `migrations/VersionXXXXXXXXXXXXXX.php` (auto-generated)

- [ ] **Step 1: Vygeneruj migraci**

```bash
docker compose run --rm app php bin/console doctrine:migrations:diff
```

Očekáváno: nový soubor v `migrations/` s SQL pro `identity_users`, `identity_roles`, `identity_refresh_tokens`.

- [ ] **Step 2: Zkontroluj vygenerovanou migraci**

Ověř, že migrace obsahuje:

```sql
CREATE TABLE identity_roles (
    id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    permissions JSON NOT NULL,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX UNIQ_identity_roles_name ON identity_roles (name);

CREATE TABLE identity_users (
    id VARCHAR(36) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role_ids JSON NOT NULL,
    active BOOLEAN NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX UNIQ_identity_users_email ON identity_users (email);

CREATE TABLE identity_refresh_tokens (
    id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY (id)
);
```

- [ ] **Step 3: Přidej seed data do migrace**

Edituj vygenerovanou migraci — na konec `up()` metody přidej seed data:

```php
// Seed: super-admin role se všemi oprávněními
$roleId = '019577a0-0000-7000-8000-000000000001';
$this->addSql("INSERT INTO identity_roles (id, name, permissions) VALUES (:id, :name, :permissions)", [
    'id'          => $roleId,
    'name'        => 'super-admin',
    'permissions' => json_encode([
        'crm.contacts.view_customers',
        'crm.contacts.create_customer',
        'crm.contacts.update_customer',
        'identity.users.manage',
        'identity.users.view',
        'identity.roles.manage',
    ]),
]);

// Seed: admin user s heslem 'changeme'
$passwordHash = password_hash('changeme', PASSWORD_BCRYPT);
$userId = '019577a0-0000-7000-8000-000000000002';
$this->addSql("INSERT INTO identity_users (id, email, password_hash, first_name, last_name, role_ids, active, created_at) VALUES (:id, :email, :password, :first_name, :last_name, :role_ids, :active, :created_at)", [
    'id'         => $userId,
    'email'      => 'admin@erp.local',
    'password'   => $passwordHash,
    'first_name' => 'Admin',
    'last_name'  => 'ERP',
    'role_ids'   => json_encode([$roleId]),
    'active'     => true,
    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
]);
```

- [ ] **Step 4: Spusť migraci**

```bash
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction
```

Očekáváno: migrace proběhne úspěšně.

- [ ] **Step 5: Ověř seed data**

```bash
docker compose run --rm app php bin/console dbal:run-sql "SELECT id, email, first_name, last_name, active FROM identity_users"
```

Očekáváno: 1 řádek s admin@erp.local.

```bash
docker compose run --rm app php bin/console dbal:run-sql "SELECT id, name, permissions FROM identity_roles"
```

Očekáváno: 1 řádek se super-admin rolí.

- [ ] **Step 6: Commit**

```bash
git add migrations/
git commit -m "feat(identity): DB migrace — identity_users, identity_roles, identity_refresh_tokens + admin seed"
```

---

## Task 10: Role use cases (TDD) — CreateRole, UpdateRolePermissions, GetRoleList, GetRoleDetail

**Files:**
- Create: `packages/identity/tests/Role/Application/TestDoubles.php`
- Create: `packages/identity/tests/Role/Application/CreateRoleHandlerTest.php`
- Create: `packages/identity/tests/Role/Application/UpdateRolePermissionsHandlerTest.php`
- Create: `packages/identity/src/Role/Application/CreateRole/CreateRoleCommand.php`
- Create: `packages/identity/src/Role/Application/CreateRole/CreateRoleHandler.php`
- Create: `packages/identity/src/Role/Application/UpdateRolePermissions/UpdateRolePermissionsCommand.php`
- Create: `packages/identity/src/Role/Application/UpdateRolePermissions/UpdateRolePermissionsHandler.php`
- Create: `packages/identity/src/Role/Application/GetRoleList/GetRoleListQuery.php`
- Create: `packages/identity/src/Role/Application/GetRoleList/GetRoleListHandler.php`
- Create: `packages/identity/src/Role/Application/GetRoleList/RoleListItemDTO.php`
- Create: `packages/identity/src/Role/Application/GetRoleDetail/GetRoleDetailQuery.php`
- Create: `packages/identity/src/Role/Application/GetRoleDetail/GetRoleDetailHandler.php`
- Create: `packages/identity/src/Role/Application/GetRoleDetail/RoleDetailDTO.php`

- [ ] **Step 1: Vytvoř sdílené test helpery**

```php
<?php
// packages/identity/tests/Role/Application/TestDoubles.php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var Role[] */
    private array $roles = [];

    public function get(RoleId $id): Role
    {
        return $this->roles[$id->value()]
            ?? throw new RoleNotFoundException($id->value());
    }

    public function save(Role $role): void
    {
        $this->roles[$role->id()->value()] = $role;
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}

final class SpyEventBus implements EventBusInterface
{
    /** @var DomainEvent[] */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
```

- [ ] **Step 2: Napiš failing test pro CreateRole**

```php
<?php
// packages/identity/tests/Role/Application/CreateRoleHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Application\CreateRole\CreateRoleHandler;
use Identity\Role\Domain\RoleCreated;
use Identity\Role\Domain\RoleId;
use PHPUnit\Framework\TestCase;

final class CreateRoleHandlerTest extends TestCase
{
    private InMemoryRoleRepository $repository;
    private SpyEventBus $eventBus;
    private CreateRoleHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRoleRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new CreateRoleHandler($this->repository, $this->eventBus);
    }

    public function test_creates_role_and_persists(): void
    {
        $roleId = RoleId::generate()->value();
        $command = new CreateRoleCommand(
            roleId: $roleId,
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers', 'crm.contacts.create_customer'],
        );

        ($this->handler)($command);

        $role = $this->repository->get(RoleId::fromString($roleId));
        $this->assertSame('crm-manager', $role->name()->value());
        $this->assertSame(['crm.contacts.view_customers', 'crm.contacts.create_customer'], $role->permissions());
    }

    public function test_dispatches_role_created_event(): void
    {
        $command = new CreateRoleCommand(
            roleId: RoleId::generate()->value(),
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers'],
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RoleCreated::class, $this->eventBus->dispatched[0]);
    }
}
```

- [ ] **Step 3: Napiš failing test pro UpdateRolePermissions**

```php
<?php
// packages/identity/tests/Role/Application/UpdateRolePermissionsHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Role\Application;

use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Application\CreateRole\CreateRoleHandler;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsCommand;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsHandler;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleNotFoundException;
use Identity\Role\Domain\RolePermissionsUpdated;
use PHPUnit\Framework\TestCase;

final class UpdateRolePermissionsHandlerTest extends TestCase
{
    private InMemoryRoleRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingRoleId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryRoleRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingRoleId = RoleId::generate()->value();
        $createHandler = new CreateRoleHandler($this->repository, $this->eventBus);
        ($createHandler)(new CreateRoleCommand(
            roleId: $this->existingRoleId,
            name: 'crm-manager',
            permissions: ['crm.contacts.view_customers'],
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_updates_role_permissions(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: $this->existingRoleId,
            permissions: ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'],
        ));

        $role = $this->repository->get(RoleId::fromString($this->existingRoleId));
        $this->assertSame(
            ['crm.contacts.view_customers', 'crm.contacts.create_customer', 'crm.contacts.update_customer'],
            $role->permissions(),
        );
    }

    public function test_dispatches_role_permissions_updated_event(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: $this->existingRoleId,
            permissions: ['crm.contacts.view_customers'],
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RolePermissionsUpdated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_when_role_not_found(): void
    {
        $handler = new UpdateRolePermissionsHandler($this->repository, $this->eventBus);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new UpdateRolePermissionsCommand(
            roleId: '018e8f2a-0000-7000-8000-000000000099',
            permissions: [],
        ));
    }
}
```

- [ ] **Step 4: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Role/Application/ --testdox
```

Očekáváno: `Error: Class "Identity\Role\Application\CreateRole\CreateRoleCommand" not found`

- [ ] **Step 5: Implementuj CreateRoleCommand**

```php
<?php
// packages/identity/src/Role/Application/CreateRole/CreateRoleCommand.php
declare(strict_types=1);

namespace Identity\Role\Application\CreateRole;

final readonly class CreateRoleCommand
{
    public function __construct(
        public string $roleId,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
```

- [ ] **Step 6: Implementuj CreateRoleHandler**

```php
<?php
// packages/identity/src/Role/Application/CreateRole/CreateRoleHandler.php
declare(strict_types=1);

namespace Identity\Role\Application\CreateRole;

use Identity\Role\Domain\Role;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleName;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateRoleHandler
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateRoleCommand $command): void
    {
        $role = Role::create(
            RoleId::fromString($command->roleId),
            RoleName::fromString($command->name),
            $command->permissions,
        );

        $this->repository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 7: Implementuj UpdateRolePermissionsCommand**

```php
<?php
// packages/identity/src/Role/Application/UpdateRolePermissions/UpdateRolePermissionsCommand.php
declare(strict_types=1);

namespace Identity\Role\Application\UpdateRolePermissions;

final readonly class UpdateRolePermissionsCommand
{
    public function __construct(
        public string $roleId,
        /** @var string[] */
        public array $permissions,
    ) {}
}
```

- [ ] **Step 8: Implementuj UpdateRolePermissionsHandler**

```php
<?php
// packages/identity/src/Role/Application/UpdateRolePermissions/UpdateRolePermissionsHandler.php
declare(strict_types=1);

namespace Identity\Role\Application\UpdateRolePermissions;

use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateRolePermissionsHandler
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateRolePermissionsCommand $command): void
    {
        $role = $this->repository->get(RoleId::fromString($command->roleId));

        $role->updatePermissions($command->permissions);

        $this->repository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 9: Implementuj GetRoleList query use case**

```php
<?php
// packages/identity/src/Role/Application/GetRoleList/GetRoleListQuery.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

final readonly class GetRoleListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
```

```php
<?php
// packages/identity/src/Role/Application/GetRoleList/RoleListItemDTO.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

final readonly class RoleListItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
```

```php
<?php
// packages/identity/src/Role/Application/GetRoleList/GetRoleListHandler.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetRoleListHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return RoleListItemDTO[] */
    public function __invoke(GetRoleListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, name, permissions
             FROM identity_roles
             ORDER BY name ASC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new RoleListItemDTO(
                id: $row['id'],
                name: $row['name'],
                permissions: json_decode($row['permissions'], true),
            ),
            $rows,
        );
    }
}
```

- [ ] **Step 10: Implementuj GetRoleDetail query use case**

```php
<?php
// packages/identity/src/Role/Application/GetRoleDetail/GetRoleDetailQuery.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

final readonly class GetRoleDetailQuery
{
    public function __construct(
        public string $roleId,
    ) {}
}
```

```php
<?php
// packages/identity/src/Role/Application/GetRoleDetail/RoleDetailDTO.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

final readonly class RoleDetailDTO
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $permissions,
    ) {}
}
```

```php
<?php
// packages/identity/src/Role/Application/GetRoleDetail/GetRoleDetailHandler.php
declare(strict_types=1);

namespace Identity\Role\Application\GetRoleDetail;

use Identity\Role\Domain\RoleNotFoundException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetRoleDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetRoleDetailQuery $query): RoleDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, name, permissions
             FROM identity_roles
             WHERE id = :id',
            ['id' => $query->roleId],
        )->fetchAssociative();

        if ($row === false) {
            throw new RoleNotFoundException($query->roleId);
        }

        return new RoleDetailDTO(
            id: $row['id'],
            name: $row['name'],
            permissions: json_decode($row['permissions'], true),
        );
    }
}
```

- [ ] **Step 11: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Role/Application/ --testdox
```

Očekáváno:
```
Identity\Tests\Role\Application\CreateRoleHandlerTest
 ✔ Creates role and persists
 ✔ Dispatches role created event

Identity\Tests\Role\Application\UpdateRolePermissionsHandlerTest
 ✔ Updates role permissions
 ✔ Dispatches role permissions updated event
 ✔ Throws when role not found
```

- [ ] **Step 12: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): Role use cases — CreateRole, UpdateRolePermissions, GetRoleList, GetRoleDetail"
```

---

## Task 11: User use cases (TDD) — RegisterUser, UpdateUser, DeactivateUser, AssignRolesToUser, GetUserList, GetUserDetail

**Files:**
- Create: `packages/identity/tests/User/Application/TestDoubles.php`
- Create: `packages/identity/tests/User/Application/RegisterUserHandlerTest.php`
- Create: `packages/identity/tests/User/Application/UpdateUserHandlerTest.php`
- Create: `packages/identity/tests/User/Application/DeactivateUserHandlerTest.php`
- Create: `packages/identity/tests/User/Application/AssignRolesToUserHandlerTest.php`
- Create: `packages/identity/src/User/Application/RegisterUser/RegisterUserCommand.php`
- Create: `packages/identity/src/User/Application/RegisterUser/RegisterUserHandler.php`
- Create: `packages/identity/src/User/Application/UpdateUser/UpdateUserCommand.php`
- Create: `packages/identity/src/User/Application/UpdateUser/UpdateUserHandler.php`
- Create: `packages/identity/src/User/Application/DeactivateUser/DeactivateUserCommand.php`
- Create: `packages/identity/src/User/Application/DeactivateUser/DeactivateUserHandler.php`
- Create: `packages/identity/src/User/Application/AssignRolesToUser/AssignRolesToUserCommand.php`
- Create: `packages/identity/src/User/Application/AssignRolesToUser/AssignRolesToUserHandler.php`
- Create: `packages/identity/src/User/Application/GetUserList/GetUserListQuery.php`
- Create: `packages/identity/src/User/Application/GetUserList/GetUserListHandler.php`
- Create: `packages/identity/src/User/Application/GetUserList/UserListItemDTO.php`
- Create: `packages/identity/src/User/Application/GetUserDetail/GetUserDetailQuery.php`
- Create: `packages/identity/src/User/Application/GetUserDetail/GetUserDetailHandler.php`
- Create: `packages/identity/src/User/Application/GetUserDetail/UserDetailDTO.php`

- [ ] **Step 1: Vytvoř sdílené test helpery**

```php
<?php
// packages/identity/tests/User/Application/TestDoubles.php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class InMemoryUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];

    public function get(UserId $id): User
    {
        return $this->users[$id->value()]
            ?? throw new UserNotFoundException($id->value());
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}

final class SpyEventBus implements EventBusInterface
{
    /** @var DomainEvent[] */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
```

- [ ] **Step 2: Napiš failing test pro RegisterUser**

```php
<?php
// packages/identity/tests/User/Application/RegisterUserHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\UserCreated;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new RegisterUserHandler($this->repository, $this->eventBus);
    }

    public function test_registers_user_and_persists(): void
    {
        $userId = UserId::generate()->value();
        $command = new RegisterUserCommand(
            userId: $userId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $user = $this->repository->get(UserId::fromString($userId));
        $this->assertSame('jan@firma.cz', $user->email()->value());
        $this->assertSame('Jan', $user->name()->firstName());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->password()->verify('SecurePass123!'));
    }

    public function test_dispatches_user_created_event(): void
    {
        $command = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserCreated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_on_duplicate_email(): void
    {
        $command1 = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        );
        ($this->handler)($command1);

        $command2 = new RegisterUserCommand(
            userId: UserId::generate()->value(),
            email: 'jan@firma.cz',
            password: 'AnotherPass123!',
            firstName: 'Jiný',
            lastName: 'Jan',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already registered');
        ($this->handler)($command2);
    }
}
```

- [ ] **Step 3: Napiš failing test pro UpdateUser**

```php
<?php
// packages/identity/tests/User/Application/UpdateUserHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Application\UpdateUser\UpdateUserCommand;
use Identity\User\Application\UpdateUser\UpdateUserHandler;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserUpdated;
use PHPUnit\Framework\TestCase;

final class UpdateUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingUserId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingUserId = UserId::generate()->value();
        $registerHandler = new RegisterUserHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterUserCommand(
            userId: $this->existingUserId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_updates_user_email_and_name(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateUserCommand(
            userId: $this->existingUserId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $this->assertSame('petr@firma.cz', $user->email()->value());
        $this->assertSame('Petr', $user->name()->firstName());
    }

    public function test_dispatches_user_updated_event(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateUserCommand(
            userId: $this->existingUserId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserUpdated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_when_user_not_found(): void
    {
        $handler = new UpdateUserHandler($this->repository, $this->eventBus);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new UpdateUserCommand(
            userId: '018e8f2a-0000-7000-8000-000000000099',
            email: 'x@x.cz',
            firstName: 'X',
            lastName: 'Y',
        ));
    }
}
```

- [ ] **Step 4: Napiš failing test pro DeactivateUser**

```php
<?php
// packages/identity/tests/User/Application/DeactivateUserHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\User\Application\DeactivateUser\DeactivateUserCommand;
use Identity\User\Application\DeactivateUser\DeactivateUserHandler;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\UserDeactivated;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class DeactivateUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingUserId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingUserId = UserId::generate()->value();
        $registerHandler = new RegisterUserHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterUserCommand(
            userId: $this->existingUserId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_deactivates_user(): void
    {
        $handler = new DeactivateUserHandler($this->repository, $this->eventBus);
        ($handler)(new DeactivateUserCommand(userId: $this->existingUserId));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $this->assertFalse($user->isActive());
    }

    public function test_dispatches_user_deactivated_event(): void
    {
        $handler = new DeactivateUserHandler($this->repository, $this->eventBus);
        ($handler)(new DeactivateUserCommand(userId: $this->existingUserId));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(UserDeactivated::class, $this->eventBus->dispatched[0]);
    }
}
```

- [ ] **Step 5: Napiš failing test pro AssignRolesToUser**

```php
<?php
// packages/identity/tests/User/Application/AssignRolesToUserHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\User\Application;

use Identity\Role\Domain\RoleId;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserHandler;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Application\RegisterUser\RegisterUserHandler;
use Identity\User\Domain\RoleAssignedToUser;
use Identity\User\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class AssignRolesToUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingUserId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingUserId = UserId::generate()->value();
        $registerHandler = new RegisterUserHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterUserCommand(
            userId: $this->existingUserId,
            email: 'jan@firma.cz',
            password: 'SecurePass123!',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_assigns_roles_to_user(): void
    {
        $roleId1 = RoleId::generate()->value();
        $roleId2 = RoleId::generate()->value();

        $handler = new AssignRolesToUserHandler($this->repository, $this->eventBus);
        ($handler)(new AssignRolesToUserCommand(
            userId: $this->existingUserId,
            roleIds: [$roleId1, $roleId2],
        ));

        $user = $this->repository->get(UserId::fromString($this->existingUserId));
        $roleIds = $user->roleIds();
        $this->assertCount(2, $roleIds);
        $this->assertSame($roleId1, $roleIds[0]->value());
        $this->assertSame($roleId2, $roleIds[1]->value());
    }

    public function test_dispatches_role_assigned_event(): void
    {
        $handler = new AssignRolesToUserHandler($this->repository, $this->eventBus);
        ($handler)(new AssignRolesToUserCommand(
            userId: $this->existingUserId,
            roleIds: [RoleId::generate()->value()],
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(RoleAssignedToUser::class, $this->eventBus->dispatched[0]);
    }
}
```

- [ ] **Step 6: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/User/Application/ --testdox
```

Očekáváno: `Error: Class "Identity\User\Application\RegisterUser\RegisterUserCommand" not found`

- [ ] **Step 7: Implementuj RegisterUserCommand**

```php
<?php
// packages/identity/src/User/Application/RegisterUser/RegisterUserCommand.php
declare(strict_types=1);

namespace Identity\User\Application\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
    ) {}
}
```

- [ ] **Step 8: Implementuj RegisterUserHandler**

```php
<?php
// packages/identity/src/User/Application/RegisterUser/RegisterUserHandler.php
declare(strict_types=1);

namespace Identity\User\Application\RegisterUser;

use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserPassword;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = UserEmail::fromString($command->email);

        if ($this->repository->findByEmail($email) !== null) {
            throw new \DomainException("User with email '{$command->email}' is already registered");
        }

        $user = User::create(
            UserId::fromString($command->userId),
            $email,
            UserPassword::fromPlaintext($command->password),
            UserName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 9: Implementuj UpdateUserCommand + Handler**

```php
<?php
// packages/identity/src/User/Application/UpdateUser/UpdateUserCommand.php
declare(strict_types=1);

namespace Identity\User\Application\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/UpdateUser/UpdateUserHandler.php
declare(strict_types=1);

namespace Identity\User\Application\UpdateUser;

use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserName;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));

        $user->update(
            UserEmail::fromString($command->email),
            UserName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 10: Implementuj DeactivateUserCommand + Handler**

```php
<?php
// packages/identity/src/User/Application/DeactivateUser/DeactivateUserCommand.php
declare(strict_types=1);

namespace Identity\User\Application\DeactivateUser;

final readonly class DeactivateUserCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/DeactivateUser/DeactivateUserHandler.php
declare(strict_types=1);

namespace Identity\User\Application\DeactivateUser;

use Identity\User\Domain\UserId;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeactivateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeactivateUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));

        $user->deactivate();

        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 11: Implementuj AssignRolesToUserCommand + Handler**

```php
<?php
// packages/identity/src/User/Application/AssignRolesToUser/AssignRolesToUserCommand.php
declare(strict_types=1);

namespace Identity\User\Application\AssignRolesToUser;

final readonly class AssignRolesToUserCommand
{
    public function __construct(
        public string $userId,
        /** @var string[] UUID strings */
        public array $roleIds,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/AssignRolesToUser/AssignRolesToUserHandler.php
declare(strict_types=1);

namespace Identity\User\Application\AssignRolesToUser;

use Identity\Role\Domain\RoleId;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AssignRolesToUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(AssignRolesToUserCommand $command): void
    {
        $user = $this->repository->get(UserId::fromString($command->userId));

        $roleIds = array_map(
            fn(string $id) => RoleId::fromString($id),
            $command->roleIds,
        );

        $user->assignRoles($roleIds);

        $this->repository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 12: Implementuj GetUserList query use case**

```php
<?php
// packages/identity/src/User/Application/GetUserList/GetUserListQuery.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

final readonly class GetUserListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/GetUserList/UserListItemDTO.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

final readonly class UserListItemDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        /** @var string[] */
        public array $roleIds,
        public bool $active,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/GetUserList/GetUserListHandler.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserListHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return UserListItemDTO[] */
    public function __invoke(GetUserListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, role_ids, active
             FROM identity_users
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new UserListItemDTO(
                id: $row['id'],
                email: $row['email'],
                fullName: $row['first_name'] . ' ' . $row['last_name'],
                roleIds: json_decode($row['role_ids'], true),
                active: (bool) $row['active'],
            ),
            $rows,
        );
    }
}
```

- [ ] **Step 13: Implementuj GetUserDetail query use case**

```php
<?php
// packages/identity/src/User/Application/GetUserDetail/GetUserDetailQuery.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

final readonly class GetUserDetailQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/GetUserDetail/UserDetailDTO.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

final readonly class UserDetailDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        /** @var string[] */
        public array $roleIds,
        public bool $active,
        public string $createdAt,
    ) {}
}
```

```php
<?php
// packages/identity/src/User/Application/GetUserDetail/GetUserDetailHandler.php
declare(strict_types=1);

namespace Identity\User\Application\GetUserDetail;

use Identity\User\Domain\UserNotFoundException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetUserDetailQuery $query): UserDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, role_ids, active, created_at
             FROM identity_users
             WHERE id = :id',
            ['id' => $query->userId],
        )->fetchAssociative();

        if ($row === false) {
            throw new UserNotFoundException($query->userId);
        }

        return new UserDetailDTO(
            id: $row['id'],
            email: $row['email'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            roleIds: json_decode($row['role_ids'], true),
            active: (bool) $row['active'],
            createdAt: $row['created_at'],
        );
    }
}
```

- [ ] **Step 14: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/User/Application/ --testdox
```

Očekáváno:
```
Identity\Tests\User\Application\RegisterUserHandlerTest
 ✔ Registers user and persists
 ✔ Dispatches user created event
 ✔ Throws on duplicate email

Identity\Tests\User\Application\UpdateUserHandlerTest
 ✔ Updates user email and name
 ✔ Dispatches user updated event
 ✔ Throws when user not found

Identity\Tests\User\Application\DeactivateUserHandlerTest
 ✔ Deactivates user
 ✔ Dispatches user deactivated event

Identity\Tests\User\Application\AssignRolesToUserHandlerTest
 ✔ Assigns roles to user
 ✔ Dispatches role assigned event
```

- [ ] **Step 15: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): User use cases — RegisterUser, UpdateUser, DeactivateUser, AssignRolesToUser, GetUserList, GetUserDetail"
```

---

## Task 12: Auth use cases (TDD) — Login, RefreshAccessToken, Logout, GetCurrentUser

**Files:**
- Create: `packages/identity/tests/Auth/Application/LoginHandlerTest.php`
- Create: `packages/identity/tests/Auth/Application/RefreshAccessTokenHandlerTest.php`
- Create: `packages/identity/tests/Auth/Application/LogoutHandlerTest.php`
- Create: `packages/identity/tests/Auth/Application/TestDoubles.php`
- Create: `packages/identity/src/Auth/Application/Login/LoginQuery.php`
- Create: `packages/identity/src/Auth/Application/Login/LoginHandler.php`
- Create: `packages/identity/src/Auth/Application/Login/LoginResultDTO.php`
- Create: `packages/identity/src/Auth/Application/RefreshAccessToken/RefreshAccessTokenQuery.php`
- Create: `packages/identity/src/Auth/Application/RefreshAccessToken/RefreshAccessTokenHandler.php`
- Create: `packages/identity/src/Auth/Application/RefreshAccessToken/RefreshResultDTO.php`
- Create: `packages/identity/src/Auth/Application/Logout/LogoutCommand.php`
- Create: `packages/identity/src/Auth/Application/Logout/LogoutHandler.php`
- Create: `packages/identity/src/Auth/Application/GetCurrentUser/GetCurrentUserQuery.php`
- Create: `packages/identity/src/Auth/Application/GetCurrentUser/GetCurrentUserHandler.php`
- Create: `packages/identity/src/Auth/Application/GetCurrentUser/CurrentUserDTO.php`

- [ ] **Step 1: Vytvoř Auth test helpery**

```php
<?php
// packages/identity/tests/Auth/Application/TestDoubles.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\User\Domain\User;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;

final class InMemoryRefreshTokenRepository implements RefreshTokenRepository
{
    /** @var RefreshToken[] keyed by tokenHash */
    private array $tokens = [];

    public function findByTokenHash(string $hash): ?RefreshToken
    {
        return $this->tokens[$hash] ?? null;
    }

    public function save(RefreshToken $token): void
    {
        $this->tokens[$token->tokenHash()] = $token;
    }
}

final class InMemoryUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];

    public function get(UserId $id): User
    {
        return $this->users[$id->value()]
            ?? throw new UserNotFoundException($id->value());
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
```

- [ ] **Step 2: Napiš failing test pro LoginHandler**

```php
<?php
// packages/identity/tests/Auth/Application/LoginHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\Login\LoginHandler;
use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Application\Login\LoginResultDTO;
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

        // Seed role
        $this->roleId = RoleId::generate()->value();
        $role = Role::create(
            RoleId::fromString($this->roleId),
            RoleName::fromString('crm-manager'),
            ['crm.contacts.view_customers', 'crm.contacts.create_customer'],
        );
        $this->roleRepository->save($role);

        // Seed user with role
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

        // Access token contains correct permissions
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
```

- [ ] **Step 3: Napiš failing test pro RefreshAccessTokenHandler**

```php
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

        // Seed role + user
        $roleId = RoleId::generate();
        $role = Role::create($roleId, RoleName::fromString('crm-manager'), ['crm.contacts.view_customers']);
        $this->roleRepository->save($role);

        $userId = UserId::generate();
        $user = User::create($userId, UserEmail::fromString('jan@firma.cz'), UserPassword::fromPlaintext('SecurePass123!'), UserName::fromParts('Jan', 'Novák'));
        $user->assignRoles([$roleId]);
        $this->userRepository->save($user);

        // Login to get refresh token
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
```

- [ ] **Step 4: Napiš failing test pro LogoutHandler**

```php
<?php
// packages/identity/tests/Auth/Application/LogoutHandlerTest.php
declare(strict_types=1);

namespace Identity\Tests\Auth\Application;

use Identity\Auth\Application\Login\LoginHandler;
use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Application\Logout\LogoutCommand;
use Identity\Auth\Application\Logout\LogoutHandler;
use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenHandler;
use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenQuery;
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

final class LogoutHandlerTest extends TestCase
{
    private InMemoryRefreshTokenRepository $refreshTokenRepository;
    private string $refreshToken;

    protected function setUp(): void
    {
        $userRepository         = new InMemoryUserRepository();
        $this->refreshTokenRepository = new InMemoryRefreshTokenRepository();
        $roleRepository         = new \Identity\Tests\Role\Application\InMemoryRoleRepository();
        $jwtService             = new FirebaseJwtTokenService('test-secret-key-at-least-32-chars-long!!', 900);

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

        // Token hash should be found but marked as revoked
        $tokenHash = hash('sha256', $this->refreshToken);
        $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);
        $this->assertNotNull($token);
        $this->assertFalse($token->isValid());
    }
}
```

- [ ] **Step 5: Spusť — ověř FAIL**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Application/ --testdox
```

Očekáváno: `Error: Class "Identity\Auth\Application\Login\LoginQuery" not found`

- [ ] **Step 6: Implementuj LoginQuery + LoginResultDTO**

```php
<?php
// packages/identity/src/Auth/Application/Login/LoginQuery.php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

final readonly class LoginQuery
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
```

```php
<?php
// packages/identity/src/Auth/Application/Login/LoginResultDTO.php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

final readonly class LoginResultDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {}
}
```

- [ ] **Step 7: Implementuj LoginHandler**

```php
<?php
// packages/identity/src/Auth/Application/Login/LoginHandler.php
declare(strict_types=1);

namespace Identity\Auth\Application\Login;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenId;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserEmail;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class LoginHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JwtTokenService $jwtService,
    ) {}

    public function __invoke(LoginQuery $query): LoginResultDTO
    {
        $user = $this->userRepository->findByEmail(UserEmail::fromString($query->email));

        if ($user === null || !$user->password()->verify($query->password)) {
            throw new \DomainException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new \DomainException('User account is deactivated');
        }

        // Collect permissions from all assigned roles
        $permissions = [];
        foreach ($user->roleIds() as $roleId) {
            try {
                $role = $this->roleRepository->get($roleId);
                $permissions = array_merge($permissions, $role->permissions());
            } catch (\DomainException) {
                // Skip deleted roles
            }
        }
        $permissions = array_values(array_unique($permissions));

        // Issue access token
        $accessToken = $this->jwtService->issueAccessToken($user->id(), $permissions);

        // Issue refresh token
        $refreshTokenPlaintext = $this->jwtService->generateRefreshToken();
        $refreshTokenHash = hash('sha256', $refreshTokenPlaintext);
        $refreshToken = new RefreshToken(
            RefreshTokenId::generate(),
            $user->id(),
            $refreshTokenHash,
            new \DateTimeImmutable('+30 days'),
        );
        $this->refreshTokenRepository->save($refreshToken);

        return new LoginResultDTO(
            accessToken: $accessToken,
            refreshToken: $refreshTokenPlaintext,
            expiresIn: 900,
        );
    }
}
```

- [ ] **Step 8: Implementuj RefreshAccessTokenQuery + RefreshResultDTO**

```php
<?php
// packages/identity/src/Auth/Application/RefreshAccessToken/RefreshAccessTokenQuery.php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

final readonly class RefreshAccessTokenQuery
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
```

```php
<?php
// packages/identity/src/Auth/Application/RefreshAccessToken/RefreshResultDTO.php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

final readonly class RefreshResultDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {}
}
```

- [ ] **Step 9: Implementuj RefreshAccessTokenHandler**

```php
<?php
// packages/identity/src/Auth/Application/RefreshAccessToken/RefreshAccessTokenHandler.php
declare(strict_types=1);

namespace Identity\Auth\Application\RefreshAccessToken;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Identity\Auth\Domain\RefreshToken;
use Identity\Auth\Domain\RefreshTokenId;
use Identity\Auth\Domain\RefreshTokenRepository;
use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class RefreshAccessTokenHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JwtTokenService $jwtService,
    ) {}

    public function __invoke(RefreshAccessTokenQuery $query): RefreshResultDTO
    {
        $tokenHash = hash('sha256', $query->refreshToken);
        $existingToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($existingToken === null || !$existingToken->isValid()) {
            throw new InvalidTokenException('Invalid or expired refresh token');
        }

        // Revoke old token (rotation)
        $existingToken->revoke();
        $this->refreshTokenRepository->save($existingToken);

        // Load user + permissions
        $user = $this->userRepository->get($existingToken->userId());

        $permissions = [];
        foreach ($user->roleIds() as $roleId) {
            try {
                $role = $this->roleRepository->get($roleId);
                $permissions = array_merge($permissions, $role->permissions());
            } catch (\DomainException) {
                // Skip deleted roles
            }
        }
        $permissions = array_values(array_unique($permissions));

        // Issue new access token
        $accessToken = $this->jwtService->issueAccessToken($user->id(), $permissions);

        // Issue new refresh token (rotation)
        $newRefreshPlaintext = $this->jwtService->generateRefreshToken();
        $newRefreshHash = hash('sha256', $newRefreshPlaintext);
        $newRefreshToken = new RefreshToken(
            RefreshTokenId::generate(),
            $user->id(),
            $newRefreshHash,
            new \DateTimeImmutable('+30 days'),
        );
        $this->refreshTokenRepository->save($newRefreshToken);

        return new RefreshResultDTO(
            accessToken: $accessToken,
            refreshToken: $newRefreshPlaintext,
            expiresIn: 900,
        );
    }
}
```

- [ ] **Step 10: Implementuj LogoutCommand + LogoutHandler**

```php
<?php
// packages/identity/src/Auth/Application/Logout/LogoutCommand.php
declare(strict_types=1);

namespace Identity\Auth\Application\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
```

```php
<?php
// packages/identity/src/Auth/Application/Logout/LogoutHandler.php
declare(strict_types=1);

namespace Identity\Auth\Application\Logout;

use Identity\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class LogoutHandler
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {}

    public function __invoke(LogoutCommand $command): void
    {
        $tokenHash = hash('sha256', $command->refreshToken);
        $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($token !== null && $token->isValid()) {
            $token->revoke();
            $this->refreshTokenRepository->save($token);
        }
    }
}
```

- [ ] **Step 11: Implementuj GetCurrentUser use case**

```php
<?php
// packages/identity/src/Auth/Application/GetCurrentUser/GetCurrentUserQuery.php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

final readonly class GetCurrentUserQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
```

```php
<?php
// packages/identity/src/Auth/Application/GetCurrentUser/CurrentUserDTO.php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

final readonly class CurrentUserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        /** @var string[] */
        public array $permissions,
    ) {}
}
```

```php
<?php
// packages/identity/src/Auth/Application/GetCurrentUser/GetCurrentUserHandler.php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

use Doctrine\DBAL\Connection;
use Identity\Role\Domain\RoleId;
use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserNotFoundException;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCurrentUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
    ) {}

    public function __invoke(GetCurrentUserQuery $query): CurrentUserDTO
    {
        $user = $this->userRepository->get(UserId::fromString($query->userId));

        $permissions = [];
        foreach ($user->roleIds() as $roleId) {
            try {
                $role = $this->roleRepository->get($roleId);
                $permissions = array_merge($permissions, $role->permissions());
            } catch (\DomainException) {
                // Skip deleted roles
            }
        }
        $permissions = array_values(array_unique($permissions));

        return new CurrentUserDTO(
            id: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->name()->firstName(),
            lastName: $user->name()->lastName(),
            permissions: $permissions,
        );
    }
}
```

- [ ] **Step 12: Spusť — ověř PASS**

```bash
cd packages/identity && ./vendor/bin/phpunit tests/Auth/Application/ --testdox
```

Očekáváno:
```
Identity\Tests\Auth\Application\JwtTokenServiceTest
 ✔ Issues and validates access token
 ✔ Validates token contains correct expiry
 ✔ Throws on invalid token
 ✔ Throws on expired token
 ✔ Throws on wrong secret
 ✔ Generates refresh token

Identity\Tests\Auth\Application\LoginHandlerTest
 ✔ Login returns tokens
 ✔ Login throws on wrong email
 ✔ Login throws on wrong password
 ✔ Login throws when user inactive

Identity\Tests\Auth\Application\RefreshAccessTokenHandlerTest
 ✔ Refresh returns new tokens
 ✔ Throws on invalid refresh token

Identity\Tests\Auth\Application\LogoutHandlerTest
 ✔ Logout revokes refresh token
```

- [ ] **Step 13: Spusť celou test suite**

```bash
cd packages/identity && ./vendor/bin/phpunit --testdox
```

Očekáváno: všechny testy zelené.

- [ ] **Step 14: Commit**

```bash
git add packages/identity/
git commit -m "feat(identity): Auth use cases — Login, RefreshAccessToken, Logout, GetCurrentUser"
```

---

## Task 13: Symfony Security — SecurityUser, JwtAuthenticator + update security.yaml

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Security/SecurityUser.php`
- Create: `packages/identity/src/Auth/Infrastructure/Security/IdentityUserProvider.php`
- Create: `packages/identity/src/Auth/Infrastructure/Security/JwtAuthenticator.php`
- Modify: `config/packages/security.yaml`

- [ ] **Step 1: Implementuj SecurityUser**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Security/SecurityUser.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface
{
    /**
     * @param string[] $roles ROLE_* formatted strings
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly array $roles,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->userId;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }
}
```

- [ ] **Step 2: Implementuj IdentityUserProvider**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Security/IdentityUserProvider.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final class IdentityUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $row = $this->connection->executeQuery(
            'SELECT u.id, u.email, u.role_ids
             FROM identity_users u
             WHERE u.id = :id AND u.active = true',
            ['id' => $identifier],
        )->fetchAssociative();

        if ($row === false) {
            throw new UserNotFoundException("User '$identifier' not found or inactive");
        }

        // Load permissions from roles
        $roleIds = json_decode($row['role_ids'], true);
        $permissions = [];

        if (!empty($roleIds)) {
            $roleRows = $this->connection->executeQuery(
                'SELECT permissions FROM identity_roles WHERE id IN (?)',
                [$roleIds],
                [\Doctrine\DBAL\ArrayParameterType::STRING],
            )->fetchAllAssociative();

            foreach ($roleRows as $roleRow) {
                $rolePermissions = json_decode($roleRow['permissions'], true);
                $permissions = array_merge($permissions, $rolePermissions);
            }
        }

        $permissions = array_unique($permissions);

        // Convert permissions to ROLE_* format
        $roles = array_map(
            fn(string $p) => 'ROLE_' . strtoupper(str_replace('.', '_', $p)),
            $permissions,
        );

        return new SecurityUser(
            userId: $row['id'],
            email: $row['email'],
            roles: array_values($roles),
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }
}
```

- [ ] **Step 3: Implementuj JwtAuthenticator**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Security/JwtAuthenticator.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Identity\Auth\Application\JwtTokenService;
use Identity\Auth\Domain\InvalidTokenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JwtTokenService $jwtService,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        try {
            $payload = $this->jwtService->validateAccessToken($token);
        } catch (InvalidTokenException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        $userId = $payload['sub'] ?? '';
        $permissions = $payload['permissions'] ?? [];

        // Convert permissions to ROLE_* format for SecurityUser
        $roles = array_map(
            fn(string $p) => 'ROLE_' . strtoupper(str_replace('.', '_', $p)),
            $permissions,
        );

        return new SelfValidatingPassport(
            new UserBadge($userId, fn(string $id) => new SecurityUser(
                userId: $id,
                email: '', // Not needed for auth, loaded lazily if needed
                roles: array_values($roles),
            )),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Let request continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
```

- [ ] **Step 4: Nahraď config/packages/security.yaml**

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        identity:
            id: Identity\Auth\Infrastructure\Security\IdentityUserProvider

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern: ^/api/identity/commands/login$
            security: false
        refresh:
            pattern: ^/api/identity/commands/refresh-token$
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

- [ ] **Step 5: Ověř container se sestaví**

```bash
docker compose run --rm app php bin/console debug:container --env=dev 2>&1 | head -20
```

Očekáváno: žádná chyba.

- [ ] **Step 6: Ověř security konfiguraci**

```bash
docker compose run --rm app php bin/console debug:firewall
```

Očekáváno: firewally `dev`, `login`, `refresh`, `api` zobrazeny.

- [ ] **Step 7: Commit**

```bash
git add packages/identity/ config/packages/security.yaml
git commit -m "feat(identity): SecurityUser, IdentityUserProvider, JwtAuthenticator + security.yaml"
```

---

## Task 14: HTTP Controllers + routes

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Http/LoginController.php`
- Create: `packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php`
- Create: `packages/identity/src/Auth/Infrastructure/Http/LogoutController.php`
- Create: `packages/identity/src/Auth/Infrastructure/Http/GetCurrentUserController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/RegisterUserController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/UpdateUserController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/DeactivateUserController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/GetUserListController.php`
- Create: `packages/identity/src/User/Infrastructure/Http/GetUserDetailController.php`
- Create: `packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php`
- Create: `packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php`
- Create: `packages/identity/src/Role/Infrastructure/Http/GetRoleListController.php`
- Create: `packages/identity/src/Role/Infrastructure/Http/GetRoleDetailController.php`
- Create: `config/routes/identity.yaml`

- [ ] **Step 1: Implementuj LoginController**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Http/LoginController.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Login\LoginQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        /** @var \Identity\Auth\Application\Login\LoginResultDTO $result */
        $result = $this->queryBus->dispatch(new LoginQuery(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
```

- [ ] **Step 2: Implementuj RefreshAccessTokenController**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/refresh-token', methods: ['POST'])]
final class RefreshAccessTokenController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        /** @var \Identity\Auth\Application\RefreshAccessToken\RefreshResultDTO $result */
        $result = $this->queryBus->dispatch(new RefreshAccessTokenQuery(
            refreshToken: (string) ($data['refresh_token'] ?? ''),
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
```

- [ ] **Step 3: Implementuj LogoutController**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Http/LogoutController.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Logout\LogoutCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/logout', methods: ['POST'])]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new LogoutCommand(
            refreshToken: (string) ($data['refresh_token'] ?? ''),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 4: Implementuj GetCurrentUserController**

```php
<?php
// packages/identity/src/Auth/Infrastructure/Http/GetCurrentUserController.php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\GetCurrentUser\GetCurrentUserQuery;
use Identity\Auth\Infrastructure\Security\SecurityUser;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/me', methods: ['GET'])]
final class GetCurrentUserController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        /** @var \Identity\Auth\Application\GetCurrentUser\CurrentUserDTO $result */
        $result = $this->queryBus->dispatch(new GetCurrentUserQuery(
            userId: $securityUser->getUserIdentifier(),
        ));

        return new JsonResponse([
            'id'          => $result->id,
            'email'       => $result->email,
            'first_name'  => $result->firstName,
            'last_name'   => $result->lastName,
            'permissions' => $result->permissions,
        ]);
    }
}
```

- [ ] **Step 5: Implementuj User controllers**

```php
<?php
// packages/identity/src/User/Infrastructure/Http/RegisterUserController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Domain\UserId;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/register-user', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class RegisterUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $userId = UserId::generate()->value();

        $this->commandBus->dispatch(new RegisterUserCommand(
            userId: $userId,
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(['id' => $userId], Response::HTTP_CREATED);
    }
}
```

```php
<?php
// packages/identity/src/User/Infrastructure/Http/UpdateUserController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\UpdateUser\UpdateUserCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/update-user/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new UpdateUserCommand(
            userId: $id,
            email: (string) ($data['email'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

```php
<?php
// packages/identity/src/User/Infrastructure/Http/DeactivateUserController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\DeactivateUser\DeactivateUserCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/deactivate-user/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class DeactivateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeactivateUserCommand(userId: $id));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

```php
<?php
// packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/assign-roles/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class AssignRolesToUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new AssignRolesToUserCommand(
            userId: $id,
            roleIds: (array) ($data['role_ids'] ?? []),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

```php
<?php
// packages/identity/src/User/Infrastructure/Http/GetUserListController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\GetUserList\GetUserListQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users', methods: ['GET'])]
#[IsGranted(IdentityPermission::VIEW_USERS->value)]
final class GetUserListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetUserListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'       => $dto->id,
                'email'    => $dto->email,
                'name'     => $dto->fullName,
                'role_ids' => $dto->roleIds,
                'active'   => $dto->active,
            ],
            $result,
        ));
    }
}
```

```php
<?php
// packages/identity/src/User/Infrastructure/Http/GetUserDetailController.php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\GetUserDetail\GetUserDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/{id}', methods: ['GET'])]
#[IsGranted(IdentityPermission::VIEW_USERS->value)]
final class GetUserDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        /** @var \Identity\User\Application\GetUserDetail\UserDetailDTO $result */
        $result = $this->queryBus->dispatch(new GetUserDetailQuery(userId: $id));

        return new JsonResponse([
            'id'         => $result->id,
            'email'      => $result->email,
            'first_name' => $result->firstName,
            'last_name'  => $result->lastName,
            'role_ids'   => $result->roleIds,
            'active'     => $result->active,
            'created_at' => $result->createdAt,
        ]);
    }
}
```

- [ ] **Step 6: Implementuj Role controllers**

```php
<?php
// packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Domain\RoleId;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/create-role', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class CreateRoleController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $roleId = RoleId::generate()->value();

        $this->commandBus->dispatch(new CreateRoleCommand(
            roleId: $roleId,
            name: (string) ($data['name'] ?? ''),
            permissions: (array) ($data['permissions'] ?? []),
        ));

        return new JsonResponse(['id' => $roleId], Response::HTTP_CREATED);
    }
}
```

```php
<?php
// packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/update-role-permissions/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class UpdateRolePermissionsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new UpdateRolePermissionsCommand(
            roleId: $id,
            permissions: (array) ($data['permissions'] ?? []),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

```php
<?php
// packages/identity/src/Role/Infrastructure/Http/GetRoleListController.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\GetRoleList\GetRoleListQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles', methods: ['GET'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class GetRoleListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetRoleListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($dto) => [
                'id'          => $dto->id,
                'name'        => $dto->name,
                'permissions' => $dto->permissions,
            ],
            $result,
        ));
    }
}
```

```php
<?php
// packages/identity/src/Role/Infrastructure/Http/GetRoleDetailController.php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\GetRoleDetail\GetRoleDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/{id}', methods: ['GET'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class GetRoleDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        /** @var \Identity\Role\Application\GetRoleDetail\RoleDetailDTO $result */
        $result = $this->queryBus->dispatch(new GetRoleDetailQuery(roleId: $id));

        return new JsonResponse([
            'id'          => $result->id,
            'name'        => $result->name,
            'permissions' => $result->permissions,
        ]);
    }
}
```

- [ ] **Step 7: Vytvoř config/routes/identity.yaml**

```yaml
# config/routes/identity.yaml
identity_auth:
    resource:
        path: '../../packages/identity/src/Auth/Infrastructure/Http/'
        namespace: 'Identity\Auth\Infrastructure\Http'
    type: attribute

identity_users:
    resource:
        path: '../../packages/identity/src/User/Infrastructure/Http/'
        namespace: 'Identity\User\Infrastructure\Http'
    type: attribute

identity_roles:
    resource:
        path: '../../packages/identity/src/Role/Infrastructure/Http/'
        namespace: 'Identity\Role\Infrastructure\Http'
    type: attribute
```

- [ ] **Step 8: Ověř routy**

```bash
docker compose run --rm app php bin/console debug:router | grep identity
```

Očekáváno:
```
  identity_auth_logincontroller__invoke           POST     /api/identity/commands/login
  identity_auth_refreshaccesstokencontroller__invoke POST  /api/identity/commands/refresh-token
  identity_auth_logoutcontroller__invoke           POST    /api/identity/commands/logout
  identity_auth_getcurrentusercontroller__invoke   GET     /api/identity/me
  identity_users_registerusercontroller__invoke    POST    /api/identity/users/commands/register-user
  identity_users_updateusercontroller__invoke      PUT     /api/identity/users/commands/update-user/{id}
  identity_users_deactivateusercontroller__invoke  POST    /api/identity/users/commands/deactivate-user/{id}
  identity_users_assignrolestousercontroller__invoke POST  /api/identity/users/commands/assign-roles/{id}
  identity_users_getuserlistcontroller__invoke     GET     /api/identity/users
  identity_users_getuserdetailcontroller__invoke   GET     /api/identity/users/{id}
  identity_roles_createrolecontroller__invoke      POST    /api/identity/roles/commands/create-role
  identity_roles_updaterolepermissionscontroller__invoke PUT /api/identity/roles/commands/update-role-permissions/{id}
  identity_roles_getrolelistcontroller__invoke     GET     /api/identity/roles
  identity_roles_getroledetailcontroller__invoke   GET     /api/identity/roles/{id}
```

- [ ] **Step 9: Commit**

```bash
git add packages/identity/ config/routes/identity.yaml
git commit -m "feat(identity): HTTP controllers + routes pro Auth, Users, Roles"
```

---

## Task 15: E2E tests — login -> JWT -> CRM endpoint

**Files:**
- Create: `tests/Identity/E2e/AuthFlowTest.php`

- [ ] **Step 1: Napiš E2E test**

```php
<?php
// tests/Identity/E2e/AuthFlowTest.php
declare(strict_types=1);

namespace App\Tests\Identity\E2e;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthFlowTest extends WebTestCase
{
    public function test_login_returns_tokens(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertSame(900, $data['expires_in']);
    }

    public function test_login_with_wrong_password_returns_error(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'wrong-password',
        ]));

        $this->assertResponseStatusCodeSame(500); // DomainException handler
    }

    public function test_jwt_grants_access_to_crm_endpoint(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];

        // Access CRM endpoint with JWT
        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function test_missing_token_returns_401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers');

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_invalid_token_returns_401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_refresh_token_rotation(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $refreshToken = $loginData['refresh_token'];

        // Refresh
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseIsSuccessful();
        $refreshData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $refreshData);
        $this->assertArrayHasKey('refresh_token', $refreshData);

        // New refresh token is different (rotation)
        $this->assertNotSame($refreshToken, $refreshData['refresh_token']);

        // Old refresh token should no longer work
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        // Should fail (expired/revoked token)
        $this->assertResponseStatusCodeSame(500);
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];
        $refreshToken = $loginData['refresh_token'];

        // Logout
        $client->request('POST', '/api/identity/commands/logout', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseStatusCodeSame(204);

        // Refresh with revoked token should fail
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseStatusCodeSame(500);
    }

    public function test_get_current_user(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];

        // Get current user
        $client->request('GET', '/api/identity/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('admin@erp.local', $data['email']);
        $this->assertSame('Admin', $data['first_name']);
        $this->assertSame('ERP', $data['last_name']);
        $this->assertContains('crm.contacts.view_customers', $data['permissions']);
        $this->assertContains('identity.users.manage', $data['permissions']);
    }
}
```

- [ ] **Step 2: Spusť E2E testy**

```bash
docker compose run --rm app php bin/phpunit tests/Identity/E2e/AuthFlowTest.php --testdox
```

Očekáváno:
```
App\Tests\Identity\E2e\AuthFlowTest
 ✔ Login returns tokens
 ✔ Login with wrong password returns error
 ✔ Jwt grants access to crm endpoint
 ✔ Missing token returns 401
 ✔ Invalid token returns 401
 ✔ Refresh token rotation
 ✔ Logout revokes refresh token
 ✔ Get current user
```

- [ ] **Step 3: Spusť celou test suite (identity unit + E2E)**

```bash
docker compose run --rm app php bin/phpunit --testdox
```

Očekáváno: všechny testy zelené (identity unit testy + E2E + CRM testy).

- [ ] **Step 4: Commit**

```bash
git add tests/Identity/
git commit -m "test(identity): E2E testy — login flow, JWT auth, refresh token rotation, CRM endpoint access"
```
