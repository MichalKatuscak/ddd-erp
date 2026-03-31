# Integration Test Coverage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add integration tests for 8 untested GET/command controllers and domain error scenarios (duplicate email → 422, not found → 404) to existing command controller test files.

**Architecture:** PHP `WebTestCase` tests hit the real Symfony app against a PostgreSQL test database. Each test logs in as the seeded admin (`admin@erp.local` / `changeme`) to get a JWT, then exercises the endpoint. Domain error scenarios for CRM require adding a duplicate email domain check to `RegisterCustomerHandler` first (Identity already has this check). All tests follow existing patterns in the codebase.

**Tech Stack:** PHP 8.4, Symfony 7.2 WebTestCase, PHPUnit 11, PostgreSQL test DB (`ddd_erp_test`), JWT auth

---

## File Map

### Production files to modify (CRM duplicate email prerequisite)
- Modify: `packages/crm/src/Contacts/Domain/CustomerRepository.php` — add `findByEmail` method
- Modify: `packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php` — implement `findByEmail`
- Modify: `packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php` — add duplicate email check

### New test files (8 controllers)
- Create: `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerListControllerTest.php`
- Create: `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerDetailControllerTest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/GetUserListControllerTest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/GetUserDetailControllerTest.php`
- Create: `packages/identity/tests/Auth/Infrastructure/Http/GetCurrentUserControllerTest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/DeactivateUserControllerTest.php`
- Create: `packages/identity/tests/Role/Infrastructure/Http/GetRoleListControllerTest.php`
- Create: `packages/identity/tests/Role/Infrastructure/Http/GetRoleDetailControllerTest.php`

### Existing test files to modify (5 files — domain error additions)
- Modify: `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php` — add 422 duplicate email
- Modify: `packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php` — add 404 not found
- Modify: `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php` — add 422 duplicate email
- Modify: `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php` — add 404 not found
- Modify: `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php` — add 404 not found

---

## Task 1: Add CRM duplicate email domain check

The `RegisterCustomerHandler` currently has no duplicate email guard — it relies on the DB unique constraint, which surfaces as a 500. To make the "422 on duplicate email" test pass, we must add a domain-level check matching the pattern already used in `RegisterUserHandler`.

**Files:**
- Modify: `packages/crm/src/Contacts/Domain/CustomerRepository.php`
- Modify: `packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php`
- Modify: `packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php`

- [ ] **Step 1: Run tests to verify baseline**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration 2>&1 | tail -5
```

Expected: all existing tests pass.

- [ ] **Step 2: Add `findByEmail` to the CustomerRepository interface**

File: `packages/crm/src/Contacts/Domain/CustomerRepository.php`

Replace the entire file with:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

interface CustomerRepository
{
    /** @throws CustomerNotFoundException */
    public function get(CustomerId $id): Customer;

    public function findByEmail(CustomerEmail $email): ?Customer;

    public function save(Customer $customer): void;

    public function nextIdentity(): CustomerId;
}
```

- [ ] **Step 3: Implement `findByEmail` in DoctrineCustomerRepository**

File: `packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php`

Replace the entire file with:

```php
<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Persistence;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerEmail;
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

    public function findByEmail(CustomerEmail $email): ?Customer
    {
        return $this->entityManager
            ->getRepository(Customer::class)
            ->findOneBy(['email' => $email]);
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

- [ ] **Step 4: Add duplicate email check to RegisterCustomerHandler**

File: `packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php`

Replace the entire file with:

```php
<?php
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
        $email = CustomerEmail::fromString($command->email);
        if ($this->repository->findByEmail($email) !== null) {
            throw new \DomainException("Customer with email '{$command->email}' is already registered");
        }

        $customer = Customer::register(
            CustomerId::fromString($command->customerId),
            $email,
            CustomerName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($customer);

        foreach ($customer->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 5: Run tests to verify existing tests still pass**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration 2>&1 | tail -5
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/crm/src/Contacts/Domain/CustomerRepository.php \
        packages/crm/src/Contacts/Infrastructure/Persistence/DoctrineCustomerRepository.php \
        packages/crm/src/Contacts/Application/RegisterCustomer/RegisterCustomerHandler.php
git commit -m "feat(crm): add duplicate email domain check to RegisterCustomerHandler"
```

---

## Task 2: CRM domain error tests

Add the "422 on duplicate email" test to `RegisterCustomerControllerTest` and the "404 on non-existent customer" test to `UpdateCustomerControllerTest`.

**Files:**
- Modify: `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php`
- Modify: `packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php`

- [ ] **Step 1: Write the failing tests first — duplicate email for RegisterCustomer**

Open `packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php` and add the following test method at the end of the class (before the final `}`):

```php
    public function test_returns_422_on_duplicate_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $email = 'duplicate+' . uniqid() . '@firma.cz';

        // Register once — should succeed
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => $email,
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);

        // Register again with same email — should fail with 422 domain error
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => $email,
                'first_name' => 'Petr',
                'last_name'  => 'Dvořák',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/domain', $data['type']);
        $this->assertSame(422, $data['status']);
    }
```

- [ ] **Step 2: Write the failing test — 404 for UpdateCustomer**

Open `packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php` and add the following test method at the end of the class (before the final `}`):

```php
    public function test_returns_404_for_non_existent_customer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/00000000-0000-7000-8000-000000000001', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'new@test.cz', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }
```

- [ ] **Step 3: Run the new tests to verify they pass**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration --filter "RegisterCustomerControllerTest|UpdateCustomerControllerTest" 2>&1 | tail -10
```

Expected: all 6 tests pass (3 existing + 1 new in RegisterCustomerControllerTest, 2 existing + 1 new in UpdateCustomerControllerTest).

- [ ] **Step 4: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/crm/tests/Contacts/Infrastructure/Http/RegisterCustomerControllerTest.php \
        packages/crm/tests/Contacts/Infrastructure/Http/UpdateCustomerControllerTest.php
git commit -m "test(crm): add duplicate email and not-found domain error scenarios"
```

---

## Task 3: Identity domain error tests

Add "422 on duplicate email" to `RegisterUserControllerTest`, "404 not found" to `UpdateUserControllerTest`, and "404 not found" to `UpdateRolePermissionsControllerTest`.

**Files:**
- Modify: `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php`
- Modify: `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php`
- Modify: `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php`

- [ ] **Step 1: Write the failing test — duplicate email for RegisterUser**

Open `packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php` and add the following test method at the end of the class (before the final `}`):

```php
    public function test_returns_422_on_duplicate_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $email = 'dupuser+' . uniqid() . '@test.cz';

        // Register once — should succeed
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => $email,
            'password'   => 'password123',
            'first_name' => 'Jan',
            'last_name'  => 'Test',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Register again with same email — should fail with 422 domain error
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => $email,
            'password'   => 'password123',
            'first_name' => 'Petr',
            'last_name'  => 'Dvořák',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/domain', $data['type']);
        $this->assertSame(422, $data['status']);
    }
```

- [ ] **Step 2: Write the failing test — 404 for UpdateUser**

Open `packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php` and add the following test method at the end of the class (before the final `}`):

```php
    public function test_returns_404_for_non_existent_user(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/identity/users/commands/update-user/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['email' => 'new@test.cz', 'first_name' => 'Jan', 'last_name' => 'Test']));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }
```

- [ ] **Step 3: Write the failing test — 404 for UpdateRolePermissions**

Open `packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php` and add the following test method at the end of the class (before the final `}`):

```php
    public function test_returns_404_for_non_existent_role(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/identity/roles/commands/update-role-permissions/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['permissions' => ['manage_users']]));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }
```

- [ ] **Step 4: Run the new tests**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration --filter "RegisterUserControllerTest|UpdateUserControllerTest|UpdateRolePermissionsControllerTest" 2>&1 | tail -10
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/identity/tests/User/Infrastructure/Http/RegisterUserControllerTest.php \
        packages/identity/tests/User/Infrastructure/Http/UpdateUserControllerTest.php \
        packages/identity/tests/Role/Infrastructure/Http/UpdateRolePermissionsControllerTest.php
git commit -m "test(identity): add duplicate email and not-found domain error scenarios"
```

---

## Task 4: CRM GET controller tests

Create test files for `GetCustomerListController` and `GetCustomerDetailController`.

**Files:**
- Create: `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerListControllerTest.php`
- Create: `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerDetailControllerTest.php`

- [ ] **Step 1: Create GetCustomerListControllerTest**

Create `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerListControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCustomerListControllerTest extends WebTestCase
{
    public function test_returns_200_with_array(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create at least one customer so the list is non-trivially testable
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'list+' . uniqid() . '@firma.cz',
                'first_name' => 'Seznam',
                'last_name'  => 'Zákazník',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('full_name', $first);
        $this->assertArrayHasKey('registered_at', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 2: Create GetCustomerDetailControllerTest**

Create `packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerDetailControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCustomerDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_customer_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a customer to fetch
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'detail+' . uniqid() . '@firma.cz',
                'first_name' => 'Detail',
                'last_name'  => 'Test',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/crm/contacts/customers/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('registered_at', $data);
    }

    public function test_returns_404_for_non_existent_customer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/crm/contacts/customers/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 3: Run the new tests**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration --filter "GetCustomerListControllerTest|GetCustomerDetailControllerTest" 2>&1 | tail -10
```

Expected: 5 tests pass (2 + 3).

- [ ] **Step 4: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerListControllerTest.php \
        packages/crm/tests/Contacts/Infrastructure/Http/GetCustomerDetailControllerTest.php
git commit -m "test(crm): add GetCustomerList and GetCustomerDetail controller tests"
```

---

## Task 5: Identity User GET and DeactivateUser controller tests

Create test files for `GetUserListController`, `GetUserDetailController`, `GetCurrentUserController`, and `DeactivateUserController`.

**Files:**
- Create: `packages/identity/tests/User/Infrastructure/Http/GetUserListControllerTest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/GetUserDetailControllerTest.php`
- Create: `packages/identity/tests/Auth/Infrastructure/Http/GetCurrentUserControllerTest.php`
- Create: `packages/identity/tests/User/Infrastructure/Http/DeactivateUserControllerTest.php`

- [ ] **Step 1: Create GetUserListControllerTest**

Create `packages/identity/tests/User/Infrastructure/Http/GetUserListControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetUserListControllerTest extends WebTestCase
{
    public function test_returns_200_with_array(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data); // seeded admin always present
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('role_ids', $first);
        $this->assertArrayHasKey('active', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/users');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 2: Create GetUserDetailControllerTest**

Create `packages/identity/tests/User/Infrastructure/Http/GetUserDetailControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetUserDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_user_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a fresh user to fetch details
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'userdetail+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'Detail',
            'last_name'  => 'User',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/identity/users/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('role_ids', $data);
        $this->assertArrayHasKey('active', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_returns_404_for_non_existent_user(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/users/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/users/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 3: Create GetCurrentUserControllerTest**

Create `packages/identity/tests/Auth/Infrastructure/Http/GetCurrentUserControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCurrentUserControllerTest extends WebTestCase
{
    public function test_returns_200_with_current_user_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertSame('admin@erp.local', $data['email']);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertIsArray($data['permissions']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 4: Create DeactivateUserControllerTest**

Create `packages/identity/tests/User/Infrastructure/Http/DeactivateUserControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeactivateUserControllerTest extends WebTestCase
{
    public function test_deactivates_user_and_returns_204(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a fresh user to deactivate (do NOT deactivate the seeded admin)
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'todeactivate+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'To',
            'last_name'  => 'Deactivate',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $userId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request(
            method: 'POST',
            uri: '/api/identity/users/commands/deactivate-user/' . $userId,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseStatusCodeSame(204);
    }

    public function test_returns_404_for_non_existent_user(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request(
            method: 'POST',
            uri: '/api/identity/users/commands/deactivate-user/00000000-0000-7000-8000-000000000001',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/users/commands/deactivate-user/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 5: Run the new tests**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration --filter "GetUserListControllerTest|GetUserDetailControllerTest|GetCurrentUserControllerTest|DeactivateUserControllerTest" 2>&1 | tail -10
```

Expected: 10 tests pass (2 + 3 + 2 + 3).

- [ ] **Step 6: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/identity/tests/User/Infrastructure/Http/GetUserListControllerTest.php \
        packages/identity/tests/User/Infrastructure/Http/GetUserDetailControllerTest.php \
        packages/identity/tests/Auth/Infrastructure/Http/GetCurrentUserControllerTest.php \
        packages/identity/tests/User/Infrastructure/Http/DeactivateUserControllerTest.php
git commit -m "test(identity): add user GET and DeactivateUser controller tests"
```

---

## Task 6: Identity Role GET controller tests

Create test files for `GetRoleListController` and `GetRoleDetailController`.

**Files:**
- Create: `packages/identity/tests/Role/Infrastructure/Http/GetRoleListControllerTest.php`
- Create: `packages/identity/tests/Role/Infrastructure/Http/GetRoleDetailControllerTest.php`

- [ ] **Step 1: Create GetRoleListControllerTest**

Create `packages/identity/tests/Role/Infrastructure/Http/GetRoleListControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetRoleListControllerTest extends WebTestCase
{
    public function test_returns_200_with_array(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a role so the list is non-trivially testable
        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'TestRole ' . uniqid(),
            'permissions' => ['view_customers'],
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/identity/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('permissions', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/roles');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 2: Create GetRoleDetailControllerTest**

Create `packages/identity/tests/Role/Infrastructure/Http/GetRoleDetailControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetRoleDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_role_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a role to fetch
        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'DetailRole ' . uniqid(),
            'permissions' => ['view_customers', 'view_users'],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/identity/roles/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertIsArray($data['permissions']);
    }

    public function test_returns_404_for_non_existent_role(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/roles/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/roles/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 3: Run the new tests**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration --filter "GetRoleListControllerTest|GetRoleDetailControllerTest" 2>&1 | tail -10
```

Expected: 5 tests pass (2 + 3).

- [ ] **Step 4: Run the full integration test suite to confirm nothing is broken**

```bash
cd /home/michal/ddd-erp
./vendor/bin/phpunit --testsuite Integration 2>&1 | tail -10
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
cd /home/michal/ddd-erp
git add packages/identity/tests/Role/Infrastructure/Http/GetRoleListControllerTest.php \
        packages/identity/tests/Role/Infrastructure/Http/GetRoleDetailControllerTest.php
git commit -m "test(identity): add GetRoleList and GetRoleDetail controller tests"
```

---

## Spec self-review

- [x] **GetCustomerList** — Task 4 ✓
- [x] **GetCustomerDetail** (200 + 404 + 401) — Task 4 ✓
- [x] **GetUserList** — Task 5 ✓
- [x] **GetUserDetail** (200 + 404 + 401) — Task 5 ✓
- [x] **GetCurrentUser** (200 + 401) — Task 5 ✓
- [x] **DeactivateUser** (204 + 404 + 401) — Task 5 ✓
- [x] **GetRoleList** — Task 6 ✓
- [x] **GetRoleDetail** (200 + 404 + 401) — Task 6 ✓
- [x] **RegisterCustomer 422 duplicate email** — Task 1 (domain) + Task 2 (test) ✓
- [x] **RegisterUser 422 duplicate email** — Task 3 ✓
- [x] **UpdateCustomer 404 not found** — Task 2 ✓
- [x] **UpdateUser 404 not found** — Task 3 ✓
- [x] **UpdateRolePermissions 404 not found** — Task 3 ✓
- [x] Non-existent UUID `00000000-0000-7000-8000-000000000001` used for all 404 tests ✓
- [x] Planning module out of scope ✓
- [x] RFC 7807 format asserted (`type`, `status`) in all 404 and 422 domain error tests ✓
