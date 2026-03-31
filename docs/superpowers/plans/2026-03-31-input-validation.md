# Input Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add RFC 7807 HTTP input validation across all bounded contexts using Symfony Validator + `#[MapRequestPayload]` Request DTOs.

**Architecture:** Two-layer validation — Request DTOs with Symfony Validator constraints deserialized automatically via `#[MapRequestPayload]`; domain invariants stay in Value Objects. All errors formatted as RFC 7807 Problem Details by `DomainExceptionListener`.

**Tech Stack:** PHP 8.4, Symfony 7.2, symfony/validator, `#[MapRequestPayload]`, PHPUnit 11, WebTestCase

---

## File Map

**New files:**
- `packages/crm/src/Contacts/Infrastructure/Http/Request/RegisterCustomerRequest.php`
- `packages/crm/src/Contacts/Infrastructure/Http/Request/UpdateCustomerRequest.php`
- `packages/identity/src/Auth/Infrastructure/Http/Request/LoginRequest.php`
- `packages/identity/src/Auth/Infrastructure/Http/Request/RefreshAccessTokenRequest.php`
- `packages/identity/src/Auth/Infrastructure/Http/Request/LogoutRequest.php`
- `packages/identity/src/User/Infrastructure/Http/Request/RegisterUserRequest.php`
- `packages/identity/src/User/Infrastructure/Http/Request/UpdateUserRequest.php`
- `packages/identity/src/User/Infrastructure/Http/Request/AssignRolesToUserRequest.php`
- `packages/identity/src/Role/Infrastructure/Http/Request/CreateRoleRequest.php`
- `packages/identity/src/Role/Infrastructure/Http/Request/UpdateRolePermissionsRequest.php`
- `packages/identity/tests/Auth/Infrastructure/Http/LoginControllerTest.php`
- `packages/identity/tests/Auth/Infrastructure/Http/LogoutControllerTest.php`
- `packages/identity/tests/Auth/Infrastructure/Http/RefreshAccessTokenControllerTest.php`
- `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php`
- `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php`
- `packages/identity/tests/User/Infrastructure/Http/AssignRolesToUserControllerTest.php`
- `packages/identity/tests/Role/Infrastructure/Http/CreateRoleControllerTest.php`
- `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php`

**Modified files:**
- `composer.json` — add `symfony/validator`
- `phpunit.xml` — add Identity integration test directories
- `packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php` — RFC 7807 format
- `packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php`
- `packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php`
- `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php` — update assertions to RFC 7807
- `packages/identity/src/Auth/Infrastructure/Http/LoginController.php`
- `packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php`
- `packages/identity/src/Auth/Infrastructure/Http/LogoutController.php`
- `packages/identity/src/User/Infrastructure/Http/RegisterUserController.php`
- `packages/identity/src/User/Infrastructure/Http/UpdateUserController.php`
- `packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php`
- `packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php`
- `packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php`

---

## Task 1: Install symfony/validator and expand phpunit.xml

**Files:**
- Modify: `composer.json`
- Modify: `phpunit.xml`

- [ ] **Step 1: Add symfony/validator to root composer.json**

In `composer.json`, add to `"require"`:
```json
"symfony/validator": "^7.2"
```

- [ ] **Step 2: Install the dependency**

Run:
```bash
composer require symfony/validator
```
Expected: symfony/validator installed in vendor/symfony/validator

- [ ] **Step 3: Expand phpunit.xml Integration testsuite**

Replace the entire `phpunit.xml` content:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Integration">
            <directory>packages/crm/tests/Contacts/Infrastructure</directory>
            <directory>packages/identity/tests/Auth/Infrastructure</directory>
            <directory>packages/identity/tests/User/Infrastructure</directory>
            <directory>packages/identity/tests/Role/Infrastructure</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/Identity/E2e</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="test"/>
        <env name="DATABASE_URL" value="postgresql://erp:erp_secret@db:5432/ddd_erp_test"/>
        <env name="KERNEL_CLASS" value="App\Kernel"/>
        <env name="JWT_SECRET" value="test_jwt_secret_at_least_32_characters_long"/>
        <env name="JWT_TTL" value="900"/>
        <env name="APP_SECRET" value="test_app_secret_32chars_minimum_x"/>
    </php>
</phpunit>
```

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock phpunit.xml
git commit -m "chore: install symfony/validator, expand Integration testsuite"
```

---

## Task 2: Extend DomainExceptionListener to RFC 7807

**Files:**
- Modify: `packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php`
- Modify: `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php`

**How Symfony 7 validation failures work:** When `#[MapRequestPayload]` fails validation, Symfony throws `UnprocessableEntityHttpException` whose `getPrevious()` is `Symfony\Component\Validator\Exception\ValidationFailedException` containing the `ConstraintViolationList`.

- [ ] **Step 1: Write a failing integration test for RFC 7807 format**

Update `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php`. Replace the `test_returns_422_on_invalid_email` method:

```php
public function test_returns_422_on_invalid_email(): void
{
    $client = static::createClient();

    $client->request('POST', '/api/identity/commands/login', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'email'    => 'admin@erp.local',
        'password' => 'changeme',
    ]));
    $loginData = json_decode($client->getResponse()->getContent(), true);
    $token = $loginData['access_token'];

    $client->request(
        method: 'POST',
        uri: '/api/crm/contacts/commands/register-customer',
        server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
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
    $this->assertSame('/errors/validation', $data['type']);
    $this->assertSame('Validation Failed', $data['title']);
    $this->assertSame(422, $data['status']);
    $this->assertArrayHasKey('violations', $data);
    $this->assertArrayHasKey('email', $data['violations']);
}
```

Also add a test for missing fields:

```php
public function test_returns_422_on_missing_fields(): void
{
    $client = static::createClient();

    $client->request('POST', '/api/identity/commands/login', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'email'    => 'admin@erp.local',
        'password' => 'changeme',
    ]));
    $loginData = json_decode($client->getResponse()->getContent(), true);
    $token = $loginData['access_token'];

    $client->request(
        method: 'POST',
        uri: '/api/crm/contacts/commands/register-customer',
        server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ],
        content: json_encode([]),
    );

    $this->assertResponseStatusCodeSame(422);
    $data = json_decode($client->getResponse()->getContent(), true);
    $this->assertSame('/errors/validation', $data['type']);
    $this->assertArrayHasKey('violations', $data);
}
```

- [ ] **Step 2: Run to confirm current failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter RegisterCustomerControllerTest`

Expected: FAIL — tests look for `type`, `title`, `status`, `violations` keys that don't exist yet.

- [ ] **Step 3: Update DomainExceptionListener**

Replace the entire content of `packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php`:

```php
<?php
declare(strict_types=1);

namespace SharedKernel\Infrastructure\Http;

use SharedKernel\Domain\UncaughtDomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class DomainExceptionListener
{
    #[AsEventListener]
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle MapRequestPayload validation failures → RFC 7807 with violations
        if ($exception instanceof UnprocessableEntityHttpException) {
            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $violations = [];
                foreach ($previous->getViolations() as $violation) {
                    $property = $violation->getPropertyPath();
                    $violations[$property][] = $violation->getMessage();
                }
                $event->setResponse(new JsonResponse([
                    'type'       => '/errors/validation',
                    'title'      => 'Validation Failed',
                    'status'     => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'violations' => $violations,
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
                return;
            }
        }

        // Let non-validation HTTP exceptions pass through
        if ($exception instanceof HttpExceptionInterface) {
            return;
        }

        // Unwrap Messenger HandlerFailedException to get the original domain exception
        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getWrappedExceptions();
            if (!empty($nested)) {
                $exception = reset($nested);
            }
        }

        // Domain exceptions that declare their own HTTP status code (e.g. 401)
        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'type'   => '/errors/domain',
                'title'  => 'Request Failed',
                'status' => $exception->getStatusCode(),
                'detail' => $exception->getMessage(),
            ], $exception->getStatusCode(), $exception->getHeaders()));
            return;
        }

        // Exceptions marked as UncaughtDomainException should propagate as 500
        if ($exception instanceof UncaughtDomainException) {
            return;
        }

        if ($exception instanceof \DomainException) {
            $message = $exception->getMessage();

            $isNotFound = str_contains(strtolower($message), 'not found');
            $status = $isNotFound
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            $type = $isNotFound ? '/errors/not-found' : '/errors/domain';
            $title = $isNotFound ? 'Resource Not Found' : 'Business Rule Violation';

            $event->setResponse(new JsonResponse([
                'type'   => $type,
                'title'  => $title,
                'status' => $status,
                'detail' => $message,
            ], $status));
        }
    }
}
```

- [ ] **Step 4: Run tests — they still fail (DTO not created yet, so validation doesn't fire)**

Run: `vendor/bin/phpunit --testsuite Integration --filter RegisterCustomerControllerTest`

Expected: test_returns_422_on_invalid_email still fails because controller still reads raw JSON (no DTO, no validation). test_registers_customer still passes.

- [ ] **Step 5: Commit the listener change**

```bash
git add packages/shared-kernel/src/Infrastructure/Http/DomainExceptionListener.php \
        packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php
git commit -m "feat: RFC 7807 error format in DomainExceptionListener"
```

---

## Task 3: CRM — RegisterCustomerRequest DTO

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Http/Request/RegisterCustomerRequest.php`
- Modify: `packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php`

- [ ] **Step 1: Create RegisterCustomerRequest**

Create `packages/crm/src/Contacts/Infrastructure/Http/Request/RegisterCustomerRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterCustomerRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
```

- [ ] **Step 2: Update RegisterCustomerController**

Replace the entire content of `packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Infrastructure\Http\Request\RegisterCustomerRequest;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/register-customer', methods: ['POST'])]
#[IsGranted(ContactsPermission::CREATE_CUSTOMER->value)]
final class RegisterCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] RegisterCustomerRequest $request): JsonResponse
    {
        $customerId = CustomerId::generate()->value();

        $this->commandBus->dispatch(new RegisterCustomerCommand(
            customerId: $customerId,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(['id' => $customerId], Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 3: Run integration tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter RegisterCustomerControllerTest`

Expected: All 4 tests PASS (registers_customer, invalid_email, missing_fields, unauthorized).

- [ ] **Step 4: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Http/Request/RegisterCustomerRequest.php \
        packages/crm/src/Contacts/Infrastructure/Http/RegisterCustomerController.php
git commit -m "feat(crm): RegisterCustomerRequest DTO with MapRequestPayload validation"
```

---

## Task 4: CRM — UpdateCustomerRequest DTO

**Files:**
- Create: `packages/crm/src/Contacts/Infrastructure/Http/Request/UpdateCustomerRequest.php`
- Modify: `packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php`

- [ ] **Step 1: Add failing integration test**

Add to `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php` (or create `UpdateCustomerControllerTest.php` — create a new file):

Create `packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateCustomerControllerTest extends WebTestCase
{
    private function getAuthToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken();

        // First create a customer to get an ID
        $client->request('POST', '/api/crm/contacts/commands/register-customer',
            [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'update+' . uniqid() . '@test.cz', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/' . $id,
            [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_fields(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken();

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/00000000-0000-0000-0000-000000000001',
            [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter UpdateCustomerControllerTest`

Expected: FAIL — no validation in place yet.

- [ ] **Step 3: Create UpdateCustomerRequest**

Create `packages/crm/src/Contacts/Infrastructure/Http/Request/UpdateCustomerRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCustomerRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
```

- [ ] **Step 4: Update UpdateCustomerController**

Replace the entire content of `packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http;

use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerCommand;
use Crm\Contacts\Infrastructure\Http\Request\UpdateCustomerRequest;
use Crm\Contacts\Infrastructure\Security\ContactsPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/crm/contacts/commands/update-customer/{id}', methods: ['PUT'])]
#[IsGranted(ContactsPermission::UPDATE_CUSTOMER->value)]
final class UpdateCustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateCustomerCommand(
            customerId: $id,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter UpdateCustomerControllerTest`

Expected: All tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/crm/src/Contacts/Infrastructure/Http/Request/UpdateCustomerRequest.php \
        packages/crm/src/Contacts/Infrastructure/Http/UpdateCustomerController.php \
        packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php
git commit -m "feat(crm): UpdateCustomerRequest DTO with MapRequestPayload validation"
```

---

## Task 5: Identity Auth — LoginRequest DTO

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Http/Request/LoginRequest.php`
- Create: `packages/identity/tests/Auth/Infrastructure/Http/LoginControllerTest.php`
- Modify: `packages/identity/src/Auth/Infrastructure/Http/LoginController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/Auth/Infrastructure/Http/LoginControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginControllerTest extends WebTestCase
{
    public function test_returns_422_on_missing_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['password' => 'changeme']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_invalid_email_format(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'not-email', 'password' => 'changeme']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_password(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('password', $data['violations']);
    }

    public function test_login_success(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $data);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter LoginControllerTest`

Expected: FAIL — directory doesn't exist yet, or tests fail on response format.

- [ ] **Step 3: Create LoginRequest**

Create `packages/identity/src/Auth/Infrastructure/Http/Request/LoginRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        public readonly string $password = '',
    ) {}
}
```

- [ ] **Step 4: Update LoginController**

Replace the entire content of `packages/identity/src/Auth/Infrastructure/Http/LoginController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Login\LoginQuery;
use Identity\Auth\Infrastructure\Http\Request\LoginRequest;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(#[MapRequestPayload] LoginRequest $request): JsonResponse
    {
        /** @var \Identity\Auth\Application\Login\LoginResultDTO $result */
        $result = $this->queryBus->dispatch(new LoginQuery(
            email: $request->email,
            password: $request->password,
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter LoginControllerTest`

Expected: All 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/Auth/Infrastructure/Http/Request/LoginRequest.php \
        packages/identity/src/Auth/Infrastructure/Http/LoginController.php \
        packages/identity/tests/Auth/Infrastructure/Http/LoginControllerTest.php
git commit -m "feat(identity): LoginRequest DTO with MapRequestPayload validation"
```

---

## Task 6: Identity Auth — RefreshAccessTokenRequest DTO

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Http/Request/RefreshAccessTokenRequest.php`
- Create: `packages/identity/tests/Auth/Infrastructure/Http/RefreshAccessTokenControllerTest.php`
- Modify: `packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/Auth/Infrastructure/Http/RefreshAccessTokenControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RefreshAccessTokenControllerTest extends WebTestCase
{
    public function test_returns_422_on_missing_refresh_token(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }

    public function test_returns_422_on_invalid_uuid(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refresh_token' => 'not-a-uuid']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter RefreshAccessTokenControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create RefreshAccessTokenRequest**

Create `packages/identity/src/Auth/Infrastructure/Http/Request/RefreshAccessTokenRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RefreshAccessTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $refresh_token = '',
    ) {}
}
```

- [ ] **Step 4: Update RefreshAccessTokenController**

Replace the entire content of `packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\RefreshAccessToken\RefreshAccessTokenQuery;
use Identity\Auth\Infrastructure\Http\Request\RefreshAccessTokenRequest;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/refresh-token', methods: ['POST'])]
final class RefreshAccessTokenController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function __invoke(#[MapRequestPayload] RefreshAccessTokenRequest $request): JsonResponse
    {
        /** @var \Identity\Auth\Application\RefreshAccessToken\RefreshResultDTO $result */
        $result = $this->queryBus->dispatch(new RefreshAccessTokenQuery(
            refreshToken: $request->refresh_token,
        ));

        return new JsonResponse([
            'access_token'  => $result->accessToken,
            'refresh_token' => $result->refreshToken,
            'expires_in'    => $result->expiresIn,
        ]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter RefreshAccessTokenControllerTest`

Expected: All 2 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/Auth/Infrastructure/Http/Request/RefreshAccessTokenRequest.php \
        packages/identity/src/Auth/Infrastructure/Http/RefreshAccessTokenController.php \
        packages/identity/tests/Auth/Infrastructure/Http/RefreshAccessTokenControllerTest.php
git commit -m "feat(identity): RefreshAccessTokenRequest DTO with validation"
```

---

## Task 7: Identity Auth — LogoutRequest DTO

**Files:**
- Create: `packages/identity/src/Auth/Infrastructure/Http/Request/LogoutRequest.php`
- Create: `packages/identity/tests/Auth/Infrastructure/Http/LogoutControllerTest.php`
- Modify: `packages/identity/src/Auth/Infrastructure/Http/LogoutController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/Auth/Infrastructure/Http/LogoutControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LogoutControllerTest extends WebTestCase
{
    public function test_returns_422_on_missing_refresh_token(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }

    public function test_returns_422_on_invalid_uuid(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refresh_token' => 'not-a-uuid']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter LogoutControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create LogoutRequest**

Create `packages/identity/src/Auth/Infrastructure/Http/Request/LogoutRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LogoutRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $refresh_token = '',
    ) {}
}
```

- [ ] **Step 4: Update LogoutController**

Replace the entire content of `packages/identity/src/Auth/Infrastructure/Http/LogoutController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http;

use Identity\Auth\Application\Logout\LogoutCommand;
use Identity\Auth\Infrastructure\Http\Request\LogoutRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/commands/logout', methods: ['POST'])]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] LogoutRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new LogoutCommand(
            refreshToken: $request->refresh_token,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter LogoutControllerTest`

Expected: All 2 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/Auth/Infrastructure/Http/Request/LogoutRequest.php \
        packages/identity/src/Auth/Infrastructure/Http/LogoutController.php \
        packages/identity/tests/Auth/Infrastructure/Http/LogoutControllerTest.php
git commit -m "feat(identity): LogoutRequest DTO with validation"
```

---

## Task 8: Identity User — RegisterUserRequest DTO

**Files:**
- Create: `packages/identity/src/User/Infrastructure/Http/Request/RegisterUserRequest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php`
- Modify: `packages/identity/src/User/Infrastructure/Http/RegisterUserController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterUserControllerTest extends WebTestCase
{
    private function getAdminToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'not-an-email',
            'password'   => 'password123',
            'first_name' => 'Jan',
            'last_name'  => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_fields(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }

    public function test_registers_user_and_returns_201(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'newuser+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'Nový',
            'last_name'  => 'Uživatel',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter RegisterUserControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create RegisterUserRequest**

Create `packages/identity/src/User/Infrastructure/Http/Request/RegisterUserRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public readonly string $password = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
```

- [ ] **Step 4: Update RegisterUserController**

Replace the entire content of `packages/identity/src/User/Infrastructure/Http/RegisterUserController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\RegisterUser\RegisterUserCommand;
use Identity\User\Domain\UserId;
use Identity\User\Infrastructure\Http\Request\RegisterUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/register-user', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class RegisterUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] RegisterUserRequest $request): JsonResponse
    {
        $userId = UserId::generate()->value();

        $this->commandBus->dispatch(new RegisterUserCommand(
            userId: $userId,
            email: $request->email,
            password: $request->password,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(['id' => $userId], Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter RegisterUserControllerTest`

Expected: All 3 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/User/Infrastructure/Http/Request/RegisterUserRequest.php \
        packages/identity/src/User/Infrastructure/Http/RegisterUserController.php \
        packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php
git commit -m "feat(identity): RegisterUserRequest DTO with validation"
```

---

## Task 9: Identity User — UpdateUserRequest DTO

**Files:**
- Create: `packages/identity/src/User/Infrastructure/Http/Request/UpdateUserRequest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php`
- Modify: `packages/identity/src/User/Infrastructure/Http/UpdateUserController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateUserControllerTest extends WebTestCase
{
    private function getAdminToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('PUT', '/api/identity/users/commands/update-user/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'not-an-email',
            'first_name' => 'Jan',
            'last_name'  => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_fields(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('PUT', '/api/identity/users/commands/update-user/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter UpdateUserControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create UpdateUserRequest**

Create `packages/identity/src/User/Infrastructure/Http/Request/UpdateUserRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
```

- [ ] **Step 4: Update UpdateUserController**

Replace the entire content of `packages/identity/src/User/Infrastructure/Http/UpdateUserController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\UpdateUser\UpdateUserCommand;
use Identity\User\Infrastructure\Http\Request\UpdateUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/update-user/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] UpdateUserRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateUserCommand(
            userId: $id,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter UpdateUserControllerTest`

Expected: All 2 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/User/Infrastructure/Http/Request/UpdateUserRequest.php \
        packages/identity/src/User/Infrastructure/Http/UpdateUserController.php \
        packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php
git commit -m "feat(identity): UpdateUserRequest DTO with validation"
```

---

## Task 10: Identity User — AssignRolesToUserRequest DTO

**Files:**
- Create: `packages/identity/src/User/Infrastructure/Http/Request/AssignRolesToUserRequest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/AssignRolesToUserControllerTest.php`
- Modify: `packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/User/Infrastructure/Http/AssignRolesToUserControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssignRolesToUserControllerTest extends WebTestCase
{
    private function getAdminToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_when_role_ids_is_missing(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/users/commands/assign-roles/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('role_ids', $data['violations']);
    }

    public function test_returns_422_when_role_ids_contains_invalid_uuid(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/users/commands/assign-roles/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['role_ids' => ['not-a-uuid']]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }

    public function test_accepts_empty_role_ids_array(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        // Get an existing user ID first
        $client->request('GET', '/api/identity/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $users = json_decode($client->getResponse()->getContent(), true);
        $userId = $users[0]['id'];

        $client->request('POST', '/api/identity/users/commands/assign-roles/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['role_ids' => []]));

        // Empty array is valid (removes all roles) — should not be 422
        $this->assertNotSame(422, $client->getResponse()->getStatusCode());
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter AssignRolesToUserControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create AssignRolesToUserRequest**

Create `packages/identity/src/User/Infrastructure/Http/Request/AssignRolesToUserRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignRolesToUserRequest
{
    public function __construct(
        // Null check only — empty array is valid (removes all roles from user)
        #[Assert\NotNull]
        #[Assert\All([new Assert\Uuid()])]
        public readonly ?array $role_ids = null,
    ) {}
}
```

- [ ] **Step 4: Update AssignRolesToUserController**

Replace the entire content of `packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\User\Application\AssignRolesToUser\AssignRolesToUserCommand;
use Identity\User\Infrastructure\Http\Request\AssignRolesToUserRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/users/commands/assign-roles/{id}', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_USERS->value)]
final class AssignRolesToUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] AssignRolesToUserRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new AssignRolesToUserCommand(
            userId: $id,
            roleIds: $request->role_ids ?? [],
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter AssignRolesToUserControllerTest`

Expected: All 3 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/User/Infrastructure/Http/Request/AssignRolesToUserRequest.php \
        packages/identity/src/User/Infrastructure/Http/AssignRolesToUserController.php \
        packages/identity/tests/User/Infrastructure/Http/AssignRolesToUserControllerTest.php
git commit -m "feat(identity): AssignRolesToUserRequest DTO with validation"
```

---

## Task 11: Identity Role — CreateRoleRequest DTO

**Files:**
- Create: `packages/identity/src/Role/Infrastructure/Http/Request/CreateRoleRequest.php`
- Create: `packages/identity/tests/Role/Infrastructure/Http/CreateRoleControllerTest.php`
- Modify: `packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/Role/Infrastructure/Http/CreateRoleControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateRoleControllerTest extends WebTestCase
{
    private function getAdminToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_on_missing_name(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['permissions' => ['crm.contacts.view_customers']]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('name', $data['violations']);
    }

    public function test_returns_422_on_empty_permissions(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['name' => 'Test Role', 'permissions' => []]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('permissions', $data['violations']);
    }

    public function test_creates_role_and_returns_201(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'Test Role ' . uniqid(),
            'permissions' => ['crm.contacts.view_customers'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter CreateRoleControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create CreateRoleRequest**

Create `packages/identity/src/Role/Infrastructure/Http/Request/CreateRoleRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $name = '',

        #[Assert\NotNull]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\NotBlank()])]
        public readonly ?array $permissions = null,
    ) {}
}
```

- [ ] **Step 4: Update CreateRoleController**

Replace the entire content of `packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Domain\RoleId;
use Identity\Role\Infrastructure\Http\Request\CreateRoleRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/create-role', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class CreateRoleController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] CreateRoleRequest $request): JsonResponse
    {
        $roleId = RoleId::generate()->value();

        $this->commandBus->dispatch(new CreateRoleCommand(
            roleId: $roleId,
            name: $request->name,
            permissions: $request->permissions ?? [],
        ));

        return new JsonResponse(['id' => $roleId], Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --testsuite Integration --filter CreateRoleControllerTest`

Expected: All 3 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/Role/Infrastructure/Http/Request/CreateRoleRequest.php \
        packages/identity/src/Role/Infrastructure/Http/CreateRoleController.php \
        packages/identity/tests/Role/Infrastructure/Http/CreateRoleControllerTest.php
git commit -m "feat(identity): CreateRoleRequest DTO with validation"
```

---

## Task 12: Identity Role — UpdateRolePermissionsRequest DTO

**Files:**
- Create: `packages/identity/src/Role/Infrastructure/Http/Request/UpdateRolePermissionsRequest.php`
- Create: `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php`
- Modify: `packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php`

- [ ] **Step 1: Create failing integration test**

Create `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateRolePermissionsControllerTest extends WebTestCase
{
    private function getAdminToken(): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        return json_decode($client->getResponse()->getContent(), true)['access_token'];
    }

    public function test_returns_422_on_missing_permissions(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('PUT', '/api/identity/roles/commands/update-role-permissions/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('permissions', $data['violations']);
    }

    public function test_returns_422_on_empty_permissions(): void
    {
        $client = static::createClient();
        $token = $this->getAdminToken();

        $client->request('PUT', '/api/identity/roles/commands/update-role-permissions/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['permissions' => []]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('permissions', $data['violations']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

Run: `vendor/bin/phpunit --testsuite Integration --filter UpdateRolePermissionsControllerTest`

Expected: FAIL.

- [ ] **Step 3: Create UpdateRolePermissionsRequest**

Create `packages/identity/src/Role/Infrastructure/Http/Request/UpdateRolePermissionsRequest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateRolePermissionsRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\NotBlank()])]
        public readonly ?array $permissions = null,
    ) {}
}
```

- [ ] **Step 4: Update UpdateRolePermissionsController**

Replace the entire content of `packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsCommand;
use Identity\Role\Infrastructure\Http\Request\UpdateRolePermissionsRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/update-role-permissions/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class UpdateRolePermissionsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] UpdateRolePermissionsRequest $request, string $id): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateRolePermissionsCommand(
            roleId: $id,
            permissions: $request->permissions ?? [],
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 5: Run all integration tests**

Run: `vendor/bin/phpunit --testsuite Integration`

Expected: All tests in all suites PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/identity/src/Role/Infrastructure/Http/Request/UpdateRolePermissionsRequest.php \
        packages/identity/src/Role/Infrastructure/Http/UpdateRolePermissionsController.php \
        packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php
git commit -m "feat(identity): UpdateRolePermissionsRequest DTO with validation"
```

---

## Final Verification

- [ ] **Run full Integration testsuite**

```bash
vendor/bin/phpunit --testsuite Integration
```

Expected: All tests PASS with no failures.

- [ ] **Smoke check: valid request still works**

The login flow still works end-to-end (this is tested in LoginControllerTest::test_login_success).

- [ ] **Final commit if any cleanup needed**

```bash
git status
# If clean, nothing to do. If there are uncommitted changes:
git add -p
git commit -m "chore: input validation cleanup"
```
