# CRM Contacts MVP — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vytvořit funkční MVP — Symfony monorepo s Composer packages, shared-kernel infrastrukturou (CQRS busy) a CRM Contacts BC s RegisterCustomer, UpdateCustomer, GetCustomerList, GetCustomerDetail use cases, HTTP API a testy.

**Architecture:** Monorepo se standardní Symfony strukturou v root + DDD packages v `packages/`. Shared-kernel definuje base třídy a bus interfaces implementované přes Symfony Messenger. CRM package implementuje Contacts BC s Vertical Slice Architecture a CQRS.

**Tech Stack:** PHP 8.4, Symfony 8.x, Doctrine ORM 3.x (XML mapping), Symfony Messenger (3 busy), PostgreSQL 16, PHPUnit 11, Docker, symfony/uid

**Referenční příručka:** https://ddd-v-symfony.katuscak.cz/ — všechna architektonická rozhodnutí se řídí touto příručkou.

---

## Přehled souborů

```
ddd-erp/
├── bin/console
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── messenger.yaml
│   │   └── security.yaml
│   ├── routes/
│   │   └── crm_contacts.yaml
│   ├── bundles.php
│   └── services.yaml
├── public/index.php
├── src/Kernel.php
├── packages/
│   ├── shared-kernel/
│   │   ├── composer.json
│   │   ├── src/
│   │   │   ├── Domain/
│   │   │   │   ├── AggregateRoot.php
│   │   │   │   ├── DomainEvent.php
│   │   │   │   └── ValueObject.php
│   │   │   ├── Application/
│   │   │   │   ├── CommandBusInterface.php
│   │   │   │   ├── QueryBusInterface.php
│   │   │   │   └── EventBusInterface.php
│   │   │   └── Infrastructure/
│   │   │       ├── Messenger/
│   │   │       │   ├── MessengerCommandBus.php
│   │   │       │   ├── MessengerQueryBus.php
│   │   │       │   └── MessengerEventBus.php
│   │   │       └── Http/
│   │   │           └── DomainExceptionListener.php
│   │   └── tests/
│   │       └── Domain/
│   │           └── AggregateRootTest.php
│   └── crm/
│       ├── composer.json
│       ├── src/
│       │   └── Contacts/
│       │       ├── Domain/
│       │       │   ├── Customer.php
│       │       │   ├── CustomerId.php
│       │       │   ├── CustomerEmail.php
│       │       │   ├── CustomerName.php
│       │       │   ├── CustomerRegistered.php
│       │       │   ├── CustomerUpdated.php
│       │       │   ├── CustomerRepository.php
│       │       │   ├── CustomerNotFoundException.php
│       │       │   └── InvalidEmailException.php
│       │       ├── Application/
│       │       │   ├── RegisterCustomer/
│       │       │   │   ├── RegisterCustomerCommand.php
│       │       │   │   └── RegisterCustomerHandler.php
│       │       │   ├── UpdateCustomer/
│       │       │   │   ├── UpdateCustomerCommand.php
│       │       │   │   └── UpdateCustomerHandler.php
│       │       │   ├── GetCustomerList/
│       │       │   │   ├── GetCustomerListQuery.php
│       │       │   │   ├── GetCustomerListHandler.php
│       │       │   │   └── CustomerListItemDTO.php
│       │       │   └── GetCustomerDetail/
│       │       │       ├── GetCustomerDetailQuery.php
│       │       │       ├── GetCustomerDetailHandler.php
│       │       │       └── CustomerDetailDTO.php
│       │       └── Infrastructure/
│       │           ├── Doctrine/
│       │           │   ├── Type/
│       │           │   │   ├── CustomerIdType.php
│       │           │   │   └── CustomerEmailType.php
│       │           │   ├── CustomerMapping.xml
│       │           │   └── CustomerNameMapping.xml
│       │           ├── Persistence/
│       │           │   └── DoctrineCustomerRepository.php
│       │           ├── Http/
│       │           │   ├── RegisterCustomerController.php
│       │           │   ├── UpdateCustomerController.php
│       │           │   ├── GetCustomerListController.php
│       │           │   └── GetCustomerDetailController.php
│       │           └── Security/
│       │               ├── ContactsPermission.php
│       │               └── ContactsVoter.php
│       └── tests/
│           └── Contacts/
│               ├── Domain/
│               │   ├── CustomerTest.php
│               │   ├── CustomerIdTest.php
│               │   ├── CustomerEmailTest.php
│               │   └── CustomerNameTest.php
│               ├── Application/
│               │   ├── RegisterCustomerHandlerTest.php
│               │   ├── UpdateCustomerHandlerTest.php
│               │   └── GetCustomerListHandlerTest.php
│               └── Infrastructure/
│                   └── Http/
│                       └── RegisterCustomerControllerTest.php
├── migrations/
├── frontend/
│   └── package.json
├── composer.json
├── docker-compose.yml
├── Dockerfile
└── .env
```

---

## Task 1: Docker + Symfony skeleton

**Files:**
- Create: `docker-compose.yml`
- Create: `Dockerfile`
- Create: `.env`
- Create: `composer.json` (root)

- [ ] **Step 1: Vytvoř docker-compose.yml**

```yaml
# docker-compose.yml
services:
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ddd_erp
      POSTGRES_USER: erp
      POSTGRES_PASSWORD: erp_secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  app:
    build: .
    volumes:
      - .:/app
    working_dir: /app
    depends_on:
      - db
    environment:
      DATABASE_URL: "postgresql://erp:erp_secret@db:5432/ddd_erp"

volumes:
  postgres_data:
```

- [ ] **Step 2: Vytvoř Dockerfile**

```dockerfile
# Dockerfile
FROM php:8.4-cli-alpine

RUN apk add --no-cache postgresql-dev git unzip \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
```

- [ ] **Step 3: Spusť Symfony skeleton installer**

```bash
composer create-project symfony/skeleton . --no-install
```

Pokud adresář není prázdný, nainstaluj manuálně:

```bash
composer require symfony/framework-bundle symfony/dotenv symfony/runtime \
    symfony/messenger symfony/security-bundle symfony/uid \
    doctrine/orm doctrine/doctrine-bundle doctrine/doctrine-migrations-bundle \
    nelmio/api-doc-bundle \
    --no-install
```

- [ ] **Step 4: Vytvoř .env**

```ini
# .env
APP_ENV=dev
APP_SECRET=change_me_in_production_32chars_min

DATABASE_URL="postgresql://erp:erp_secret@127.0.0.1:5432/ddd_erp"
```

- [ ] **Step 5: Spusť composer install v Docker**

```bash
docker compose run --rm app composer install
```

Očekáváno: vendor/ adresář vytvořen, žádné chyby.

- [ ] **Step 6: Ověř Symfony funguje**

```bash
docker compose run --rm app php bin/console about
```

Očekáváno: výpis informací o Symfony aplikaci.

- [ ] **Step 7: Commit**

```bash
git add docker-compose.yml Dockerfile .env composer.json composer.lock symfony.lock
git commit -m "feat: Symfony skeleton + Docker setup"
```

---

## Task 2: Composer monorepo — path repositories

**Files:**
- Modify: `composer.json`
- Create: `packages/shared-kernel/composer.json`
- Create: `packages/crm/composer.json`

- [ ] **Step 1: Přidej path repositories a požadavky do root composer.json**

Edituj `composer.json` — přidej do sekce `repositories` a `require`:

```json
{
    "name": "ddd-erp/app",
    "type": "project",
    "require": {
        "php": "^8.4",
        "ddd-erp/shared-kernel": "*",
        "ddd-erp/crm": "*"
    },
    "repositories": [
        {"type": "path", "url": "./packages/shared-kernel", "options": {"symlink": true}},
        {"type": "path", "url": "./packages/crm", "options": {"symlink": true}}
    ],
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

- [ ] **Step 2: Vytvoř packages/shared-kernel/composer.json**

```json
{
    "name": "ddd-erp/shared-kernel",
    "type": "library",
    "require": {
        "php": "^8.4",
        "symfony/messenger": "^7.0 || ^8.0",
        "symfony/http-kernel": "^7.0 || ^8.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "SharedKernel\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "SharedKernel\\Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 3: Vytvoř packages/crm/composer.json**

```json
{
    "name": "ddd-erp/crm",
    "type": "library",
    "require": {
        "php": "^8.4",
        "ddd-erp/shared-kernel": "*",
        "doctrine/orm": "^3.0",
        "doctrine/doctrine-bundle": "^2.0",
        "symfony/security-core": "^7.0 || ^8.0",
        "symfony/uid": "^7.0 || ^8.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "symfony/browser-kit": "^7.0 || ^8.0"
    },
    "autoload": {
        "psr-4": {
            "Crm\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Crm\\Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 4: Nainstaluj packages**

```bash
docker compose run --rm app composer update
```

Očekáváno: `packages/shared-kernel` a `packages/crm` jsou symlinknuty do `vendor/`.

- [ ] **Step 5: Ověř autoload**

```bash
docker compose run --rm app php -r "echo class_exists('SharedKernel\Domain\AggregateRoot') ? 'OK' : 'FAIL';"
```

Pozn.: třída ještě neexistuje — očekáváno `FAIL`, ale bez PHP chyby na autoload.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock packages/
git commit -m "feat: Composer monorepo s path repositories (shared-kernel, crm)"
```

---

## Task 3: Shared Kernel — Domain base třídy (TDD)

**Files:**
- Create: `packages/shared-kernel/tests/Domain/AggregateRootTest.php`
- Create: `packages/shared-kernel/src/Domain/AggregateRoot.php`
- Create: `packages/shared-kernel/src/Domain/DomainEvent.php`
- Create: `packages/shared-kernel/src/Domain/ValueObject.php`

- [ ] **Step 1: Vytvoř phpunit.xml pro shared-kernel**

```xml
<!-- packages/shared-kernel/phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="SharedKernel">
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

- [ ] **Step 2: Nainstaluj PHPUnit pro shared-kernel**

```bash
cd packages/shared-kernel && composer install
```

- [ ] **Step 3: Napiš failing test pro AggregateRoot**

```php
<?php
// packages/shared-kernel/tests/Domain/AggregateRootTest.php
declare(strict_types=1);

namespace SharedKernel\Tests\Domain;

use PHPUnit\Framework\TestCase;
use SharedKernel\Domain\AggregateRoot;
use SharedKernel\Domain\DomainEvent;

final class AggregateRootTest extends TestCase
{
    public function test_pulls_recorded_events_and_clears_them(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->recordEvent(new class extends DomainEvent {});
                $this->recordEvent(new class extends DomainEvent {});
            }
        };

        $aggregate->doSomething();

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(2, $events);

        // Po pull jsou události vymazány
        $this->assertCount(0, $aggregate->pullDomainEvents());
    }

    public function test_domain_event_records_occurred_at(): void
    {
        $before = new \DateTimeImmutable();
        $event = new class extends DomainEvent {};
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $event->occurredAt);
        $this->assertLessThanOrEqual($after, $event->occurredAt);
    }
}
```

- [ ] **Step 4: Spusť test — ověř FAIL**

```bash
cd packages/shared-kernel && ./vendor/bin/phpunit --testdox
```

Očekáváno: `Error: Class "SharedKernel\Domain\AggregateRoot" not found`

- [ ] **Step 5: Implementuj DomainEvent**

```php
<?php
// packages/shared-kernel/src/Domain/DomainEvent.php
declare(strict_types=1);

namespace SharedKernel\Domain;

abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
```

- [ ] **Step 6: Implementuj AggregateRoot**

```php
<?php
// packages/shared-kernel/src/Domain/AggregateRoot.php
declare(strict_types=1);

namespace SharedKernel\Domain;

abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return DomainEvent[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

- [ ] **Step 7: Implementuj ValueObject**

```php
<?php
// packages/shared-kernel/src/Domain/ValueObject.php
declare(strict_types=1);

namespace SharedKernel\Domain;

abstract class ValueObject
{
    abstract public function equals(self $other): bool;
}
```

- [ ] **Step 8: Spusť testy — ověř PASS**

```bash
cd packages/shared-kernel && ./vendor/bin/phpunit --testdox
```

Očekáváno:
```
SharedKernel\Tests\Domain\AggregateRootTest
 ✔ Pulls recorded events and clears them
 ✔ Domain event records occurred at
```

- [ ] **Step 9: Commit**

```bash
git add packages/shared-kernel/
git commit -m "feat(shared-kernel): AggregateRoot, DomainEvent, ValueObject base třídy"
```

---

## Task 4: Shared Kernel — Bus interfaces + Messenger implementace

**Files:**
- Create: `packages/shared-kernel/src/Application/CommandBusInterface.php`
- Create: `packages/shared-kernel/src/Application/QueryBusInterface.php`
- Create: `packages/shared-kernel/src/Application/EventBusInterface.php`
- Create: `packages/shared-kernel/src/Infrastructure/Messenger/MessengerCommandBus.php`
- Create: `packages/shared-kernel/src/Infrastructure/Messenger/MessengerQueryBus.php`
- Create: `packages/shared-kernel/src/Infrastructure/Messenger/MessengerEventBus.php`

- [ ] **Step 1: Vytvoř CommandBusInterface**

```php
<?php
// packages/shared-kernel/src/Application/CommandBusInterface.php
declare(strict_types=1);

namespace SharedKernel\Application;

interface CommandBusInterface
{
    public function dispatch(object $command): void;
}
```

- [ ] **Step 2: Vytvoř QueryBusInterface**

```php
<?php
// packages/shared-kernel/src/Application/QueryBusInterface.php
declare(strict_types=1);

namespace SharedKernel\Application;

interface QueryBusInterface
{
    public function dispatch(object $query): mixed;
}
```

- [ ] **Step 3: Vytvoř EventBusInterface**

```php
<?php
// packages/shared-kernel/src/Application/EventBusInterface.php
declare(strict_types=1);

namespace SharedKernel\Application;

use SharedKernel\Domain\DomainEvent;

interface EventBusInterface
{
    public function dispatch(DomainEvent $event): void;
}
```

- [ ] **Step 4: Implementuj MessengerCommandBus**

```php
<?php
// packages/shared-kernel/src/Infrastructure/Messenger/MessengerCommandBus.php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    public function dispatch(object $command): void
    {
        $this->commandBus->dispatch($command);
    }
}
```

- [ ] **Step 5: Implementuj MessengerQueryBus**

Messenger vrací hodnotu z handleru přes `HandledStamp`. QueryBus ji extrahuje.

```php
<?php
// packages/shared-kernel/src/Infrastructure/Messenger/MessengerQueryBus.php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\QueryBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {}

    public function dispatch(object $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        /** @var HandledStamp|null $stamp */
        $stamp = $envelope->last(HandledStamp::class);
        return $stamp?->getResult();
    }
}
```

- [ ] **Step 6: Implementuj MessengerEventBus**

```php
<?php
// packages/shared-kernel/src/Infrastructure/Messenger/MessengerEventBus.php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Messenger;

use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add packages/shared-kernel/src/Application/ packages/shared-kernel/src/Infrastructure/
git commit -m "feat(shared-kernel): CommandBus, QueryBus, EventBus interfaces + Messenger implementace"
```

---

## Task 5: Symfony Messenger konfigurace — 3 busy

**Files:**
- Create: `config/packages/messenger.yaml`
- Modify: `config/services.yaml`

- [ ] **Step 1: Vytvoř config/packages/messenger.yaml**

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            query.bus:
                middleware: []
            event.bus:
                default_middleware:
                    enabled: true
                    allow_no_handlers: true
```

- [ ] **Step 2: Nakonfiguruj services.yaml — wire bus implementace**

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'

    # Shared Kernel — Command Bus
    SharedKernel\Infrastructure\Messenger\MessengerCommandBus:
        arguments:
            $commandBus: '@command.bus'

    SharedKernel\Application\CommandBusInterface:
        alias: SharedKernel\Infrastructure\Messenger\MessengerCommandBus

    # Shared Kernel — Query Bus
    SharedKernel\Infrastructure\Messenger\MessengerQueryBus:
        arguments:
            $queryBus: '@query.bus'

    SharedKernel\Application\QueryBusInterface:
        alias: SharedKernel\Infrastructure\Messenger\MessengerQueryBus

    # Shared Kernel — Event Bus
    SharedKernel\Infrastructure\Messenger\MessengerEventBus:
        arguments:
            $eventBus: '@event.bus'

    SharedKernel\Application\EventBusInterface:
        alias: SharedKernel\Infrastructure\Messenger\MessengerEventBus

    # CRM package — autoconfigure handlery
    Crm\:
        resource: '../packages/crm/src/'
        exclude:
            - '../packages/crm/src/*/Domain/'
            - '../packages/crm/src/*/Infrastructure/Doctrine/Type/'
```

- [ ] **Step 3: Ověř container se sestaví bez chyb**

```bash
docker compose run --rm app php bin/console debug:container --env=dev 2>&1 | head -20
```

Očekáváno: žádná chyba, výpis service kontejneru.

- [ ] **Step 4: Commit**

```bash
git add config/
git commit -m "feat: Symfony Messenger 3-bus konfigurace (command, query, event)"
```

---

## Task 6: CRM — CustomerId Value Object (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Domain/CustomerIdTest.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerId.php`

- [ ] **Step 1: Nainstaluj PHPUnit pro crm package**

```bash
cd packages/crm && composer install
```

- [ ] **Step 2: Vytvoř phpunit.xml pro crm**

```xml
<!-- packages/crm/phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="CRM">
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

- [ ] **Step 3: Napiš failing test**

```php
<?php
// packages/crm/tests/Contacts/Domain/CustomerIdTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerId;
use PHPUnit\Framework\TestCase;

final class CustomerIdTest extends TestCase
{
    public function test_generates_unique_ids(): void
    {
        $id1 = CustomerId::generate();
        $id2 = CustomerId::generate();

        $this->assertNotEquals($id1->value(), $id2->value());
    }

    public function test_creates_from_valid_string(): void
    {
        $id = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', $id->value());
    }

    public function test_throws_on_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerId::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $id1 = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $id2 = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');

        $this->assertTrue($id1->equals($id2));
    }

    public function test_to_string(): void
    {
        $id = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->assertSame('018e8f2a-1234-7000-8000-000000000001', (string) $id);
    }
}
```

- [ ] **Step 4: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/CustomerIdTest.php --testdox
```

Očekáváno: `Error: Class "Crm\Contacts\Domain\CustomerId" not found`

- [ ] **Step 5: Implementuj CustomerId**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerId.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use Symfony\Component\Uid\Uuid;

final class CustomerId
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
            throw new \InvalidArgumentException("Invalid CustomerId: '$value'");
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

- [ ] **Step 6: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/CustomerIdTest.php --testdox
```

Očekáváno: 5 testů zelených.

- [ ] **Step 7: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): CustomerId Value Object"
```

---

## Task 7: CRM — CustomerEmail a CustomerName Value Objects (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Domain/CustomerEmailTest.php`
- Create: `packages/crm/tests/Contacts/Domain/CustomerNameTest.php`
- Create: `packages/crm/src/Contacts/Domain/InvalidEmailException.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerEmail.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerName.php`

- [ ] **Step 1: Napiš failing test pro CustomerEmail**

```php
<?php
// packages/crm/tests/Contacts/Domain/CustomerEmailTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\InvalidEmailException;
use PHPUnit\Framework\TestCase;

final class CustomerEmailTest extends TestCase
{
    public function test_creates_from_valid_email(): void
    {
        $email = CustomerEmail::fromString('Jan.Novak@Firma.CZ');
        $this->assertSame('jan.novak@firma.cz', $email->value());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);
        CustomerEmail::fromString('not-an-email');
    }

    public function test_equality(): void
    {
        $email1 = CustomerEmail::fromString('jan@firma.cz');
        $email2 = CustomerEmail::fromString('JAN@FIRMA.CZ');

        $this->assertTrue($email1->equals($email2));
    }
}
```

- [ ] **Step 2: Napiš failing test pro CustomerName**

```php
<?php
// packages/crm/tests/Contacts/Domain/CustomerNameTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerName;
use PHPUnit\Framework\TestCase;

final class CustomerNameTest extends TestCase
{
    public function test_creates_from_parts(): void
    {
        $name = CustomerName::fromParts('Jan', 'Novák');
        $this->assertSame('Jan', $name->firstName());
        $this->assertSame('Novák', $name->lastName());
        $this->assertSame('Jan Novák', $name->fullName());
    }

    public function test_throws_on_empty_first_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerName::fromParts('', 'Novák');
    }

    public function test_throws_on_empty_last_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerName::fromParts('Jan', '  ');
    }

    public function test_equality(): void
    {
        $name1 = CustomerName::fromParts('Jan', 'Novák');
        $name2 = CustomerName::fromParts('Jan', 'Novák');

        $this->assertTrue($name1->equals($name2));
    }
}
```

- [ ] **Step 3: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/CustomerEmailTest.php tests/Contacts/Domain/CustomerNameTest.php --testdox
```

Očekáváno: `Error: Class not found`

- [ ] **Step 4: Implementuj InvalidEmailException**

```php
<?php
// packages/crm/src/Contacts/Domain/InvalidEmailException.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class InvalidEmailException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("Invalid email address: '$email'");
    }
}
```

- [ ] **Step 5: Implementuj CustomerEmail**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerEmail.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class CustomerEmail
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($value);
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

- [ ] **Step 6: Implementuj CustomerName**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerName.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class CustomerName
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

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName;
    }
}
```

- [ ] **Step 7: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/ --testdox
```

Očekáváno: všechny testy zelené.

- [ ] **Step 8: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): CustomerEmail, CustomerName Value Objects"
```

---

## Task 8: CRM — Customer Aggregate + Domain Events + Repository Interface (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Domain/CustomerTest.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerRegistered.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerUpdated.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerNotFoundException.php`
- Create: `packages/crm/src/Contacts/Domain/Customer.php`
- Create: `packages/crm/src/Contacts/Domain/CustomerRepository.php`

- [ ] **Step 1: Napiš failing test pro Customer**

```php
<?php
// packages/crm/tests/Contacts/Domain/CustomerTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRegistered;
use Crm\Contacts\Domain\CustomerUpdated;
use Crm\Contacts\Domain\InvalidEmailException;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    private CustomerId $id;
    private CustomerEmail $email;
    private CustomerName $name;

    protected function setUp(): void
    {
        $this->id    = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->email = CustomerEmail::fromString('jan@firma.cz');
        $this->name  = CustomerName::fromParts('Jan', 'Novák');
    }

    public function test_registers_customer(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);

        $this->assertTrue($customer->id()->equals($this->id));
        $this->assertTrue($customer->email()->equals($this->email));
        $this->assertTrue($customer->name()->equals($this->name));
    }

    public function test_registration_emits_customer_registered_event(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $events   = $customer->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(CustomerRegistered::class, $events[0]);
        $this->assertTrue($events[0]->customerId->equals($this->id));
    }

    public function test_pull_clears_events(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $customer->pullDomainEvents();

        $this->assertCount(0, $customer->pullDomainEvents());
    }

    public function test_updates_customer(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $customer->pullDomainEvents(); // vymaž registration event

        $newEmail = CustomerEmail::fromString('petr@firma.cz');
        $newName  = CustomerName::fromParts('Petr', 'Svoboda');
        $customer->update($newEmail, $newName);

        $this->assertTrue($customer->email()->equals($newEmail));
        $this->assertTrue($customer->name()->equals($newName));

        $events = $customer->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CustomerUpdated::class, $events[0]);
    }
}
```

- [ ] **Step 2: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/CustomerTest.php --testdox
```

Očekáváno: `Error: Class "Crm\Contacts\Domain\Customer" not found`

- [ ] **Step 3: Implementuj CustomerRegistered**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerRegistered.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use SharedKernel\Domain\DomainEvent;

final class CustomerRegistered extends DomainEvent
{
    public function __construct(
        public readonly CustomerId $customerId,
        public readonly CustomerEmail $email,
        public readonly CustomerName $name,
    ) {
        parent::__construct();
    }
}
```

- [ ] **Step 4: Implementuj CustomerUpdated**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerUpdated.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use SharedKernel\Domain\DomainEvent;

final class CustomerUpdated extends DomainEvent
{
    public function __construct(
        public readonly CustomerId $customerId,
        public readonly CustomerEmail $email,
        public readonly CustomerName $name,
    ) {
        parent::__construct();
    }
}
```

- [ ] **Step 5: Implementuj CustomerNotFoundException**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerNotFoundException.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class CustomerNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Customer not found: '$id'");
    }
}
```

- [ ] **Step 6: Implementuj Customer Aggregate**

```php
<?php
// packages/crm/src/Contacts/Domain/Customer.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Customer extends AggregateRoot
{
    private function __construct(
        private readonly CustomerId $id,
        private CustomerEmail $email,
        private CustomerName $name,
        private readonly \DateTimeImmutable $registeredAt,
    ) {}

    public static function register(
        CustomerId $id,
        CustomerEmail $email,
        CustomerName $name,
    ): self {
        $customer = new self($id, $email, $name, new \DateTimeImmutable());
        $customer->recordEvent(new CustomerRegistered($id, $email, $name));
        return $customer;
    }

    public function update(CustomerEmail $email, CustomerName $name): void
    {
        $this->email = $email;
        $this->name  = $name;
        $this->recordEvent(new CustomerUpdated($this->id, $email, $name));
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): CustomerEmail
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
```

- [ ] **Step 7: Implementuj CustomerRepository interface**

```php
<?php
// packages/crm/src/Contacts/Domain/CustomerRepository.php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

interface CustomerRepository
{
    /** @throws CustomerNotFoundException */
    public function get(CustomerId $id): Customer;

    public function save(Customer $customer): void;

    public function nextIdentity(): CustomerId;
}
```

- [ ] **Step 8: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Domain/ --testdox
```

Očekáváno: všechny testy zelené (CustomerTest + CustomerIdTest + CustomerEmailTest + CustomerNameTest).

- [ ] **Step 9: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): Customer Aggregate, Domain Events, CustomerRepository interface"
```

---

## Task 9: CRM — RegisterCustomer use case (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Application/RegisterCustomerHandlerTest.php`
- Create: `packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerCommand.php`
- Create: `packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php`

- [ ] **Step 1: Vytvoř sdílené test helpery**

Aby se předešlo duplicitě, vytvoř helper soubor sdílený mezi Application testy:

```php
<?php
// packages/crm/tests/Contacts/Application/TestDoubles.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerRepository;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class InMemoryCustomerRepository implements CustomerRepository
{
    /** @var Customer[] */
    private array $customers = [];

    public function get(CustomerId $id): Customer
    {
        return $this->customers[$id->value()]
            ?? throw new CustomerNotFoundException($id->value());
    }

    public function save(Customer $customer): void
    {
        $this->customers[$customer->id()->value()] = $customer;
    }

    public function nextIdentity(): CustomerId
    {
        return CustomerId::generate();
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

- [ ] **Step 2: Napiš failing test**

Test používá in-memory implementaci repository (žádná DB).

```php
<?php
// packages/crm/tests/Contacts/Application/RegisterCustomerHandlerTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerHandler;
use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerRepository;
use PHPUnit\Framework\TestCase;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class RegisterCustomerHandlerTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private SpyEventBus $eventBus;
    private RegisterCustomerHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new RegisterCustomerHandler($this->repository, $this->eventBus);
    }

    public function test_registers_customer_and_persists(): void
    {
        $customerId = CustomerId::generate()->value();
        $command = new RegisterCustomerCommand(
            customerId: $customerId,
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $customer = $this->repository->get(CustomerId::fromString($customerId));
        $this->assertSame('jan@firma.cz', $customer->email()->value());
        $this->assertSame('Jan', $customer->name()->firstName());
    }

    public function test_dispatches_customer_registered_event(): void
    {
        $command = new RegisterCustomerCommand(
            customerId: CustomerId::generate()->value(),
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(
            \Crm\Contacts\Domain\CustomerRegistered::class,
            $this->eventBus->dispatched[0],
        );
    }
}

```

Pozn.: `InMemoryCustomerRepository` a `SpyEventBus` jsou definovány v `TestDoubles.php` (Task 9, Step 1) a jsou automaticky dostupné díky PSR-4 autoloadu.

- [ ] **Step 3: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Application/RegisterCustomerHandlerTest.php --testdox
```

Očekáváno: `Error: Class "Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand" not found`

- [ ] **Step 3: Implementuj RegisterCustomerCommand**

ID je předgenerováno klientem (controllerem) a předáno v commandu — zajišťuje idempotenci.

```php
<?php
// packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerCommand.php
declare(strict_types=1);

namespace Crm\Contacts\Application\RegisterCustomer;

final readonly class RegisterCustomerCommand
{
    public function __construct(
        public string $customerId,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
```

- [ ] **Step 4: Implementuj RegisterCustomerHandler**

```php
<?php
// packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php
declare(strict_types=1);

namespace Crm\Contacts\Application\RegisterCustomer;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterCustomerCommand $command): void
    {
        $customer = Customer::register(
            CustomerId::fromString($command->customerId),
            CustomerEmail::fromString($command->email),
            CustomerName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($customer);

        foreach ($customer->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 5: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Application/RegisterCustomerHandlerTest.php --testdox
```

Očekáváno: 2 testy zelené.

- [ ] **Step 6: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): TestDoubles helper + RegisterCustomer command + handler"
```

---

## Task 10: CRM — UpdateCustomer use case (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Application/UpdateCustomerHandlerTest.php`
- Create: `packages/crm/src/Contacts/Application/UpdateCustomer/UpdateCustomerCommand.php`
- Create: `packages/crm/src/Contacts/Application/UpdateCustomer/UpdateCustomerHandler.php`

- [ ] **Step 1: Napiš failing test**

```php
<?php
// packages/crm/tests/Contacts/Application/UpdateCustomerHandlerTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerHandler;
use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerCommand;
use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerHandler;
use Crm\Contacts\Domain\CustomerNotFoundException;
use PHPUnit\Framework\TestCase;

final class UpdateCustomerHandlerTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingCustomerId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->eventBus   = new SpyEventBus();

        // Zaregistruj existujícího zákazníka
        $this->existingCustomerId = \Crm\Contacts\Domain\CustomerId::generate()->value();
        $registerHandler = new RegisterCustomerHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = []; // reset spy
    }

    public function test_updates_customer_email_and_name(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $customer = $this->repository->get(\Crm\Contacts\Domain\CustomerId::fromString($this->existingCustomerId));
        $this->assertSame('petr@firma.cz', $customer->email()->value());
        $this->assertSame('Petr', $customer->name()->firstName());
    }

    public function test_dispatches_customer_updated_event(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(
            \Crm\Contacts\Domain\CustomerUpdated::class,
            $this->eventBus->dispatched[0],
        );
    }

    public function test_throws_when_customer_not_found(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        $this->expectException(CustomerNotFoundException::class);
        ($handler)(new UpdateCustomerCommand(
            customerId: '018e8f2a-0000-7000-8000-000000000099',
            email: 'x@x.cz',
            firstName: 'X',
            lastName: 'Y',
        ));
    }
}
```

- [ ] **Step 2: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Application/UpdateCustomerHandlerTest.php --testdox
```

- [ ] **Step 3: Implementuj UpdateCustomerCommand**

```php
<?php
// packages/crm/src/Contacts/Application/UpdateCustomer/UpdateCustomerCommand.php
declare(strict_types=1);

namespace Crm\Contacts\Application\UpdateCustomer;

final readonly class UpdateCustomerCommand
{
    public function __construct(
        public string $customerId,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
```

- [ ] **Step 4: Implementuj UpdateCustomerHandler**

```php
<?php
// packages/crm/src/Contacts/Application/UpdateCustomer/UpdateCustomerHandler.php
declare(strict_types=1);

namespace Crm\Contacts\Application\UpdateCustomer;

use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->get(CustomerId::fromString($command->customerId));

        $customer->update(
            CustomerEmail::fromString($command->email),
            CustomerName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($customer);

        foreach ($customer->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 5: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Application/ --testdox
```

Očekáváno: všechny Application testy zelené.

- [ ] **Step 6: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): UpdateCustomer command + handler"
```

---

## Task 11: CRM — GetCustomerList + GetCustomerDetail use cases (TDD)

**Files:**
- Create: `packages/crm/tests/Contacts/Application/GetCustomerListHandlerTest.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerList/GetCustomerListQuery.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerList/CustomerListItemDTO.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerList/GetCustomerListHandler.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerDetail/GetCustomerDetailQuery.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerDetail/CustomerDetailDTO.php`
- Create: `packages/crm/src/Contacts/Application/GetCustomerDetail/GetCustomerDetailHandler.php`

- [ ] **Step 1: Napiš failing test pro GetCustomerList**

Read handlery jdou přímo na DB přes DBAL Connection. V testu mockujeme Connection.

```php
<?php
// packages/crm/tests/Contacts/Application/GetCustomerListHandlerTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\GetCustomerDetail\CustomerDetailDTO;
use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailHandler;
use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailQuery;
use Crm\Contacts\Application\GetCustomerList\CustomerListItemDTO;
use Crm\Contacts\Application\GetCustomerList\GetCustomerListHandler;
use Crm\Contacts\Application\GetCustomerList\GetCustomerListQuery;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetCustomerListHandlerTest extends TestCase
{
    private Connection&MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    public function test_returns_list_of_customers(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            [
                'id'            => '018e8f2a-1234-7000-8000-000000000001',
                'email'         => 'jan@firma.cz',
                'first_name'    => 'Jan',
                'last_name'     => 'Novák',
                'registered_at' => '2026-01-15 10:00:00',
            ],
        ]);

        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerListHandler($this->connection);
        $items   = ($handler)(new GetCustomerListQuery());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(CustomerListItemDTO::class, $items[0]);
        $this->assertSame('jan@firma.cz', $items[0]->email);
        $this->assertSame('Jan Novák', $items[0]->fullName);
    }

    public function test_returns_customer_detail(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn([
            'id'            => '018e8f2a-1234-7000-8000-000000000001',
            'email'         => 'jan@firma.cz',
            'first_name'    => 'Jan',
            'last_name'     => 'Novák',
            'registered_at' => '2026-01-15 10:00:00',
        ]);

        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerDetailHandler($this->connection);
        $detail  = ($handler)(new GetCustomerDetailQuery('018e8f2a-1234-7000-8000-000000000001'));

        $this->assertInstanceOf(CustomerDetailDTO::class, $detail);
        $this->assertSame('jan@firma.cz', $detail->email);
        $this->assertSame('Jan', $detail->firstName);
    }

    public function test_throws_when_customer_detail_not_found(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(false);
        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerDetailHandler($this->connection);
        $this->expectException(CustomerNotFoundException::class);
        ($handler)(new GetCustomerDetailQuery('018e8f2a-0000-7000-8000-000000000099'));
    }
}
```

- [ ] **Step 2: Spusť — ověř FAIL**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/Application/GetCustomerListHandlerTest.php --testdox
```

- [ ] **Step 3: Implementuj GetCustomerList třídy**

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerList/GetCustomerListQuery.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

final readonly class GetCustomerListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
```

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerList/CustomerListItemDTO.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

final readonly class CustomerListItemDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        public string $registeredAt,
    ) {}
}
```

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerList/GetCustomerListHandler.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerList;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCustomerListHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return CustomerListItemDTO[] */
    public function __invoke(GetCustomerListQuery $query): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, registered_at
             FROM crm_customers
             ORDER BY registered_at DESC
             LIMIT :limit OFFSET :offset',
            ['limit' => $query->limit, 'offset' => $query->offset],
        )->fetchAllAssociative();

        return array_map(
            fn(array $row) => new CustomerListItemDTO(
                id: $row['id'],
                email: $row['email'],
                fullName: $row['first_name'] . ' ' . $row['last_name'],
                registeredAt: $row['registered_at'],
            ),
            $rows,
        );
    }
}
```

- [ ] **Step 4: Implementuj GetCustomerDetail třídy**

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerDetail/GetCustomerDetailQuery.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

final readonly class GetCustomerDetailQuery
{
    public function __construct(
        public string $customerId,
    ) {}
}
```

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerDetail/CustomerDetailDTO.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

final readonly class CustomerDetailDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $registeredAt,
    ) {}
}
```

```php
<?php
// packages/crm/src/Contacts/Application/GetCustomerDetail/GetCustomerDetailHandler.php
declare(strict_types=1);

namespace Crm\Contacts\Application\GetCustomerDetail;

use Crm\Contacts\Domain\CustomerNotFoundException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCustomerDetailHandler
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(GetCustomerDetailQuery $query): CustomerDetailDTO
    {
        $row = $this->connection->executeQuery(
            'SELECT id, email, first_name, last_name, registered_at
             FROM crm_customers
             WHERE id = :id',
            ['id' => $query->customerId],
        )->fetchAssociative();

        if ($row === false) {
            throw new CustomerNotFoundException($query->customerId);
        }

        return new CustomerDetailDTO(
            id: $row['id'],
            email: $row['email'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            registeredAt: $row['registered_at'],
        );
    }
}
```

- [ ] **Step 5: Spusť — ověř PASS**

```bash
cd packages/crm && ./vendor/bin/phpunit tests/Contacts/ --testdox
```

Očekáváno: všechny testy zelené.

- [ ] **Step 6: Commit**

```bash
git add packages/crm/
git commit -m "feat(crm): GetCustomerList + GetCustomerDetail queries + handlers + DTOs"
```

---

## Task 12: CRM — Doctrine typy + XML mapping

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Doctrine/Type/CustomerIdType.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Doctrine/Type/CustomerEmailType.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Doctrine/CustomerMapping.xml`
- Create: `packages/crm/src/Contacts/Infrastructure/Doctrine/CustomerNameMapping.xml`
- Modify: `config/packages/doctrine.yaml`

- [ ] **Step 1: Implementuj CustomerIdType**

Custom Doctrine typ mapuje CustomerId ↔ string v DB. Žije v Infrastructure — doménová třída zůstává čistá.

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Doctrine/Type/CustomerIdType.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Doctrine\Type;

use Crm\Contacts\Domain\CustomerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CustomerIdType extends StringType
{
    public const NAME = 'customer_id';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CustomerId
    {
        if ($value === null) {
            return null;
        }
        return CustomerId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CustomerId ? $value->value() : (string) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

- [ ] **Step 2: Implementuj CustomerEmailType**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Doctrine/Type/CustomerEmailType.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Doctrine\Type;

use Crm\Contacts\Domain\CustomerEmail;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CustomerEmailType extends StringType
{
    public const NAME = 'customer_email';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CustomerEmail
    {
        if ($value === null) {
            return null;
        }
        return CustomerEmail::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CustomerEmail ? $value->value() : (string) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

- [ ] **Step 3: Vytvoř CustomerNameMapping.xml (Embeddable)**

CustomerName je Doctrine Embeddable. Sloupce `first_name` / `last_name` jsou definovány přímo tady — Customer.xml pak jen referencuje embeddable.

```xml
<!-- packages/crm/src/Contacts/Infrastructure/Doctrine/CustomerNameMapping.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="Crm\Contacts\Domain\CustomerName">
        <field name="firstName" type="string" column="first_name" length="100" nullable="false"/>
        <field name="lastName"  type="string" column="last_name"  length="100" nullable="false"/>
    </embeddable>

</doctrine-mapping>
```

- [ ] **Step 4: Vytvoř CustomerMapping.xml**

`use-column-prefix="false"` zajistí, že Doctrine nebude prefixovat sloupce CustomerName názvem embedded fieldu (`name_first_name` → `first_name`).

```xml
<!-- packages/crm/src/Contacts/Infrastructure/Doctrine/CustomerMapping.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="Crm\Contacts\Domain\Customer" table="crm_customers">

        <id name="id" type="customer_id" column="id">
            <generator strategy="NONE"/>
        </id>

        <field name="email" type="customer_email" column="email" length="255" nullable="false" unique="true"/>

        <embedded name="name" class="Crm\Contacts\Domain\CustomerName" use-column-prefix="false"/>

        <field name="registeredAt" type="datetime_immutable" column="registered_at" nullable="false"/>

    </entity>

</doctrine-mapping>
```

- [ ] **Step 5: Nakonfiguruj Doctrine v doctrine.yaml**

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        types:
            customer_id:    Crm\Contacts\Infrastructure\Doctrine\Type\CustomerIdType
            customer_email: Crm\Contacts\Infrastructure\Doctrine\Type\CustomerEmailType

    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        mappings:
            CrmContacts:
                type: xml
                dir: '%kernel.project_dir%/packages/crm/src/Contacts/Infrastructure/Doctrine'
                prefix: 'Crm\Contacts\Domain'
                is_bundle: false
```

- [ ] **Step 6: Ověř mapping**

```bash
docker compose run --rm app php bin/console doctrine:mapping:info
```

Očekáváno: `Crm\Contacts\Domain\Customer` je v seznamu mapovaných entit.

- [ ] **Step 7: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Doctrine/ config/packages/doctrine.yaml
git commit -m "feat(crm): Doctrine custom types + XML mapping pro Customer Aggregate"
```

---

## Task 13: CRM — DoctrineCustomerRepository + Migrace

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php`
- Create: `migrations/` (generováno)
- Modify: `config/services.yaml`

- [ ] **Step 1: Implementuj DoctrineCustomerRepository**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Persistence;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCustomerRepository implements CustomerRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function get(CustomerId $id): Customer
    {
        $customer = $this->entityManager->find(Customer::class, $id);
        if ($customer === null) {
            throw new CustomerNotFoundException($id->value());
        }
        return $customer;
    }

    public function save(Customer $customer): void
    {
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
    }

    public function nextIdentity(): CustomerId
    {
        return CustomerId::generate();
    }
}
```

- [ ] **Step 2: Přidej alias do services.yaml**

Přidej na konec `config/services.yaml`:

```yaml
    Crm\Contacts\Domain\CustomerRepository:
        alias: Crm\Contacts\Infrastructure\Persistence\DoctrineCustomerRepository
```

- [ ] **Step 3: Spusť Docker a vytvoř databázi**

```bash
docker compose up -d db
docker compose run --rm app php bin/console doctrine:database:create
```

Očekáváno: `Created database "ddd_erp" for connection named default`

- [ ] **Step 4: Vygeneruj migraci**

```bash
docker compose run --rm app php bin/console doctrine:migrations:diff
```

Očekáváno: nový soubor v `migrations/` s SQL pro tabulku `crm_customers`.

- [ ] **Step 5: Zkontroluj vygenerovanou migraci**

Otevři soubor `migrations/Version*.php`. Ověř, že obsahuje:

```sql
CREATE TABLE crm_customers (
    id VARCHAR(255) NOT NULL COMMENT '...',
    email VARCHAR(255) NOT NULL COMMENT '...',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    registered_at TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (email)
);
```

- [ ] **Step 6: Spusť migraci**

```bash
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction
```

Očekáváno: `[OK] Successfully executed 1 migrations.`

- [ ] **Step 7: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Persistence/ config/services.yaml migrations/
git commit -m "feat(crm): DoctrineCustomerRepository + databázová migrace"
```

---

## Task 14: CRM — Oprávnění (ContactsPermission + ContactsVoter)

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Security/ContactsPermission.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Security/ContactsVoter.php`
- Modify: `config/packages/security.yaml`

- [ ] **Step 1: Implementuj ContactsPermission enum**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Security/ContactsPermission.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Security;

enum ContactsPermission: string
{
    case VIEW_CUSTOMERS   = 'crm.contacts.view_customers';
    case CREATE_CUSTOMER  = 'crm.contacts.create_customer';
    case UPDATE_CUSTOMER  = 'crm.contacts.update_customer';
}
```

- [ ] **Step 2: Implementuj ContactsVoter**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Security/ContactsVoter.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, null>
 */
final class ContactsVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, array_column(ContactsPermission::cases(), 'value'), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($user === null) {
            return false;
        }

        // Uživatel musí mít roli odpovídající oprávnění.
        // Mapování: permission string → ROLE_* konvence
        $role = 'ROLE_' . strtoupper(str_replace('.', '_', $attribute));
        return in_array($role, $token->getRoleNames(), true);
    }
}
```

- [ ] **Step 3: Základní security.yaml**

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        users_in_memory:
            memory:
                users:
                    admin:
                        password: '$2y$13$...'  # vygeneruj: php bin/console security:hash-password
                        roles:
                            - ROLE_CRM_CONTACTS_VIEW_CUSTOMERS
                            - ROLE_CRM_CONTACTS_CREATE_CUSTOMER
                            - ROLE_CRM_CONTACTS_UPDATE_CUSTOMER

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        api:
            pattern: ^/api
            stateless: true
            http_basic: ~

    access_control:
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

- [ ] **Step 4: Ověř container**

```bash
docker compose run --rm app php bin/console debug:container ContactsVoter
```

Očekáváno: voter nalezen jako service.

- [ ] **Step 5: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Security/ config/packages/security.yaml
git commit -m "feat(crm): ContactsPermission enum + ContactsVoter"
```

---

## Task 15: CRM — HTTP Controllers + Exception handling

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Http/GetCustomerListController.php`
- Create: `packages/crm/src/Contacts/Infrastructure/Http/GetCustomerDetailController.php`
- Create: `packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php`
- Create: `config/routes/crm_contacts.yaml`

- [ ] **Step 1: Implementuj RegisterCustomerController**

Controller přijme JSON, předgeneruje ID, odešle Command.

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/register-customer', methods: ['POST'])]
#[IsGranted(ContactsPermission::CREATE_CUSTOMER->value)]
final class RegisterCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $customerId = CustomerId::generate()->value();

        $this->commandBus->dispatch(new RegisterCustomerCommand(
            customerId: $customerId,
            email: (string) ($data['email'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(['id' => $customerId], Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 2: Implementuj UpdateCustomerController**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerCommand;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/update-customer/{id}', methods: ['PUT'])]
#[IsGranted(ContactsPermission::UPDATE_CUSTOMER->value)]
final class UpdateCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new UpdateCustomerCommand(
            customerId: $id,
            email: (string) ($data['email'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 3: Implementuj GetCustomerListController**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Http/GetCustomerListController.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\GetCustomerList\GetCustomerListQuery;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/customers', methods: ['GET'])]
#[IsGranted(ContactsPermission::VIEW_CUSTOMERS->value)]
final class GetCustomerListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetCustomerListQuery(
            limit: (int) $request->query->get('limit', 50),
            offset: (int) $request->query->get('offset', 0),
        ));

        return new JsonResponse(array_map(
            fn($item) => [
                'id'            => $item->id,
                'email'         => $item->email,
                'full_name'     => $item->fullName,
                'registered_at' => $item->registeredAt,
            ],
            $items,
        ));
    }
}
```

- [ ] **Step 4: Implementuj GetCustomerDetailController**

```php
<?php
// packages/crm/src/Contacts/Infrastructure/Http/GetCustomerDetailController.php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailQuery;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/customers/{id}', methods: ['GET'])]
#[IsGranted(ContactsPermission::VIEW_CUSTOMERS->value)]
final class GetCustomerDetailController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $detail = $this->queryBus->dispatch(new GetCustomerDetailQuery($id));

        return new JsonResponse([
            'id'            => $detail->id,
            'email'         => $detail->email,
            'first_name'    => $detail->firstName,
            'last_name'     => $detail->lastName,
            'registered_at' => $detail->registeredAt,
        ]);
    }
}
```

- [ ] **Step 5: Implementuj DomainExceptionListener**

Mapuje doménové výjimky na HTTP odpovědi.

```php
<?php
// packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class DomainExceptionListener
{
    #[AsEventListener]
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Symfony HTTP výjimky necháme projít
        if ($exception instanceof HttpExceptionInterface) {
            return;
        }

        if ($exception instanceof \DomainException) {
            $message = $exception->getMessage();

            // CustomerNotFoundException → 404
            $status = str_contains($message, 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            $event->setResponse(new JsonResponse(
                ['error' => $message],
                $status,
            ));
        }
    }
}
```

- [ ] **Step 6: Vytvoř config/routes/crm_contacts.yaml**

```yaml
# config/routes/crm_contacts.yaml
crm_contacts:
    resource:
        path: '../../packages/crm/src/Contacts/Infrastructure/Http/'
        namespace: 'Crm\Contacts\Infrastructure\Http'
    type: attribute
```

- [ ] **Step 7: Ověř routes**

```bash
docker compose run --rm app php bin/console debug:router | grep crm
```

Očekáváno:
```
 POST   /api/crm/contacts/commands/register-customer
 PUT    /api/crm/contacts/commands/update-customer/{id}
 GET    /api/crm/contacts/customers
 GET    /api/crm/contacts/customers/{id}
```

- [ ] **Step 8: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Http/ packages/shared-kernel/src/Infrastructure/Http/ config/routes/
git commit -m "feat(crm): HTTP controllers + DomainExceptionListener + routes"
```

---

## Task 16: E2E test — RegisterCustomer HTTP API

**Files:**
- Create: `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php`

- [ ] **Step 1: Vytvoř phpunit.xml pro integrační/E2E testy v root projektu**

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Integration">
            <directory>packages/crm/tests/Contacts/Infrastructure</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="test"/>
        <env name="DATABASE_URL" value="postgresql://erp:erp_secret@db:5432/ddd_erp_test"/>
    </php>
</phpunit>
```

- [ ] **Step 2: Vytvoř testovací databázi**

```bash
docker compose run --rm app php bin/console doctrine:database:create --env=test
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction --env=test
```

- [ ] **Step 3: Napiš E2E test**

```php
<?php
// packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterCustomerControllerTest extends WebTestCase
{
    public function test_registers_customer_and_returns_201(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('admin:password'),
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'jan@firma.cz',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertNotEmpty($data['id']);
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('admin:password'),
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'not-an-email',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/crm/contacts/commands/register-customer');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 4: Spusť E2E testy**

```bash
docker compose run --rm app ./vendor/bin/phpunit --testdox
```

Očekáváno: 3 testy zelené.

- [ ] **Step 5: Spusť všechny unit testy (shared-kernel + crm)**

```bash
docker compose run --rm app sh -c "
  cd /app/packages/shared-kernel && ./vendor/bin/phpunit --testdox &&
  cd /app/packages/crm && ./vendor/bin/phpunit --testdox
"
```

Očekáváno: všechny testy zelené.

- [ ] **Step 6: Commit**

```bash
git add phpunit.xml packages/crm/tests/Contacts/Infrastructure/
git commit -m "test(crm): E2E testy pro RegisterCustomer HTTP API"
```

---

## Task 17: React frontend skeleton

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/.gitignore`

- [ ] **Step 1: Vytvoř React app skeleton**

```bash
cd frontend && npx create-react-app . --template typescript
```

Nebo s Vite (doporučeno pro rychlost):

```bash
npm create vite@latest frontend -- --template react-ts
cd frontend && npm install
```

- [ ] **Step 2: Přidej .gitignore pro frontend**

```
# frontend/.gitignore
node_modules/
dist/
.env.local
```

- [ ] **Step 3: Ověř frontend funguje**

```bash
cd frontend && npm run dev
```

Očekáváno: server na `http://localhost:5173`

- [ ] **Step 4: Commit**

```bash
git add frontend/
git commit -m "feat(frontend): React + TypeScript skeleton (Vite)"
```

---

## Závěrečná ověření

- [ ] Spusť všechny testy

```bash
# Unit testy
docker compose run --rm app sh -c "cd /app/packages/shared-kernel && ./vendor/bin/phpunit"
docker compose run --rm app sh -c "cd /app/packages/crm && ./vendor/bin/phpunit"
# E2E testy
docker compose run --rm app ./vendor/bin/phpunit
```

- [ ] Ověř Symfony container

```bash
docker compose run --rm app php bin/console debug:container --env=prod 2>&1 | grep -i error || echo "OK"
```

- [ ] Ověř routes

```bash
docker compose run --rm app php bin/console debug:router
```

Očekáváno: 4 CRM endpointy registrovány.

- [ ] Finální commit

```bash
git add .
git commit -m "feat: MVP kompletní — CRM Contacts BC s CQRS, HTTP API a testy"
git push
```
