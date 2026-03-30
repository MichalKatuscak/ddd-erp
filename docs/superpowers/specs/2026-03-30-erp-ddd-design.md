# ERP Systém — DDD + Symfony Design Spec

**Datum:** 2026-03-30
**Status:** Schváleno
**MVP:** CRM (Contacts BC)

---

## 1. Přehled projektu

Modulární interní ERP systém pro jednoho zákazníka (single-tenant) postavený na Domain-Driven Design a CQRS architektuře v Symfony 8 / PHP 8.4+.

Systém pokrývá 4 domény:
- **CRM** — správa zákazníků, obchodní pipeline, aktivity
- **Projektové řízení** — projekty, úkoly, výkazy práce
- **E-shop** — katalog, ceník, sklad, objednávky, platby
- **Plánování výroby** — kusovníky, výrobní příkazy, kapacity, kvalita

Moduly lze individuálně zapínat a vypínat bez zásahu do kódu.

---

## 2. Technický stack

| Vrstva | Technologie |
|--------|-------------|
| Backend | PHP 8.4+, Symfony 8 |
| Databáze | PostgreSQL |
| Messaging | Symfony Messenger (3 busy) |
| ORM | Doctrine ORM (XML mapping) |
| API dokumentace | nelmio/api-doc-bundle (OpenAPI) |
| Frontend | Samostatný SPA repozitář (JSON API) |
| Testy | PHPUnit, reálná PostgreSQL v Docker |

---

## 3. Architektura

### 3.1 Fyzická struktura — Composer packages

Každá doména je samostatný Composer balíček v `/packages/`. Symfony aplikace v `/app/` je pouze "glue" vrstva.

```
ddd-erp/
├── app/
│   ├── config/
│   │   ├── bundles.php
│   │   ├── packages/
│   │   └── routes/
│   ├── public/
│   └── src/
│       └── Kernel.php
├── packages/
│   ├── shared-kernel/
│   │   ├── composer.json
│   │   └── src/
│   │       ├── Domain/           # AggregateRoot, DomainEvent, ValueObject base classes
│   │       ├── Application/      # CommandBus, QueryBus, EventBus interfaces
│   │       ├── Infrastructure/   # Messenger implementace, base Repository
│   │       └── Saga/             # SagaManager, base Saga class
│   ├── crm/
│   │   ├── composer.json
│   │   └── src/
│   │       ├── Contacts/
│   │       ├── SalesPipeline/
│   │       └── Activities/
│   ├── projects/
│   │   └── src/
│   │       ├── Projects/
│   │       ├── Tasks/
│   │       └── TimeTracking/
│   ├── eshop/
│   │   └── src/
│   │       ├── Catalog/
│   │       ├── Pricing/
│   │       ├── Inventory/
│   │       ├── Orders/
│   │       └── Payments/
│   ├── manufacturing/
│   │   └── src/
│   │       ├── BillOfMaterials/
│   │       ├── ProductionOrders/
│   │       ├── CapacityPlanning/
│   │       └── QualityControl/
│   ├── notifications/
│   └── reporting/
├── composer.json
└── docker-compose.yml
```

### 3.2 Bounded Contexts (~18 celkem)

| Doména | Bounded Contexts |
|--------|-----------------|
| Shared Kernel | Identity & Access, Organization |
| CRM | Contacts, SalesPipeline, Activities |
| Projektové řízení | Projects, Tasks, TimeTracking |
| E-shop | Catalog, Pricing, Inventory, Orders, Payments |
| Výroba | BillOfMaterials, ProductionOrders, CapacityPlanning, QualityControl |
| Podpora | Notifications, Reporting |

### 3.3 Interní struktura Bounded Context

Každý BC používá Vertical Slice Architecture — každá feature je soběstačný slice.

```
crm/src/Contacts/
├── Domain/
│   ├── Customer.php              # Aggregate Root (čistý PHP, žádné anotace)
│   ├── CustomerId.php            # Value Object
│   ├── CustomerEmail.php         # Value Object
│   ├── CustomerRegistered.php    # Domain Event
│   └── CustomerRepository.php   # Interface (Port)
├── Application/
│   ├── RegisterCustomer/
│   │   ├── RegisterCustomerCommand.php
│   │   └── RegisterCustomerHandler.php
│   ├── UpdateCustomer/
│   │   ├── UpdateCustomerCommand.php
│   │   └── UpdateCustomerHandler.php
│   └── GetCustomerList/
│       ├── GetCustomerListQuery.php
│       ├── GetCustomerListHandler.php
│       └── CustomerListItemDTO.php
└── Infrastructure/
    ├── Persistence/
    │   └── DoctrineCustomerRepository.php
    ├── Http/
    │   ├── RegisterCustomerController.php
    │   └── GetCustomerListController.php
    └── Doctrine/
        └── CustomerMapping.xml   # XML mapping — nikdy anotace v doméně
```

**Pravidlo:** Doctrine mapping je výhradně v Infrastructure. Doménové třídy jsou čisté PHP objekty bez Symfony/Doctrine závislostí.

---

## 4. CQRS a Messaging

### 4.1 Tři Messenger busy

```php
// Write — mění stav systému
$commandBus->dispatch(new RegisterCustomerCommand(...));

// Read — nikdy nemění stav
$result = $queryBus->dispatch(new GetCustomerListQuery(...));

// Domain Events — async inter-BC komunikace
$eventBus->dispatch(new CustomerRegistered($customerId));
```

### 4.2 Write flow

```
Controller
  → CommandBus
    → CommandHandler
        → Repository::get()       # načti agregát
        → Aggregate::action()     # byznys logika uvnitř agregátu
        → Repository::save()      # ulož
        → EventBus::dispatch()    # emituj domain events
```

### 4.3 Read flow

Query handlery nepoužívají agregáty — přistupují přímo na DB přes Doctrine DBAL nebo SQL. Read modely jsou DTO, ne domain objekty.

```php
class GetCustomerListHandler {
    public function __invoke(GetCustomerListQuery $query): array {
        return $this->connection->executeQuery(
            'SELECT id, name, email FROM crm_customers WHERE tenant_id = :tenant LIMIT :limit',
            ['tenant' => $query->tenantId, 'limit' => $query->limit]
        )->fetchAllAssociative();
    }
}
```

### 4.4 Sagy (Process Managers)

Sagy orchestrují long-running procesy překračující hranice BC. Stav je persistován v PostgreSQL.

**Příklady procesů vyžadujících Sagu:**
- `DealWon` (CRM) → vytvoř Project → notifikuj tým
- `OrderPlaced` → rezervuj Inventory → vytvoř ProductionOrder → expeduj → vyfakturuj
- `ProductionOrderCreated` → zkontroluj BOM → rezervuj materiály

```
Saga životní cyklus:
  Domain Event → SagaManager::handle()
    → najdi nebo vytvoř SagaState v DB
    → Saga::on{EventName}()
      → vydá Commands do příslušných BC
      → aktualizuje SagaState (status, data)
    → při pádu systému: pokračuje od posledního uloženého stavu
```

Sagy žijí v `shared-kernel/src/Saga/` nebo dedikovaném `process-managers` package.

---

## 5. API Design

### 5.1 URL konvence

```
# Commands — akce orientované URL
POST /api/crm/contacts/commands/register-customer
POST /api/crm/sales-pipeline/commands/create-deal
POST /api/projects/commands/create-project

# Queries — resource orientované URL
GET  /api/crm/contacts/customers
GET  /api/crm/contacts/customers/{id}
GET  /api/projects/projects
GET  /api/projects/projects/{id}/tasks
```

### 5.2 Dokumentace

OpenAPI spec generovaný přes `nelmio/api-doc-bundle` z PHP atributů na controllerech.

---

## 6. Oprávnění

Každý BC deklaruje vlastní sadu oprávnění jako PHP enum:

```php
enum ContactsPermission: string {
    case VIEW_CUSTOMERS  = 'crm.contacts.view_customers';
    case CREATE_CUSTOMER = 'crm.contacts.create_customer';
    case DELETE_CUSTOMER = 'crm.contacts.delete_customer';
}
```

Symfony Security Voter per BC kontroluje oprávnění. Role a přiřazení oprávnění spravuje `shared-kernel/Identity`.

---

## 7. Zapínání/vypínání modulů

Stav modulů uložen v PostgreSQL tabulce `organization_modules`.

```php
enum Module: string {
    case CRM           = 'crm';
    case PROJECTS      = 'projects';
    case ESHOP         = 'eshop';
    case MANUFACTURING = 'manufacturing';
}
```

Symfony Compiler Pass při sestavení containeru:
- Vypnutý modul → jeho routes se nezaregistrují, services se nevloží do containeru
- Změna stavu modulu vyžaduje cache clear (restart)

---

## 8. Testování

### Úroveň 1 — Unit testy (doménová logika)

Testují agregáty a value objects v izolaci. Žádný framework, žádná databáze. Nejrychlejší, nejvíce jich bude.

```php
class CustomerTest extends TestCase {
    public function test_registration_emits_customer_registered_event(): void {
        $customer = Customer::register(CustomerId::generate(), new CustomerEmail('jan@firma.cz'));
        $this->assertInstanceOf(CustomerRegistered::class, $customer->pullDomainEvents()[0]);
    }
}
```

### Úroveň 2 — Integrační testy (handlery + DB)

Testují Command/Query handlery proti reálné PostgreSQL v Docker. Žádné DB mocky.

```php
class RegisterCustomerHandlerTest extends KernelTestCase {
    public function test_registers_customer_and_persists(): void {
        $this->commandBus->dispatch(new RegisterCustomerCommand('jan@firma.cz', 'Jan Novák'));
        $result = $this->queryBus->dispatch(new GetCustomerListQuery());
        $this->assertCount(1, $result->items);
    }
}
```

### Úroveň 3 — E2E testy (HTTP API)

Testují celý stack přes Symfony `WebTestCase`. Ověřují HTTP status kódy a JSON strukturu odpovědí.

### Konvence

- Každý BC má vlastní `tests/` adresář uvnitř svého package
- CI: unit testy bez DB, integrační s Docker PostgreSQL
- Sagy mají vlastní integrační testy simulující celý event flow

---

## 9. MVP scope — CRM Contacts BC

První implementace pokryje pouze BC `Contacts` v rámci CRM domény:

- `RegisterCustomer` command
- `UpdateCustomer` command
- `GetCustomerList` query
- `GetCustomerDetail` query
- PostgreSQL persistence přes Doctrine XML mapping
- HTTP API endpointy
- Základní oprávnění (VIEW, CREATE, UPDATE)
- Unit + integrační testy

Shared-kernel infrastruktura (CommandBus, QueryBus, EventBus, AggregateRoot) musí být hotová před MVP.
