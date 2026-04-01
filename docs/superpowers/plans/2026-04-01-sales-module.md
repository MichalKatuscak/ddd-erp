# Sales Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `packages/sales` Bounded Context — Poptávky (Inquiry) and Nabídky (Quote) — with the full flow Inquiry → Quote → Order (Planning BC).

**Architecture:** New Composer package `packages/sales` with two aggregate roots (`Inquiry`, `Quote`). Repositories use raw DBAL (same pattern as Planning). On `QuoteAccepted` event, Planning BC creates an Order. Frontend gets 3 pages (InquiriesPage, InquiryDetailPage, QuoteDetailPage).

**Tech Stack:** PHP 8.4, Symfony 7, DBAL (raw SQL), dompdf/dompdf for PDF, React 19, TanStack Query v5, CSS Modules.

---

## File Map

```
packages/sales/
  composer.json
  src/
    Inquiry/
      Domain/
        Inquiry.php, InquiryId.php, InquiryStatus.php, SalesRole.php
        RequiredRole.php, Attachment.php
        InquiryRepository.php, InquiryNotFoundException.php
        InquiryCreated.php (domain event)
      Application/
        CreateInquiry/{CreateInquiryCommand.php, CreateInquiryHandler.php}
        UpdateInquiry/{UpdateInquiryCommand.php, UpdateInquiryHandler.php}
        AdvanceInquiryStatus/{AdvanceInquiryStatusCommand.php, AdvanceInquiryStatusHandler.php}
        AttachFile/{AttachFileCommand.php, AttachFileHandler.php}
        GetInquiryList/{GetInquiryListQuery.php, GetInquiryListHandler.php, InquiryListItemDTO.php}
        GetInquiryDetail/{GetInquiryDetailQuery.php, GetInquiryDetailHandler.php, InquiryDetailDTO.php}
      Infrastructure/
        Persistence/DoctrineInquiryRepository.php
        Http/{CreateInquiryController, UpdateInquiryController, AdvanceInquiryStatusController,
              AttachFileController, GetInquiryListController, GetInquiryDetailController}.php
        Http/Request/{CreateInquiryRequest, UpdateInquiryRequest, AdvanceInquiryStatusRequest}.php
        Storage/{FileStorage.php (interface), LocalFileStorage.php}
    Quote/
      Domain/
        Quote.php, QuotePhase.php, QuoteId.php, QuotePhaseId.php
        Money.php, QuoteStatus.php
        QuoteRepository.php, QuoteNotFoundException.php
        QuoteAccepted.php (domain event)
      Application/
        CreateQuote/{CreateQuoteCommand.php, CreateQuoteHandler.php}
        AddQuotePhase/{AddQuotePhaseCommand.php, AddQuotePhaseHandler.php}
        UpdateQuotePhase/{UpdateQuotePhaseCommand.php, UpdateQuotePhaseHandler.php}
        SendQuote/{SendQuoteCommand.php, SendQuoteHandler.php}
        AcceptQuote/{AcceptQuoteCommand.php, AcceptQuoteHandler.php}
        RejectQuote/{RejectQuoteCommand.php, RejectQuoteHandler.php}
        ExportQuotePdf/{ExportQuotePdfCommand.php, ExportQuotePdfHandler.php}
        GetQuoteDetail/{GetQuoteDetailQuery.php, GetQuoteDetailHandler.php, QuoteDetailDTO.php, QuotePhaseDTO.php}
      Infrastructure/
        Persistence/DoctrineQuoteRepository.php
        Http/{CreateQuoteController, AddQuotePhaseController, UpdateQuotePhaseController,
              SendQuoteController, AcceptQuoteController, RejectQuoteController,
              ExportQuotePdfController, GetQuotePdfController, GetQuoteDetailController}.php
        Http/Request/{CreateQuoteRequest, AddQuotePhaseRequest, UpdateQuotePhaseRequest}.php
        Pdf/QuotePdfGenerator.php
    Infrastructure/
      Http/GetAttachmentController.php
      Security/{SalesPermission.php, SalesVoter.php}
  tests/
    Inquiry/Domain/InquiryTest.php
    Inquiry/Application/{CreateInquiryHandlerTest, UpdateInquiryHandlerTest,
      AdvanceInquiryStatusHandlerTest, AttachFileHandlerTest,
      GetInquiryListHandlerTest, GetInquiryDetailHandlerTest}.php
    Inquiry/Application/{InMemoryInquiryRepository, SpyEventBus, SpyCommandBus}.php
    Quote/Domain/QuoteTest.php
    Quote/Application/{CreateQuoteHandlerTest, AddQuotePhaseHandlerTest,
      AcceptQuoteHandlerTest, GetQuoteDetailHandlerTest}.php
    Quote/Application/InMemoryQuoteRepository.php

# Config changes (root app)
config/routes/sales.yaml           — NEW
config/services.yaml               — add Sales\ block
config/packages/messenger.yaml     — add CreateOrderFromQuoteHandler routing
composer.json                      — add sales package
phpunit.xml                        — add sales controller test dirs

# Planning BC change
packages/planning/src/Order/Application/CreateOrderFromQuote/{CreateOrderFromQuoteHandler.php}

# Frontend
frontend/src/app/api/sales.ts
frontend/src/app/modules/sales/{InquiriesPage,InquiryDetailPage,QuoteDetailPage}.tsx
frontend/src/app/modules/sales/{InquiriesPage,InquiryDetailPage,QuoteDetailPage}.module.css
frontend/src/app/router.tsx        — add 3 routes
frontend/src/app/components/AppLayout/AppLayout.tsx  — add sidebar section
```

---

## Task 1: Package scaffold

**Files:**
- Create: `packages/sales/composer.json`
- Modify: `composer.json` (root)
- Create: `config/routes/sales.yaml`

- [ ] **Step 1: Create package directory structure**

```bash
mkdir -p packages/sales/src/Inquiry/{Domain,Application,Infrastructure/{Persistence,Http/Request,Storage}}
mkdir -p packages/sales/src/Quote/{Domain,Application,Infrastructure/{Persistence,Http/Request,Pdf}}
mkdir -p packages/sales/src/Infrastructure/{Http,Security}
mkdir -p packages/sales/tests/Inquiry/{Domain,Application}
mkdir -p packages/sales/tests/Quote/{Domain,Application}
```

- [ ] **Step 2: Create `packages/sales/composer.json`**

```json
{
    "name": "ddd-erp/sales",
    "type": "library",
    "description": "Sales BC — Inquiries and Quotes",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {"type": "path", "url": "../shared-kernel"}
    ],
    "require": {
        "php": "^8.4",
        "ddd-erp/shared-kernel": "*",
        "doctrine/dbal": "^4.0",
        "symfony/uid": "^7.2",
        "symfony/messenger": "^7.2",
        "symfony/security-core": "^7.2",
        "dompdf/dompdf": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {"Sales\\": "src/"}
    },
    "autoload-dev": {
        "psr-4": {"Sales\\Tests\\": "tests/"}
    }
}
```

- [ ] **Step 3: Add sales to root `composer.json`**

In `repositories` array add:
```json
{"type": "path", "url": "./packages/sales", "options": {"symlink": true}}
```

In `require` add:
```json
"ddd-erp/sales": "*"
```

In `autoload-dev.psr-4` add:
```json
"Sales\\Tests\\": "packages/sales/tests/"
```

- [ ] **Step 4: Install packages**

```bash
composer update ddd-erp/sales
```

Expected: resolves ddd-erp/sales and dompdf/dompdf, no errors.

- [ ] **Step 5: Create `config/routes/sales.yaml`**

```yaml
sales_inquiry:
    resource:
        path: '../../packages/sales/src/Inquiry/Infrastructure/Http/'
        namespace: 'Sales\Inquiry\Infrastructure\Http'
    type: attribute

sales_quote:
    resource:
        path: '../../packages/sales/src/Quote/Infrastructure/Http/'
        namespace: 'Sales\Quote\Infrastructure\Http'
    type: attribute

sales_shared:
    resource:
        path: '../../packages/sales/src/Infrastructure/Http/'
        namespace: 'Sales\Infrastructure\Http'
    type: attribute
```

- [ ] **Step 6: Add Sales to `config/services.yaml`**

After the Planning block, add:

```yaml
    # Sales package
    Sales\:
        resource: '../packages/sales/src/'
        exclude:
            - '../packages/sales/src/Inquiry/Domain/'
            - '../packages/sales/src/Quote/Domain/'

    Sales\Inquiry\Domain\InquiryRepository:
        alias: Sales\Inquiry\Infrastructure\Persistence\DoctrineInquiryRepository

    Sales\Quote\Domain\QuoteRepository:
        alias: Sales\Quote\Infrastructure\Persistence\DoctrineQuoteRepository

    Sales\Inquiry\Infrastructure\Storage\FileStorage:
        alias: Sales\Inquiry\Infrastructure\Storage\LocalFileStorage

    Sales\Inquiry\Infrastructure\Storage\LocalFileStorage:
        arguments:
            $uploadDir: '%kernel.project_dir%/var/uploads/sales/attachments'

    Sales\Quote\Infrastructure\Pdf\QuotePdfGenerator:
        arguments:
            $outputDir: '%kernel.project_dir%/var/uploads/sales/quotes'
```

- [ ] **Step 7: Verify Symfony container compiles**

```bash
php bin/console cache:clear
```

Expected: "Cache for the 'dev' environment (debug=true) was successfully cleared."

- [ ] **Step 8: Commit**

```bash
git add packages/sales/composer.json composer.json composer.lock config/routes/sales.yaml config/services.yaml
git commit -m "chore(sales): scaffold sales package and register in Symfony"
```

---

## Task 2: Inquiry domain — value objects

**Files:**
- Create: `packages/sales/src/Inquiry/Domain/InquiryId.php`
- Create: `packages/sales/src/Inquiry/Domain/InquiryStatus.php`
- Create: `packages/sales/src/Inquiry/Domain/SalesRole.php`
- Create: `packages/sales/src/Inquiry/Domain/RequiredRole.php`
- Create: `packages/sales/src/Inquiry/Domain/Attachment.php`
- Create: `packages/sales/src/Inquiry/Domain/InvalidStatusTransitionException.php`
- Test: `packages/sales/tests/Inquiry/Domain/InquiryStatusTest.php`

- [ ] **Step 1: Write failing test for InquiryStatus**

```php
<?php
declare(strict_types=1);

namespace Sales\Tests\Inquiry\Domain;

use Sales\Inquiry\Domain\InquiryStatus;
use Sales\Inquiry\Domain\InvalidStatusTransitionException;
use PHPUnit\Framework\TestCase;

final class InquiryStatusTest extends TestCase
{
    public function test_linear_next_from_new(): void
    {
        $this->assertSame(InquiryStatus::InProgress, InquiryStatus::New->next());
    }

    public function test_linear_next_from_in_progress(): void
    {
        $this->assertSame(InquiryStatus::Quoted, InquiryStatus::InProgress->next());
    }

    public function test_next_from_quoted_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        InquiryStatus::Quoted->next();
    }

    public function test_next_from_terminal_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        InquiryStatus::Won->next();
    }

    public function test_can_transition_to_won_from_quoted(): void
    {
        $this->assertTrue(InquiryStatus::Quoted->canTransitionTo(InquiryStatus::Won));
    }

    public function test_cannot_transition_to_won_from_new(): void
    {
        $this->assertFalse(InquiryStatus::New->canTransitionTo(InquiryStatus::Won));
    }

    public function test_can_cancel_from_any_non_terminal(): void
    {
        $this->assertTrue(InquiryStatus::New->canTransitionTo(InquiryStatus::Cancelled));
        $this->assertTrue(InquiryStatus::InProgress->canTransitionTo(InquiryStatus::Cancelled));
        $this->assertTrue(InquiryStatus::Quoted->canTransitionTo(InquiryStatus::Cancelled));
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Domain/InquiryStatusTest.php --testdox
```

Expected: class not found error.

- [ ] **Step 3: Create `packages/sales/src/Inquiry/Domain/InvalidStatusTransitionException.php`**

```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

final class InvalidStatusTransitionException extends \DomainException {}
```

- [ ] **Step 4: Create `packages/sales/src/Inquiry/Domain/InquiryStatus.php`**

```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

enum InquiryStatus: string
{
    case New        = 'new';
    case InProgress = 'in_progress';
    case Quoted     = 'quoted';
    case Won        = 'won';
    case Lost       = 'lost';
    case Cancelled  = 'cancelled';

    public function next(): self
    {
        return match($this) {
            self::New        => self::InProgress,
            self::InProgress => self::Quoted,
            default          => throw new InvalidStatusTransitionException(
                "Cannot advance from terminal or branching status '{$this->value}'"
            ),
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::New        => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($target, [self::Quoted, self::Cancelled], true),
            self::Quoted     => in_array($target, [self::Won, self::Lost, self::Cancelled], true),
            default          => false,
        };
    }
}
```

- [ ] **Step 5: Run — expect PASS**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Domain/InquiryStatusTest.php --testdox
```

Expected: 7 tests, 7 assertions, OK.

- [ ] **Step 6: Create remaining value objects**

`packages/sales/src/Inquiry/Domain/InquiryId.php`:
```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

use Symfony\Component\Uid\Uuid;

final class InquiryId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid InquiryId: '$value'");
        }
        return new self($value);
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
```

`packages/sales/src/Inquiry/Domain/SalesRole.php`:
```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

enum SalesRole: string
{
    case Designer = 'designer';
    case Frontend = 'frontend';
    case Backend  = 'backend';
    case Pm       = 'pm';
    case Qa       = 'qa';
    case Devops   = 'devops';
}
```

`packages/sales/src/Inquiry/Domain/RequiredRole.php`:
```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

final class RequiredRole
{
    /** @param string[] $skills */
    public function __construct(
        public readonly SalesRole $role,
        public readonly array     $skills,
    ) {}
}
```

`packages/sales/src/Inquiry/Domain/Attachment.php`:
```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

use Symfony\Component\Uid\Uuid;

final class Attachment
{
    public readonly string $id;

    public function __construct(
        public readonly string $path,
        public readonly string $mimeType,
        public readonly string $originalName,
        ?string $id = null,
    ) {
        $this->id = $id ?? (string) Uuid::v7();
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add packages/sales/src/Inquiry/Domain/ packages/sales/tests/Inquiry/Domain/
git commit -m "feat(sales): add Inquiry domain value objects and InquiryStatus enum"
```

---

## Task 3: Inquiry aggregate root

**Files:**
- Create: `packages/sales/src/Inquiry/Domain/Inquiry.php`
- Create: `packages/sales/src/Inquiry/Domain/InquiryRepository.php`
- Create: `packages/sales/src/Inquiry/Domain/InquiryNotFoundException.php`
- Create: `packages/sales/src/Inquiry/Domain/InquiryCreated.php`
- Test: `packages/sales/tests/Inquiry/Domain/InquiryTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
declare(strict_types=1);

namespace Sales\Tests\Inquiry\Domain;

use Sales\Inquiry\Domain\Inquiry;
use Sales\Inquiry\Domain\InquiryCreated;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Inquiry\Domain\InquiryStatus;
use Sales\Inquiry\Domain\InvalidStatusTransitionException;
use Sales\Inquiry\Domain\RequiredRole;
use Sales\Inquiry\Domain\Attachment;
use Sales\Inquiry\Domain\SalesRole;
use PHPUnit\Framework\TestCase;

final class InquiryTest extends TestCase
{
    private InquiryId $id;

    protected function setUp(): void
    {
        $this->id = InquiryId::fromString('018e8f2a-1234-7000-8000-000000000001');
    }

    public function test_creates_inquiry_with_new_status(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []);
        $this->assertSame(InquiryStatus::New, $inquiry->status());
        $this->assertSame('Firma s.r.o.', $inquiry->customerName());
    }

    public function test_creation_emits_inquiry_created_event(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []);
        $events = $inquiry->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InquiryCreated::class, $events[0]);
    }

    public function test_advances_status_linearly(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $inquiry->pullDomainEvents();
        $inquiry->advanceStatus(null);
        $this->assertSame(InquiryStatus::InProgress, $inquiry->status());
    }

    public function test_can_mark_as_won_from_quoted(): void
    {
        $inquiry = Inquiry::reconstruct($this->id, null, 'Firma', 'a@b.cz', 'X', null, [], [], InquiryStatus::Quoted, new \DateTimeImmutable());
        $inquiry->advanceStatus('won');
        $this->assertSame(InquiryStatus::Won, $inquiry->status());
    }

    public function test_throws_on_invalid_transition(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $this->expectException(InvalidStatusTransitionException::class);
        $inquiry->advanceStatus('won');
    }

    public function test_adds_attachment(): void
    {
        $inquiry = Inquiry::create($this->id, null, 'Firma', 'a@b.cz', 'X', null, []);
        $inquiry->addAttachment(new Attachment('path/to/file.pdf', 'application/pdf', 'file.pdf'));
        $this->assertCount(1, $inquiry->attachments());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Domain/InquiryTest.php --testdox
```

Expected: class not found.

- [ ] **Step 3: Create `InquiryNotFoundException.php`**

```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;
final class InquiryNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Inquiry '$id' not found");
    }
}
```

- [ ] **Step 4: Create `InquiryCreated.php`**

```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;
final class InquiryCreated
{
    public function __construct(public readonly InquiryId $inquiryId) {}
}
```

- [ ] **Step 5: Create `InquiryRepository.php`**

```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Domain;
interface InquiryRepository
{
    public function get(InquiryId $id): Inquiry;
    public function save(Inquiry $inquiry): void;
}
```

- [ ] **Step 6: Create `packages/sales/src/Inquiry/Domain/Inquiry.php`**

```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Inquiry extends AggregateRoot
{
    /** @var RequiredRole[] */
    private array $requiredRoles;
    /** @var Attachment[] */
    private array $attachments;

    private function __construct(
        private readonly InquiryId          $id,
        private ?string                      $customerId,
        private string                       $customerName,
        private string                       $contactEmail,
        private string                       $description,
        private ?\DateTimeImmutable          $requestedDeadline,
        array                                $requiredRoles,
        array                                $attachments,
        private InquiryStatus               $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->requiredRoles = $requiredRoles;
        $this->attachments   = $attachments;
    }

    /** @param RequiredRole[] $requiredRoles */
    public static function create(
        InquiryId          $id,
        ?string            $customerId,
        string             $customerName,
        string             $contactEmail,
        string             $description,
        ?\DateTimeImmutable $requestedDeadline,
        array              $requiredRoles,
    ): self {
        $inquiry = new self(
            $id, $customerId, $customerName, $contactEmail,
            $description, $requestedDeadline, $requiredRoles, [],
            InquiryStatus::New, new \DateTimeImmutable(),
        );
        $inquiry->recordEvent(new InquiryCreated($id));
        return $inquiry;
    }

    /** @param RequiredRole[] $requiredRoles @param Attachment[] $attachments */
    public static function reconstruct(
        InquiryId          $id,
        ?string            $customerId,
        string             $customerName,
        string             $contactEmail,
        string             $description,
        ?\DateTimeImmutable $requestedDeadline,
        array              $requiredRoles,
        array              $attachments,
        InquiryStatus      $status,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $customerId, $customerName, $contactEmail,
            $description, $requestedDeadline, $requiredRoles, $attachments,
            $status, $createdAt);
    }

    public function update(
        ?string $customerId,
        string  $customerName,
        string  $contactEmail,
        string  $description,
        ?\DateTimeImmutable $requestedDeadline,
        array   $requiredRoles,
    ): void {
        $this->customerId        = $customerId;
        $this->customerName      = $customerName;
        $this->contactEmail      = $contactEmail;
        $this->description       = $description;
        $this->requestedDeadline = $requestedDeadline;
        $this->requiredRoles     = $requiredRoles;
    }

    public function advanceStatus(?string $targetStatus): void
    {
        if ($targetStatus === null) {
            $this->status = $this->status->next();
            return;
        }
        $target = InquiryStatus::from($targetStatus);
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition from '{$this->status->value}' to '{$target->value}'"
            );
        }
        $this->status = $target;
    }

    public function addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    public function id(): InquiryId { return $this->id; }
    public function customerId(): ?string { return $this->customerId; }
    public function customerName(): string { return $this->customerName; }
    public function contactEmail(): string { return $this->contactEmail; }
    public function description(): string { return $this->description; }
    public function requestedDeadline(): ?\DateTimeImmutable { return $this->requestedDeadline; }
    /** @return RequiredRole[] */
    public function requiredRoles(): array { return $this->requiredRoles; }
    /** @return Attachment[] */
    public function attachments(): array { return $this->attachments; }
    public function status(): InquiryStatus { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
}
```

- [ ] **Step 7: Run — expect PASS**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Domain/InquiryTest.php --testdox
```

Expected: 6 tests, 6 assertions, OK.

- [ ] **Step 8: Commit**

```bash
git add packages/sales/src/Inquiry/Domain/ packages/sales/tests/Inquiry/Domain/InquiryTest.php
git commit -m "feat(sales): add Inquiry aggregate root"
```

---

## Task 4: Inquiry application — CreateInquiry + UpdateInquiry

**Files:**
- Create: `packages/sales/src/Inquiry/Application/CreateInquiry/{CreateInquiryCommand,CreateInquiryHandler}.php`
- Create: `packages/sales/src/Inquiry/Application/UpdateInquiry/{UpdateInquiryCommand,UpdateInquiryHandler}.php`
- Create: `packages/sales/tests/Inquiry/Application/InMemoryInquiryRepository.php`
- Create: `packages/sales/tests/Inquiry/Application/SpyEventBus.php`
- Test: `packages/sales/tests/Inquiry/Application/CreateInquiryHandlerTest.php`
- Test: `packages/sales/tests/Inquiry/Application/UpdateInquiryHandlerTest.php`

- [ ] **Step 1: Create test doubles**

`packages/sales/tests/Inquiry/Application/InMemoryInquiryRepository.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Domain\{Inquiry, InquiryId, InquiryNotFoundException, InquiryRepository};
final class InMemoryInquiryRepository implements InquiryRepository
{
    private array $items = [];
    public function get(InquiryId $id): Inquiry
    {
        return $this->items[$id->value()] ?? throw new InquiryNotFoundException($id->value());
    }
    public function save(Inquiry $inquiry): void
    {
        $this->items[$inquiry->id()->value()] = $inquiry;
    }
}
```

`packages/sales/tests/Inquiry/Application/SpyEventBus.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use SharedKernel\Application\EventBusInterface;
final class SpyEventBus implements EventBusInterface
{
    public array $dispatched = [];
    public function dispatch(object $event): void { $this->dispatched[] = $event; }
}
```

`packages/sales/tests/Inquiry/Application/SpyCommandBus.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use SharedKernel\Application\CommandBusInterface;
final class SpyCommandBus implements CommandBusInterface
{
    public array $dispatched = [];
    public function dispatch(object $command): void { $this->dispatched[] = $command; }
}
```

- [ ] **Step 2: Write failing test for CreateInquiry**

`packages/sales/tests/Inquiry/Application/CreateInquiryHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Domain\{InquiryCreated, InquiryId, InquiryStatus};
use PHPUnit\Framework\TestCase;
final class CreateInquiryHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private SpyEventBus $eventBus;
    private CreateInquiryHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new CreateInquiryHandler($this->repository, $this->eventBus);
    }
    public function test_creates_inquiry(): void
    {
        $id = InquiryId::generate()->value();
        ($this->handler)(new CreateInquiryCommand($id, null, 'Firma s.r.o.', 'info@firma.cz', 'Popis', null, []));
        $inquiry = $this->repository->get(InquiryId::fromString($id));
        $this->assertSame('Firma s.r.o.', $inquiry->customerName());
        $this->assertSame(InquiryStatus::New, $inquiry->status());
    }
    public function test_dispatches_inquiry_created_event(): void
    {
        ($this->handler)(new CreateInquiryCommand(InquiryId::generate()->value(), null, 'Firma', 'a@b.cz', 'X', null, []));
        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(InquiryCreated::class, $this->eventBus->dispatched[0]);
    }
}
```

- [ ] **Step 3: Run — expect FAIL**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Application/CreateInquiryHandlerTest.php --testdox
```

Expected: class not found.

- [ ] **Step 4: Create CreateInquiry command + handler**

`packages/sales/src/Inquiry/Application/CreateInquiry/CreateInquiryCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\CreateInquiry;
final readonly class CreateInquiryCommand
{
    /** @param array<array{role: string, skills: string[]}> $requiredRoles */
    public function __construct(
        public string  $inquiryId,
        public ?string $customerId,
        public string  $customerName,
        public string  $contactEmail,
        public string  $description,
        public ?string $requestedDeadline,
        public array   $requiredRoles,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/CreateInquiry/CreateInquiryHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\CreateInquiry;
use Sales\Inquiry\Domain\{Inquiry, InquiryId, InquiryRepository, RequiredRole, SalesRole};
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class CreateInquiryHandler
{
    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly EventBusInterface  $eventBus,
    ) {}
    public function __invoke(CreateInquiryCommand $command): void
    {
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            $command->requiredRoles,
        );
        $inquiry = Inquiry::create(
            InquiryId::fromString($command->inquiryId),
            $command->customerId,
            $command->customerName,
            $command->contactEmail,
            $command->description,
            $command->requestedDeadline ? new \DateTimeImmutable($command->requestedDeadline) : null,
            $roles,
        );
        $this->repository->save($inquiry);
        foreach ($inquiry->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
```

- [ ] **Step 5: Run — expect PASS**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Application/CreateInquiryHandlerTest.php --testdox
```

Expected: 2 tests, OK.

- [ ] **Step 6: Write and implement UpdateInquiry**

Test `packages/sales/tests/Inquiry/Application/UpdateInquiryHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Application\UpdateInquiry\{UpdateInquiryCommand, UpdateInquiryHandler};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class UpdateInquiryHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private UpdateInquiryHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $createHandler = new CreateInquiryHandler($this->repository, new SpyEventBus());
        $id = InquiryId::generate()->value();
        ($createHandler)(new CreateInquiryCommand($id, null, 'Stará firma', 'old@b.cz', 'Old', null, []));
        $this->inquiryId = $id;
        $this->handler = new UpdateInquiryHandler($this->repository);
    }
    private string $inquiryId;
    public function test_updates_customer_name(): void
    {
        ($this->handler)(new UpdateInquiryCommand($this->inquiryId, null, 'Nová firma', 'new@b.cz', 'Nový popis', null, []));
        $inquiry = $this->repository->get(InquiryId::fromString($this->inquiryId));
        $this->assertSame('Nová firma', $inquiry->customerName());
    }
}
```

`packages/sales/src/Inquiry/Application/UpdateInquiry/UpdateInquiryCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\UpdateInquiry;
final readonly class UpdateInquiryCommand
{
    /** @param array<array{role: string, skills: string[]}> $requiredRoles */
    public function __construct(
        public string  $inquiryId,
        public ?string $customerId,
        public string  $customerName,
        public string  $contactEmail,
        public string  $description,
        public ?string $requestedDeadline,
        public array   $requiredRoles,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/UpdateInquiry/UpdateInquiryHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\UpdateInquiry;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository, RequiredRole, SalesRole};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class UpdateInquiryHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(UpdateInquiryCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            $command->requiredRoles,
        );
        $inquiry->update(
            $command->customerId, $command->customerName, $command->contactEmail,
            $command->description,
            $command->requestedDeadline ? new \DateTimeImmutable($command->requestedDeadline) : null,
            $roles,
        );
        $this->repository->save($inquiry);
    }
}
```

- [ ] **Step 7: Run all Inquiry application tests so far**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Application/ --testdox
```

Expected: 3 tests, OK.

- [ ] **Step 8: Commit**

```bash
git add packages/sales/src/Inquiry/Application/ packages/sales/tests/Inquiry/Application/
git commit -m "feat(sales): add CreateInquiry and UpdateInquiry handlers"
```

---

## Task 5: Inquiry application — AdvanceStatus + AttachFile + Queries

**Files:**
- Create: `AdvanceInquiryStatus/{Command,Handler}.php`
- Create: `AttachFile/{Command,Handler}.php`
- Create: `GetInquiryList/{Query,Handler,InquiryListItemDTO}.php`
- Create: `GetInquiryDetail/{Query,Handler,InquiryDetailDTO}.php`
- Create: `Infrastructure/Storage/FileStorage.php` (interface)
- Tests for each

- [ ] **Step 1: Write failing tests**

`packages/sales/tests/Inquiry/Application/AdvanceInquiryStatusHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use Sales\Inquiry\Application\AdvanceInquiryStatus\{AdvanceInquiryStatusCommand, AdvanceInquiryStatusHandler};
use Sales\Inquiry\Application\CreateInquiry\{CreateInquiryCommand, CreateInquiryHandler};
use Sales\Inquiry\Domain\{InquiryId, InquiryStatus, InvalidStatusTransitionException};
use PHPUnit\Framework\TestCase;
final class AdvanceInquiryStatusHandlerTest extends TestCase
{
    private InMemoryInquiryRepository $repository;
    private AdvanceInquiryStatusHandler $handler;
    private string $inquiryId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryInquiryRepository();
        $id = InquiryId::generate()->value();
        (new CreateInquiryHandler($this->repository, new SpyEventBus()))(
            new CreateInquiryCommand($id, null, 'Firma', 'a@b.cz', 'X', null, [])
        );
        $this->inquiryId = $id;
        $this->handler = new AdvanceInquiryStatusHandler($this->repository);
    }
    public function test_advances_linearly(): void
    {
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null));
        $this->assertSame(InquiryStatus::InProgress, $this->repository->get(InquiryId::fromString($this->inquiryId))->status());
    }
    public function test_sets_terminal_status(): void
    {
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null)); // new→in_progress
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, null)); // in_progress→quoted
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, 'won'));
        $this->assertSame(InquiryStatus::Won, $this->repository->get(InquiryId::fromString($this->inquiryId))->status());
    }
    public function test_throws_on_invalid_transition(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        ($this->handler)(new AdvanceInquiryStatusCommand($this->inquiryId, 'won'));
    }
}
```

- [ ] **Step 2: Implement AdvanceInquiryStatus**

`packages/sales/src/Inquiry/Application/AdvanceInquiryStatus/AdvanceInquiryStatusCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AdvanceInquiryStatus;
final readonly class AdvanceInquiryStatusCommand
{
    public function __construct(
        public string  $inquiryId,
        public ?string $targetStatus,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/AdvanceInquiryStatus/AdvanceInquiryStatusHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AdvanceInquiryStatus;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AdvanceInquiryStatusHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(AdvanceInquiryStatusCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $inquiry->advanceStatus($command->targetStatus);
        $this->repository->save($inquiry);
    }
}
```

- [ ] **Step 3: Create FileStorage interface and implement AttachFile**

`packages/sales/src/Inquiry/Infrastructure/Storage/FileStorage.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Storage;
interface FileStorage
{
    /**
     * Stores file and returns stored path (relative to upload dir).
     * $tmpPath: absolute path to uploaded temp file
     */
    public function store(string $tmpPath, string $originalName, string $mimeType): string;
    public function absolutePath(string $storedPath): string;
}
```

`packages/sales/src/Inquiry/Application/AttachFile/AttachFileCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AttachFile;
final readonly class AttachFileCommand
{
    public function __construct(
        public string $inquiryId,
        public string $storedPath,
        public string $mimeType,
        public string $originalName,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/AttachFile/AttachFileHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AttachFile;
use Sales\Inquiry\Domain\{Attachment, InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AttachFileHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(AttachFileCommand $command): void
    {
        $inquiry = $this->repository->get(InquiryId::fromString($command->inquiryId));
        $inquiry->addAttachment(new Attachment($command->storedPath, $command->mimeType, $command->originalName));
        $this->repository->save($inquiry);
    }
}
```

- [ ] **Step 4: Create GetInquiryList**

`packages/sales/src/Inquiry/Application/GetInquiryList/GetInquiryListQuery.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
final readonly class GetInquiryListQuery
{
    public function __construct(public ?string $status = null) {}
}
```

`packages/sales/src/Inquiry/Application/GetInquiryList/InquiryListItemDTO.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
final readonly class InquiryListItemDTO
{
    public function __construct(
        public string  $id,
        public string  $customerName,
        public string  $description,
        public string  $status,
        public ?string $requestedDeadline,
        public string  $createdAt,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/GetInquiryList/GetInquiryListHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryList;
use Sales\Inquiry\Domain\InquiryRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetInquiryListHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    /** @return InquiryListItemDTO[] */
    public function __invoke(GetInquiryListQuery $query): array
    {
        return $this->repository->findAll($query->status);
    }
}
```

Note: `findAll(?string $status): InquiryListItemDTO[]` must be added to `InquiryRepository` interface:
```php
/** @return InquiryListItemDTO[] */
public function findAll(?string $status): array;
```

And `InMemoryInquiryRepository` must implement it:
```php
public function findAll(?string $status): array
{
    $items = array_values($this->items);
    if ($status !== null) {
        $items = array_filter($items, fn($i) => $i->status()->value === $status);
    }
    return array_map(fn($i) => new \Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO(
        $i->id()->value(), $i->customerName(), $i->description(),
        $i->status()->value, $i->requestedDeadline()?->format('Y-m-d'), $i->createdAt()->format('c'),
    ), array_values($items));
}
```

- [ ] **Step 5: Create GetInquiryDetail**

`packages/sales/src/Inquiry/Application/GetInquiryDetail/InquiryDetailDTO.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
final readonly class InquiryDetailDTO
{
    /** @param array<array{role:string,skills:string[]}> $requiredRoles
     *  @param array<array{id:string,path:string,mimeType:string,originalName:string}> $attachments */
    public function __construct(
        public string  $id,
        public ?string $customerId,
        public string  $customerName,
        public string  $contactEmail,
        public string  $description,
        public ?string $requestedDeadline,
        public array   $requiredRoles,
        public array   $attachments,
        public string  $status,
        public string  $createdAt,
    ) {}
}
```

`packages/sales/src/Inquiry/Application/GetInquiryDetail/GetInquiryDetailQuery.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
final readonly class GetInquiryDetailQuery
{
    public function __construct(public string $inquiryId) {}
}
```

`packages/sales/src/Inquiry/Application/GetInquiryDetail/GetInquiryDetailHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\GetInquiryDetail;
use Sales\Inquiry\Domain\{InquiryId, InquiryRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetInquiryDetailHandler
{
    public function __construct(private readonly InquiryRepository $repository) {}
    public function __invoke(GetInquiryDetailQuery $query): InquiryDetailDTO
    {
        $inquiry = $this->repository->get(InquiryId::fromString($query->inquiryId));
        return new InquiryDetailDTO(
            id: $inquiry->id()->value(),
            customerId: $inquiry->customerId(),
            customerName: $inquiry->customerName(),
            contactEmail: $inquiry->contactEmail(),
            description: $inquiry->description(),
            requestedDeadline: $inquiry->requestedDeadline()?->format('Y-m-d'),
            requiredRoles: array_map(
                fn($r) => ['role' => $r->role->value, 'skills' => $r->skills],
                $inquiry->requiredRoles(),
            ),
            attachments: array_map(
                fn($a) => ['id' => $a->id, 'path' => $a->path, 'mimeType' => $a->mimeType, 'originalName' => $a->originalName],
                $inquiry->attachments(),
            ),
            status: $inquiry->status()->value,
            createdAt: $inquiry->createdAt()->format('c'),
        );
    }
}
```

- [ ] **Step 6: Run all Inquiry application tests**

```bash
vendor/bin/phpunit packages/sales/tests/Inquiry/Application/ --testdox
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add packages/sales/src/Inquiry/ packages/sales/tests/Inquiry/Application/
git commit -m "feat(sales): add AdvanceInquiryStatus, AttachFile, and Inquiry query handlers"
```

---

## Task 6: Inquiry infrastructure — DBAL repository + LocalFileStorage

**Files:**
- Create: `packages/sales/src/Inquiry/Infrastructure/Persistence/DoctrineInquiryRepository.php`
- Create: `packages/sales/src/Inquiry/Infrastructure/Storage/LocalFileStorage.php`

- [ ] **Step 1: Create `DoctrineInquiryRepository.php`**

```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO;
use Sales\Inquiry\Domain\{Attachment, Inquiry, InquiryId, InquiryNotFoundException,
    InquiryRepository, InquiryStatus, RequiredRole, SalesRole};

final class DoctrineInquiryRepository implements InquiryRepository
{
    public function __construct(private readonly Connection $connection) {}

    public function get(InquiryId $id): Inquiry
    {
        $row = $this->connection->executeQuery(
            'SELECT id, customer_id, customer_name, contact_email, description,
                    requested_deadline, required_roles, status, created_at
             FROM sales_inquiries WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();

        if (!$row) {
            throw new InquiryNotFoundException($id->value());
        }

        $attachmentRows = $this->connection->executeQuery(
            'SELECT id, path, mime_type, original_name FROM sales_inquiry_attachments WHERE inquiry_id = :id ORDER BY created_at',
            ['id' => $id->value()],
        )->fetchAllAssociative();

        return $this->hydrate($row, $attachmentRows);
    }

    /** @return InquiryListItemDTO[] */
    public function findAll(?string $status): array
    {
        $sql = 'SELECT id, customer_name, description, status, requested_deadline, created_at FROM sales_inquiries';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';

        return array_map(
            fn(array $row) => new InquiryListItemDTO(
                $row['id'], $row['customer_name'], $row['description'],
                $row['status'],
                $row['requested_deadline'],
                $row['created_at'],
            ),
            $this->connection->executeQuery($sql, $params)->fetchAllAssociative(),
        );
    }

    public function save(Inquiry $inquiry): void
    {
        $this->connection->executeStatement(
            'INSERT INTO sales_inquiries
                (id, customer_id, customer_name, contact_email, description, requested_deadline, required_roles, status, created_at)
             VALUES (:id, :customer_id, :customer_name, :contact_email, :description, :requested_deadline, :required_roles, :status, :created_at)
             ON CONFLICT (id) DO UPDATE SET
                customer_id = EXCLUDED.customer_id,
                customer_name = EXCLUDED.customer_name,
                contact_email = EXCLUDED.contact_email,
                description = EXCLUDED.description,
                requested_deadline = EXCLUDED.requested_deadline,
                required_roles = EXCLUDED.required_roles,
                status = EXCLUDED.status',
            [
                'id'                => $inquiry->id()->value(),
                'customer_id'       => $inquiry->customerId(),
                'customer_name'     => $inquiry->customerName(),
                'contact_email'     => $inquiry->contactEmail(),
                'description'       => $inquiry->description(),
                'requested_deadline'=> $inquiry->requestedDeadline()?->format('Y-m-d'),
                'required_roles'    => json_encode(array_map(
                    fn(RequiredRole $r) => ['role' => $r->role->value, 'skills' => $r->skills],
                    $inquiry->requiredRoles(),
                )),
                'status'            => $inquiry->status()->value,
                'created_at'        => $inquiry->createdAt()->format('Y-m-d H:i:s'),
            ],
        );

        // sync attachments: insert new ones (upsert by id)
        foreach ($inquiry->attachments() as $attachment) {
            $this->connection->executeStatement(
                'INSERT INTO sales_inquiry_attachments (id, inquiry_id, path, mime_type, original_name, created_at)
                 VALUES (:id, :inquiry_id, :path, :mime_type, :original_name, NOW())
                 ON CONFLICT (id) DO NOTHING',
                [
                    'id'           => $attachment->id,
                    'inquiry_id'   => $inquiry->id()->value(),
                    'path'         => $attachment->path,
                    'mime_type'    => $attachment->mimeType,
                    'original_name'=> $attachment->originalName,
                ],
            );
        }
    }

    private function hydrate(array $row, array $attachmentRows): Inquiry
    {
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            json_decode($row['required_roles'], true) ?? [],
        );
        $attachments = array_map(
            fn(array $a) => new Attachment($a['path'], $a['mime_type'], $a['original_name'], $a['id']),
            $attachmentRows,
        );
        return Inquiry::reconstruct(
            InquiryId::fromString($row['id']),
            $row['customer_id'],
            $row['customer_name'],
            $row['contact_email'],
            $row['description'],
            $row['requested_deadline'] ? new \DateTimeImmutable($row['requested_deadline']) : null,
            $roles,
            $attachments,
            InquiryStatus::from($row['status']),
            new \DateTimeImmutable($row['created_at']),
        );
    }
}
```

- [ ] **Step 2: Create `LocalFileStorage.php`**

```php
<?php
declare(strict_types=1);

namespace Sales\Inquiry\Infrastructure\Storage;

use Symfony\Component\Uid\Uuid;

final class LocalFileStorage implements FileStorage
{
    private const ALLOWED_MIMES = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];

    public function __construct(private readonly string $uploadDir) {}

    public function store(string $tmpPath, string $originalName, string $mimeType): string
    {
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException("Unsupported MIME type: $mimeType");
        }
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = Uuid::v7() . ($ext ? '.' . $ext : '');
        $dest     = $this->uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $dest) && !rename($tmpPath, $dest)) {
            throw new \RuntimeException("Could not store file at $dest");
        }
        return $filename;
    }

    public function absolutePath(string $storedPath): string
    {
        return $this->uploadDir . '/' . $storedPath;
    }
}
```

- [ ] **Step 3: Verify no syntax errors**

```bash
php -l packages/sales/src/Inquiry/Infrastructure/Persistence/DoctrineInquiryRepository.php
php -l packages/sales/src/Inquiry/Infrastructure/Storage/LocalFileStorage.php
```

Expected: "No syntax errors detected".

- [ ] **Step 4: Commit**

```bash
git add packages/sales/src/Inquiry/Infrastructure/
git commit -m "feat(sales): add DoctrineInquiryRepository (DBAL) and LocalFileStorage"
```

---

## Task 7: Inquiry HTTP controllers

**Files:**
- Create: `packages/sales/src/Inquiry/Infrastructure/Http/Request/{CreateInquiryRequest,UpdateInquiryRequest,AdvanceInquiryStatusRequest}.php`
- Create: `packages/sales/src/Inquiry/Infrastructure/Http/{CreateInquiryController,UpdateInquiryController,GetInquiryListController,GetInquiryDetailController,AdvanceInquiryStatusController,AttachFileController}.php`
- Create: `packages/sales/src/Infrastructure/Http/GetAttachmentController.php`
- Create: `packages/sales/src/Infrastructure/Security/{SalesPermission,SalesVoter}.php`

- [ ] **Step 1: Create SalesPermission + SalesVoter**

`packages/sales/src/Infrastructure/Security/SalesPermission.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Infrastructure\Security;
enum SalesPermission: string
{
    case ManageInquiries = 'sales.inquiries.manage';
    case ManageQuotes    = 'sales.quotes.manage';
}
```

`packages/sales/src/Infrastructure/Security/SalesVoter.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Infrastructure\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
/** @extends Voter<string, null> */
final class SalesVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, array_column(SalesPermission::cases(), 'value'), true);
    }
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($token->getUser() === null) { return false; }
        $role = 'ROLE_' . strtoupper(str_replace('.', '_', $attribute));
        return in_array($role, $token->getRoleNames(), true);
    }
}
```

- [ ] **Step 2: Create HTTP Request DTOs**

`packages/sales/src/Inquiry/Infrastructure/Http/Request/CreateInquiryRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class CreateInquiryRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $customer_name = '',
        #[Assert\NotBlank] #[Assert\Email] public readonly string $contact_email = '',
        #[Assert\NotBlank] public readonly string $description = '',
        public readonly ?string $customer_id = null,
        public readonly ?string $requested_deadline = null,
        #[Assert\Type('array')] public readonly array $required_roles = [],
    ) {}
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/Request/UpdateInquiryRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class UpdateInquiryRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $customer_name = '',
        #[Assert\NotBlank] #[Assert\Email] public readonly string $contact_email = '',
        #[Assert\NotBlank] public readonly string $description = '',
        public readonly ?string $customer_id = null,
        public readonly ?string $requested_deadline = null,
        #[Assert\Type('array')] public readonly array $required_roles = [],
    ) {}
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/Request/AdvanceInquiryStatusRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http\Request;
final class AdvanceInquiryStatusRequest
{
    public function __construct(public readonly ?string $target_status = null) {}
}
```

- [ ] **Step 3: Create Inquiry controllers**

`packages/sales/src/Inquiry/Infrastructure/Http/CreateInquiryController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\CreateInquiry\CreateInquiryCommand;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Inquiry\Infrastructure\Http\Request\CreateInquiryRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class CreateInquiryController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(#[MapRequestPayload] CreateInquiryRequest $request): JsonResponse
    {
        $id = InquiryId::generate()->value();
        $this->commandBus->dispatch(new CreateInquiryCommand(
            inquiryId: $id,
            customerId: $request->customer_id,
            customerName: $request->customer_name,
            contactEmail: $request->contact_email,
            description: $request->description,
            requestedDeadline: $request->requested_deadline,
            requiredRoles: $request->required_roles,
        ));
        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/GetInquiryListController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\GetInquiryList\GetInquiryListQuery;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetInquiryListController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetInquiryListQuery($request->query->get('status')));
        return new JsonResponse(array_map(fn($i) => [
            'id'                 => $i->id,
            'customer_name'      => $i->customerName,
            'description'        => $i->description,
            'status'             => $i->status,
            'requested_deadline' => $i->requestedDeadline,
            'created_at'         => $i->createdAt,
        ], $items));
    }
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/GetInquiryDetailController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\GetInquiryDetail\GetInquiryDetailQuery;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetInquiryDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(string $id): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetInquiryDetailQuery($id));
        return new JsonResponse([
            'id'                 => $dto->id,
            'customer_id'        => $dto->customerId,
            'customer_name'      => $dto->customerName,
            'contact_email'      => $dto->contactEmail,
            'description'        => $dto->description,
            'requested_deadline' => $dto->requestedDeadline,
            'required_roles'     => $dto->requiredRoles,
            'attachments'        => $dto->attachments,
            'status'             => $dto->status,
            'created_at'         => $dto->createdAt,
        ]);
    }
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/UpdateInquiryController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\UpdateInquiry\UpdateInquiryCommand;
use Sales\Inquiry\Infrastructure\Http\Request\UpdateInquiryRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}', methods: ['PUT'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class UpdateInquiryController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $id, #[MapRequestPayload] UpdateInquiryRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateInquiryCommand(
            $id, $request->customer_id, $request->customer_name, $request->contact_email,
            $request->description, $request->requested_deadline, $request->required_roles,
        ));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/AdvanceInquiryStatusController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand;
use Sales\Inquiry\Infrastructure\Http\Request\AdvanceInquiryStatusRequest;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}/commands/advance-status', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class AdvanceInquiryStatusController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $id, #[MapRequestPayload] AdvanceInquiryStatusRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new AdvanceInquiryStatusCommand($id, $request->target_status));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

`packages/sales/src/Inquiry/Infrastructure/Http/AttachFileController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\AttachFile\AttachFileCommand;
use Sales\Inquiry\Infrastructure\Storage\FileStorage;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}/attachments', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class AttachFileController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly FileStorage         $fileStorage,
    ) {}
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if ($file === null) {
            return new JsonResponse(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $storedPath = $this->fileStorage->store(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/octet-stream',
        );
        $this->commandBus->dispatch(new AttachFileCommand(
            $id, $storedPath, $file->getMimeType() ?? '', $file->getClientOriginalName(),
        ));
        return new JsonResponse(['path' => $storedPath], Response::HTTP_CREATED);
    }
}
```

`packages/sales/src/Infrastructure/Http/GetAttachmentController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Infrastructure\Http;
use Sales\Inquiry\Infrastructure\Storage\FileStorage;
use Sales\Infrastructure\Security\SalesPermission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/attachments/{filename}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetAttachmentController extends AbstractController
{
    public function __construct(private readonly FileStorage $fileStorage) {}
    public function __invoke(string $filename): Response
    {
        $absolutePath = $this->fileStorage->absolutePath($filename);
        if (!file_exists($absolutePath)) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
        return new BinaryFileResponse($absolutePath);
    }
}
```

- [ ] **Step 4: Verify container**

```bash
php bin/console cache:clear && php bin/console debug:router | grep sales
```

Expected: lists all sales routes.

- [ ] **Step 5: Commit**

```bash
git add packages/sales/src/Inquiry/Infrastructure/Http/ packages/sales/src/Infrastructure/
git commit -m "feat(sales): add Inquiry HTTP controllers and SalesPermission"
```

---

## Task 8: Quote domain

**Files:**
- Create: `packages/sales/src/Quote/Domain/{Money,QuoteId,QuotePhaseId,QuoteStatus,QuotePhase,Quote,QuoteRepository,QuoteNotFoundException,QuoteAccepted}.php`
- Test: `packages/sales/tests/Quote/Domain/QuoteTest.php`

- [ ] **Step 1: Write failing test**

`packages/sales/tests/Quote/Domain/QuoteTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Domain\{Money, Quote, QuoteId, QuotePhase, QuotePhaseId, QuoteStatus};
use Sales\Inquiry\Domain\SalesRole;
use PHPUnit\Framework\TestCase;
final class QuoteTest extends TestCase
{
    private QuoteId $id;
    private InquiryId $inquiryId;
    protected function setUp(): void
    {
        $this->id        = QuoteId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->inquiryId = InquiryId::fromString('018e8f2a-1234-7000-8000-000000000002');
    }
    public function test_creates_quote_in_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $this->assertSame(QuoteStatus::Draft, $quote->status());
        $this->assertEquals(new Money(0, 'CZK'), $quote->totalPrice());
    }
    public function test_adds_phase_and_computes_total(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $phase = new QuotePhase(QuotePhaseId::fromString('018e8f2a-1234-7000-8000-000000000003'), 'Backend', SalesRole::Backend, 10, new Money(10000, 'CZK'));
        $quote->addPhase($phase);
        $this->assertEquals(new Money(100000, 'CZK'), $quote->totalPrice());
    }
    public function test_can_send_from_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $quote->send();
        $this->assertSame(QuoteStatus::Sent, $quote->status());
    }
    public function test_cannot_accept_draft(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $this->expectException(\DomainException::class);
        $quote->accept();
    }
    public function test_accept_emits_quote_accepted_event(): void
    {
        $quote = Quote::create($this->id, $this->inquiryId, new \DateTimeImmutable('+30 days'), '');
        $quote->send();
        $quote->accept();
        $events = $quote->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\Sales\Quote\Domain\QuoteAccepted::class, $events[0]);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
vendor/bin/phpunit packages/sales/tests/Quote/Domain/QuoteTest.php --testdox
```

Expected: class not found.

- [ ] **Step 3: Create Quote domain classes**

`packages/sales/src/Quote/Domain/Money.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
final class Money
{
    public function __construct(
        public readonly int    $amount,   // in cents / halere
        public readonly string $currency, // e.g. 'CZK'
    ) {}
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
        return new self($this->amount + $other->amount, $this->currency);
    }
    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
```

`packages/sales/src/Quote/Domain/QuoteId.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Symfony\Component\Uid\Uuid;
final class QuoteId
{
    private function __construct(private readonly string $value) {}
    public static function generate(): self { return new self((string) Uuid::v7()); }
    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) { throw new \InvalidArgumentException("Invalid QuoteId: '$value'"); }
        return new self($value);
    }
    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
```

`packages/sales/src/Quote/Domain/QuotePhaseId.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Symfony\Component\Uid\Uuid;
final class QuotePhaseId
{
    private function __construct(private readonly string $value) {}
    public static function generate(): self { return new self((string) Uuid::v7()); }
    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) { throw new \InvalidArgumentException("Invalid QuotePhaseId: '$value'"); }
        return new self($value);
    }
    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}
```

`packages/sales/src/Quote/Domain/QuoteStatus.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
enum QuoteStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
```

`packages/sales/src/Quote/Domain/QuotePhase.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\SalesRole;
final class QuotePhase
{
    public readonly Money $subtotal;
    public function __construct(
        private readonly QuotePhaseId $id,
        private string                $name,
        private SalesRole             $requiredRole,
        private int                   $durationDays,
        private Money                 $dailyRate,
    ) {
        $this->subtotal = $dailyRate->multiply($durationDays);
    }
    public static function reconstruct(QuotePhaseId $id, string $name, SalesRole $role, int $durationDays, Money $dailyRate): self
    {
        return new self($id, $name, $role, $durationDays, $dailyRate);
    }
    public function update(string $name, SalesRole $role, int $durationDays, Money $dailyRate): self
    {
        return new self($this->id, $name, $role, $durationDays, $dailyRate);
    }
    public function id(): QuotePhaseId { return $this->id; }
    public function name(): string { return $this->name; }
    public function requiredRole(): SalesRole { return $this->requiredRole; }
    public function durationDays(): int { return $this->durationDays; }
    public function dailyRate(): Money { return $this->dailyRate; }
}
```

`packages/sales/src/Quote/Domain/QuoteNotFoundException.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
final class QuoteNotFoundException extends \DomainException
{
    public function __construct(string $id) { parent::__construct("Quote '$id' not found"); }
}
```

`packages/sales/src/Quote/Domain/QuoteAccepted.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
final class QuoteAccepted
{
    public function __construct(
        public readonly QuoteId   $quoteId,
        public readonly InquiryId $inquiryId,
    ) {}
}
```

`packages/sales/src/Quote/Domain/QuoteRepository.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
interface QuoteRepository
{
    public function get(QuoteId $id): Quote;
    public function findByInquiry(string $inquiryId): array; // returns Quote[]
    public function save(Quote $quote): void;
}
```

`packages/sales/src/Quote/Domain/Quote.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
use SharedKernel\Domain\AggregateRoot;
final class Quote extends AggregateRoot
{
    /** @var QuotePhase[] */
    private array $phases;
    private Money $totalPrice;
    private function __construct(
        private readonly QuoteId    $id,
        private readonly InquiryId  $inquiryId,
        private \DateTimeImmutable  $validUntil,
        private QuoteStatus         $status,
        private ?string             $pdfPath,
        private string              $notes,
        array                       $phases,
    ) {
        $this->phases     = $phases;
        $this->totalPrice = $this->computeTotal();
    }
    public static function create(QuoteId $id, InquiryId $inquiryId, \DateTimeImmutable $validUntil, string $notes): self
    {
        return new self($id, $inquiryId, $validUntil, QuoteStatus::Draft, null, $notes, []);
    }
    /** @param QuotePhase[] $phases */
    public static function reconstruct(QuoteId $id, InquiryId $inquiryId, \DateTimeImmutable $validUntil, QuoteStatus $status, ?string $pdfPath, string $notes, array $phases): self
    {
        return new self($id, $inquiryId, $validUntil, $status, $pdfPath, $notes, $phases);
    }
    public function addPhase(QuotePhase $phase): void
    {
        $this->phases[]   = $phase;
        $this->totalPrice = $this->computeTotal();
    }
    public function updatePhase(QuotePhaseId $phaseId, string $name, \Sales\Inquiry\Domain\SalesRole $role, int $durationDays, Money $dailyRate): void
    {
        foreach ($this->phases as $i => $phase) {
            if ($phase->id()->equals($phaseId)) {
                $this->phases[$i] = $phase->update($name, $role, $durationDays, $dailyRate);
                $this->totalPrice = $this->computeTotal();
                return;
            }
        }
        throw new \DomainException("Phase '{$phaseId->value()}' not found in quote");
    }
    public function send(): void
    {
        if ($this->status !== QuoteStatus::Draft) {
            throw new \DomainException("Only draft quotes can be sent");
        }
        $this->status = QuoteStatus::Sent;
    }
    public function accept(): void
    {
        if ($this->status !== QuoteStatus::Sent) {
            throw new \DomainException("Only sent quotes can be accepted");
        }
        $this->status = QuoteStatus::Accepted;
        $this->recordEvent(new QuoteAccepted($this->id, $this->inquiryId));
    }
    public function reject(): void
    {
        if ($this->status !== QuoteStatus::Sent) {
            throw new \DomainException("Only sent quotes can be rejected");
        }
        $this->status = QuoteStatus::Rejected;
    }
    public function markPdfGenerated(string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }
    private function computeTotal(): Money
    {
        $total = new Money(0, 'CZK');
        foreach ($this->phases as $phase) {
            $total = $total->add($phase->subtotal);
        }
        return $total;
    }
    public function id(): QuoteId { return $this->id; }
    public function inquiryId(): InquiryId { return $this->inquiryId; }
    public function validUntil(): \DateTimeImmutable { return $this->validUntil; }
    public function status(): QuoteStatus { return $this->status; }
    public function pdfPath(): ?string { return $this->pdfPath; }
    public function notes(): string { return $this->notes; }
    /** @return QuotePhase[] */
    public function phases(): array { return $this->phases; }
    public function totalPrice(): Money { return $this->totalPrice; }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
vendor/bin/phpunit packages/sales/tests/Quote/Domain/QuoteTest.php --testdox
```

Expected: 5 tests, OK.

- [ ] **Step 5: Commit**

```bash
git add packages/sales/src/Quote/Domain/ packages/sales/tests/Quote/Domain/
git commit -m "feat(sales): add Quote aggregate root and domain objects"
```

---

## Task 9: Quote application — command handlers

**Files:**
- Create: `packages/sales/src/Quote/Application/{CreateQuote,AddQuotePhase,UpdateQuotePhase,SendQuote,RejectQuote}/{Command,Handler}.php`
- Create: `packages/sales/src/Quote/Application/AcceptQuote/{AcceptQuoteCommand,AcceptQuoteHandler}.php`
- Test: `packages/sales/tests/Quote/Application/{CreateQuoteHandlerTest,AddQuotePhaseHandlerTest,AcceptQuoteHandlerTest}.php`

- [ ] **Step 1: Create Quote test doubles**

`packages/sales/tests/Quote/Application/InMemoryQuoteRepository.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Quote\Domain\{Quote, QuoteId, QuoteNotFoundException, QuoteRepository};
final class InMemoryQuoteRepository implements QuoteRepository
{
    private array $items = [];
    public function get(QuoteId $id): Quote
    {
        return $this->items[$id->value()] ?? throw new QuoteNotFoundException($id->value());
    }
    public function findByInquiry(string $inquiryId): array
    {
        return array_values(array_filter($this->items, fn($q) => $q->inquiryId()->value() === $inquiryId));
    }
    public function save(Quote $quote): void
    {
        $this->items[$quote->id()->value()] = $quote;
    }
}
```

- [ ] **Step 2: Write failing test for CreateQuote**

`packages/sales/tests/Quote/Application/CreateQuoteHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Domain\{QuoteId, QuoteStatus};
use Sales\Inquiry\Domain\InquiryId;
use PHPUnit\Framework\TestCase;
final class CreateQuoteHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private CreateQuoteHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $this->handler    = new CreateQuoteHandler($this->repository);
    }
    public function test_creates_quote_in_draft(): void
    {
        $qid = QuoteId::generate()->value();
        $iid = InquiryId::generate()->value();
        ($this->handler)(new CreateQuoteCommand($qid, $iid, date('Y-m-d', strtotime('+30 days')), ''));
        $quote = $this->repository->get(QuoteId::fromString($qid));
        $this->assertSame(QuoteStatus::Draft, $quote->status());
        $this->assertSame($iid, $quote->inquiryId()->value());
    }
}
```

- [ ] **Step 3: Implement CreateQuote**

`packages/sales/src/Quote/Application/CreateQuote/CreateQuoteCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\CreateQuote;
final readonly class CreateQuoteCommand
{
    public function __construct(
        public string $quoteId,
        public string $inquiryId,
        public string $validUntil,
        public string $notes,
    ) {}
}
```

`packages/sales/src/Quote/Application/CreateQuote/CreateQuoteHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\CreateQuote;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Domain\{Quote, QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class CreateQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(CreateQuoteCommand $command): void
    {
        $quote = Quote::create(
            QuoteId::fromString($command->quoteId),
            InquiryId::fromString($command->inquiryId),
            new \DateTimeImmutable($command->validUntil),
            $command->notes,
        );
        $this->repository->save($quote);
    }
}
```

- [ ] **Step 4: Write failing test for AddQuotePhase**

`packages/sales/tests/Quote/Application/AddQuotePhaseHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\{InquiryId, SalesRole};
use Sales\Quote\Application\AddQuotePhase\{AddQuotePhaseCommand, AddQuotePhaseHandler};
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Domain\{Money, QuoteId, QuotePhaseId};
use PHPUnit\Framework\TestCase;
final class AddQuotePhaseHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private string $quoteId;
    private AddQuotePhaseHandler $handler;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $qid = QuoteId::generate()->value();
        (new CreateQuoteHandler($this->repository))(new CreateQuoteCommand($qid, InquiryId::generate()->value(), date('Y-m-d', strtotime('+30 days')), ''));
        $this->quoteId = $qid;
        $this->handler = new AddQuotePhaseHandler($this->repository);
    }
    public function test_adds_phase_and_updates_total(): void
    {
        $pid = QuotePhaseId::generate()->value();
        ($this->handler)(new AddQuotePhaseCommand($this->quoteId, $pid, 'Backend', 'backend', 5, 10000, 'CZK'));
        $quote = $this->repository->get(QuoteId::fromString($this->quoteId));
        $this->assertCount(1, $quote->phases());
        $this->assertSame(50000, $quote->totalPrice()->amount);
    }
}
```

- [ ] **Step 5: Implement AddQuotePhase, UpdateQuotePhase**

`packages/sales/src/Quote/Application/AddQuotePhase/AddQuotePhaseCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AddQuotePhase;
final readonly class AddQuotePhaseCommand
{
    public function __construct(
        public string $quoteId,
        public string $phaseId,
        public string $name,
        public string $requiredRole,
        public int    $durationDays,
        public int    $dailyRateAmount,
        public string $dailyRateCurrency,
    ) {}
}
```

`packages/sales/src/Quote/Application/AddQuotePhase/AddQuotePhaseHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AddQuotePhase;
use Sales\Inquiry\Domain\SalesRole;
use Sales\Quote\Domain\{Money, QuoteId, QuotePhase, QuotePhaseId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AddQuotePhaseHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(AddQuotePhaseCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->addPhase(new QuotePhase(
            QuotePhaseId::fromString($command->phaseId),
            $command->name,
            SalesRole::from($command->requiredRole),
            $command->durationDays,
            new Money($command->dailyRateAmount, $command->dailyRateCurrency),
        ));
        $this->repository->save($quote);
    }
}
```

`packages/sales/src/Quote/Application/UpdateQuotePhase/UpdateQuotePhaseCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\UpdateQuotePhase;
final readonly class UpdateQuotePhaseCommand
{
    public function __construct(
        public string $quoteId,
        public string $phaseId,
        public string $name,
        public string $requiredRole,
        public int    $durationDays,
        public int    $dailyRateAmount,
        public string $dailyRateCurrency,
    ) {}
}
```

`packages/sales/src/Quote/Application/UpdateQuotePhase/UpdateQuotePhaseHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\UpdateQuotePhase;
use Sales\Inquiry\Domain\SalesRole;
use Sales\Quote\Domain\{Money, QuoteId, QuotePhaseId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class UpdateQuotePhaseHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(UpdateQuotePhaseCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->updatePhase(
            QuotePhaseId::fromString($command->phaseId),
            $command->name,
            SalesRole::from($command->requiredRole),
            $command->durationDays,
            new Money($command->dailyRateAmount, $command->dailyRateCurrency),
        );
        $this->repository->save($quote);
    }
}
```

- [ ] **Step 6: Implement SendQuote + RejectQuote**

`packages/sales/src/Quote/Application/SendQuote/SendQuoteCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\SendQuote;
final readonly class SendQuoteCommand
{
    public function __construct(public string $quoteId) {}
}
```

`packages/sales/src/Quote/Application/SendQuote/SendQuoteHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\SendQuote;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class SendQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(SendQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->send();
        $this->repository->save($quote);
    }
}
```

`packages/sales/src/Quote/Application/RejectQuote/RejectQuoteCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\RejectQuote;
final readonly class RejectQuoteCommand
{
    public function __construct(public string $quoteId) {}
}
```

`packages/sales/src/Quote/Application/RejectQuote/RejectQuoteHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\RejectQuote;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class RejectQuoteHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(RejectQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->reject();
        $this->repository->save($quote);
    }
}
```

- [ ] **Step 7: Write failing test for AcceptQuote**

`packages/sales/tests/Quote/Application/AcceptQuoteHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Application\AcceptQuote\{AcceptQuoteCommand, AcceptQuoteHandler};
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Application\SendQuote\{SendQuoteCommand, SendQuoteHandler};
use Sales\Quote\Domain\{QuoteAccepted, QuoteId, QuoteStatus};
use Sales\Tests\Inquiry\Application\{SpyCommandBus, SpyEventBus};
use PHPUnit\Framework\TestCase;
final class AcceptQuoteHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private SpyEventBus $eventBus;
    private SpyCommandBus $commandBus;
    private string $quoteId;
    private string $inquiryId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $this->eventBus   = new SpyEventBus();
        $this->commandBus = new SpyCommandBus();
        $qid = QuoteId::generate()->value();
        $iid = InquiryId::generate()->value();
        (new CreateQuoteHandler($this->repository))(new CreateQuoteCommand($qid, $iid, date('Y-m-d', strtotime('+30 days')), ''));
        (new SendQuoteHandler($this->repository))(new SendQuoteCommand($qid));
        $this->quoteId   = $qid;
        $this->inquiryId = $iid;
    }
    public function test_accepts_quote_and_emits_event(): void
    {
        $handler = new AcceptQuoteHandler($this->repository, $this->eventBus, $this->commandBus);
        ($handler)(new AcceptQuoteCommand($this->quoteId));
        $quote = $this->repository->get(QuoteId::fromString($this->quoteId));
        $this->assertSame(QuoteStatus::Accepted, $quote->status());
        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(QuoteAccepted::class, $this->eventBus->dispatched[0]);
    }
    public function test_advances_inquiry_to_won(): void
    {
        $handler = new AcceptQuoteHandler($this->repository, $this->eventBus, $this->commandBus);
        ($handler)(new AcceptQuoteCommand($this->quoteId));
        $this->assertCount(1, $this->commandBus->dispatched);
        $cmd = $this->commandBus->dispatched[0];
        $this->assertInstanceOf(\Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand::class, $cmd);
        $this->assertSame('won', $cmd->targetStatus);
        $this->assertSame($this->inquiryId, $cmd->inquiryId);
    }
}
```

- [ ] **Step 8: Implement AcceptQuote**

`packages/sales/src/Quote/Application/AcceptQuote/AcceptQuoteCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AcceptQuote;
final readonly class AcceptQuoteCommand
{
    public function __construct(public string $quoteId) {}
}
```

`packages/sales/src/Quote/Application/AcceptQuote/AcceptQuoteHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AcceptQuote;
use Sales\Inquiry\Application\AdvanceInquiryStatus\AdvanceInquiryStatusCommand;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use SharedKernel\Application\{CommandBusInterface, EventBusInterface};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class AcceptQuoteHandler
{
    public function __construct(
        private readonly QuoteRepository    $repository,
        private readonly EventBusInterface  $eventBus,
        private readonly CommandBusInterface $commandBus,
    ) {}
    public function __invoke(AcceptQuoteCommand $command): void
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->accept();
        $this->repository->save($quote);
        foreach ($quote->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
        $this->commandBus->dispatch(new AdvanceInquiryStatusCommand(
            $quote->inquiryId()->value(), 'won',
        ));
    }
}
```

- [ ] **Step 9: Run all Quote application tests**

```bash
vendor/bin/phpunit packages/sales/tests/Quote/Application/ --testdox
```

Expected: all pass.

- [ ] **Step 10: Commit**

```bash
git add packages/sales/src/Quote/Application/ packages/sales/tests/Quote/Application/
git commit -m "feat(sales): add Quote command handlers including AcceptQuote"
```

---

## Task 10: Quote application — GetQuoteDetail + ExportQuotePdf

**Files:**
- Create: `packages/sales/src/Quote/Application/GetQuoteDetail/{GetQuoteDetailQuery,GetQuoteDetailHandler,QuoteDetailDTO,QuotePhaseDTO}.php`
- Create: `packages/sales/src/Quote/Application/ExportQuotePdf/{ExportQuotePdfCommand,ExportQuotePdfHandler}.php`
- Create: `packages/sales/src/Quote/Infrastructure/Pdf/QuotePdfGenerator.php`
- Test: `packages/sales/tests/Quote/Application/GetQuoteDetailHandlerTest.php`

- [ ] **Step 1: Write failing test**

`packages/sales/tests/Quote/Application/GetQuoteDetailHandlerTest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Tests\Quote\Application;
use Sales\Inquiry\Domain\InquiryId;
use Sales\Quote\Application\CreateQuote\{CreateQuoteCommand, CreateQuoteHandler};
use Sales\Quote\Application\GetQuoteDetail\{GetQuoteDetailHandler, GetQuoteDetailQuery};
use Sales\Quote\Domain\{QuoteId, QuoteStatus};
use PHPUnit\Framework\TestCase;
final class GetQuoteDetailHandlerTest extends TestCase
{
    private InMemoryQuoteRepository $repository;
    private string $quoteId;
    protected function setUp(): void
    {
        $this->repository = new InMemoryQuoteRepository();
        $qid = QuoteId::generate()->value();
        (new CreateQuoteHandler($this->repository))(
            new CreateQuoteCommand($qid, InquiryId::generate()->value(), date('Y-m-d', strtotime('+30 days')), 'Notes')
        );
        $this->quoteId = $qid;
    }
    public function test_returns_quote_detail(): void
    {
        $handler = new GetQuoteDetailHandler($this->repository);
        $dto = ($handler)(new GetQuoteDetailQuery($this->quoteId));
        $this->assertSame($this->quoteId, $dto->id);
        $this->assertSame('draft', $dto->status);
        $this->assertSame('Notes', $dto->notes);
        $this->assertSame(0, $dto->totalPriceAmount);
        $this->assertIsArray($dto->phases);
    }
}
```

- [ ] **Step 2: Implement GetQuoteDetail**

`packages/sales/src/Quote/Application/GetQuoteDetail/QuotePhaseDTO.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
final readonly class QuotePhaseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $requiredRole,
        public int    $durationDays,
        public int    $dailyRateAmount,
        public string $dailyRateCurrency,
        public int    $subtotalAmount,
        public string $subtotalCurrency,
    ) {}
}
```

`packages/sales/src/Quote/Application/GetQuoteDetail/QuoteDetailDTO.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
final readonly class QuoteDetailDTO
{
    /** @param QuotePhaseDTO[] $phases */
    public function __construct(
        public string  $id,
        public string  $inquiryId,
        public string  $validUntil,
        public string  $status,
        public ?string $pdfPath,
        public string  $notes,
        public array   $phases,
        public int     $totalPriceAmount,
        public string  $totalPriceCurrency,
    ) {}
}
```

`packages/sales/src/Quote/Application/GetQuoteDetail/GetQuoteDetailQuery.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
final readonly class GetQuoteDetailQuery
{
    public function __construct(public string $quoteId) {}
}
```

`packages/sales/src/Quote/Application/GetQuoteDetail/GetQuoteDetailHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\GetQuoteDetail;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'query.bus')]
final class GetQuoteDetailHandler
{
    public function __construct(private readonly QuoteRepository $repository) {}
    public function __invoke(GetQuoteDetailQuery $query): QuoteDetailDTO
    {
        $quote = $this->repository->get(QuoteId::fromString($query->quoteId));
        $phases = array_map(fn($p) => new QuotePhaseDTO(
            $p->id()->value(), $p->name(), $p->requiredRole()->value,
            $p->durationDays(), $p->dailyRate()->amount, $p->dailyRate()->currency,
            $p->subtotal->amount, $p->subtotal->currency,
        ), $quote->phases());
        return new QuoteDetailDTO(
            id: $quote->id()->value(),
            inquiryId: $quote->inquiryId()->value(),
            validUntil: $quote->validUntil()->format('Y-m-d'),
            status: $quote->status()->value,
            pdfPath: $quote->pdfPath(),
            notes: $quote->notes(),
            phases: $phases,
            totalPriceAmount: $quote->totalPrice()->amount,
            totalPriceCurrency: $quote->totalPrice()->currency,
        );
    }
}
```

- [ ] **Step 3: Implement ExportQuotePdf**

`packages/sales/src/Quote/Infrastructure/Pdf/QuotePdfGenerator.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sales\Quote\Application\GetQuoteDetail\QuoteDetailDTO;
final class QuotePdfGenerator
{
    public function __construct(private readonly string $outputDir) {}
    public function generate(QuoteDetailDTO $quote): string
    {
        $html = $this->renderHtml($quote);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        $filename   = $quote->id . '.pdf';
        $outputPath = $this->outputDir . '/' . $filename;
        file_put_contents($outputPath, $dompdf->output());
        return $outputPath;
    }
    private function renderHtml(QuoteDetailDTO $quote): string
    {
        $phases = '';
        foreach ($quote->phases as $phase) {
            $daily = number_format($phase->dailyRateAmount / 100, 2, ',', ' ');
            $sub   = number_format($phase->subtotalAmount / 100, 2, ',', ' ');
            $phases .= "<tr><td>{$phase->name}</td><td>{$phase->requiredRole}</td>"
                . "<td>{$phase->durationDays}</td><td>{$daily} {$phase->dailyRateCurrency}</td>"
                . "<td>{$sub} {$phase->subtotalCurrency}</td></tr>";
        }
        $total = number_format($quote->totalPriceAmount / 100, 2, ',', ' ');
        return <<<HTML
        <!DOCTYPE html><html><head><meta charset="utf-8">
        <style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ccc;padding:6px;text-align:left}th{background:#f5f5f5}</style>
        </head><body>
        <h1>Nabídka</h1>
        <p>Platnost do: {$quote->validUntil}</p>
        <table><thead><tr><th>Fáze</th><th>Role</th><th>Dny</th><th>Sazba/den</th><th>Mezisoučet</th></tr></thead>
        <tbody>{$phases}</tbody></table>
        <p><strong>Celkem: {$total} {$quote->totalPriceCurrency}</strong></p>
        <p>{$quote->notes}</p>
        </body></html>
        HTML;
    }
}
```

`packages/sales/src/Quote/Application/ExportQuotePdf/ExportQuotePdfCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\ExportQuotePdf;
final readonly class ExportQuotePdfCommand
{
    public function __construct(public string $quoteId) {}
}
```

`packages/sales/src/Quote/Application/ExportQuotePdf/ExportQuotePdfHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Application\ExportQuotePdf;
use Sales\Quote\Application\GetQuoteDetail\{GetQuoteDetailHandler, GetQuoteDetailQuery};
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Sales\Quote\Infrastructure\Pdf\QuotePdfGenerator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class ExportQuotePdfHandler
{
    public function __construct(
        private readonly QuoteRepository    $repository,
        private readonly GetQuoteDetailHandler $detailHandler,
        private readonly QuotePdfGenerator  $pdfGenerator,
    ) {}
    public function __invoke(ExportQuotePdfCommand $command): void
    {
        $dto        = ($this->detailHandler)(new GetQuoteDetailQuery($command->quoteId));
        $outputPath = $this->pdfGenerator->generate($dto);
        $quote      = $this->repository->get(QuoteId::fromString($command->quoteId));
        $quote->markPdfGenerated($outputPath);
        $this->repository->save($quote);
    }
}
```

- [ ] **Step 4: Run Quote tests**

```bash
vendor/bin/phpunit packages/sales/tests/Quote/ --testdox
```

Expected: all pass.

- [ ] **Step 5: Commit**

```bash
git add packages/sales/src/Quote/Application/ packages/sales/src/Quote/Infrastructure/Pdf/ packages/sales/tests/Quote/
git commit -m "feat(sales): add GetQuoteDetail and ExportQuotePdf handlers"
```

---

## Task 11: Quote infrastructure — DBAL repository + HTTP controllers

**Files:**
- Create: `packages/sales/src/Quote/Infrastructure/Persistence/DoctrineQuoteRepository.php`
- Create: `packages/sales/src/Quote/Infrastructure/Http/Request/{CreateQuoteRequest,AddQuotePhaseRequest,UpdateQuotePhaseRequest}.php`
- Create: `packages/sales/src/Quote/Infrastructure/Http/{CreateQuoteController,GetQuoteDetailController,AddQuotePhaseController,UpdateQuotePhaseController,SendQuoteController,AcceptQuoteController,RejectQuoteController,ExportQuotePdfController,GetQuotePdfController}.php`

- [ ] **Step 1: Create `DoctrineQuoteRepository.php`**

```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Persistence;
use Doctrine\DBAL\Connection;
use Sales\Inquiry\Domain\{InquiryId, SalesRole};
use Sales\Quote\Domain\{Money, Quote, QuoteId, QuoteNotFoundException, QuotePhase, QuotePhaseId, QuoteRepository, QuoteStatus};
final class DoctrineQuoteRepository implements QuoteRepository
{
    public function __construct(private readonly Connection $connection) {}
    public function get(QuoteId $id): Quote
    {
        $row = $this->connection->executeQuery(
            'SELECT id, inquiry_id, valid_until, status, pdf_path, notes, total_price_amount, total_price_currency
             FROM sales_quotes WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();
        if (!$row) { throw new QuoteNotFoundException($id->value()); }
        $phaseRows = $this->connection->executeQuery(
            'SELECT id, name, required_role, duration_days, daily_rate_amount, daily_rate_currency
             FROM sales_quote_phases WHERE quote_id = :id ORDER BY sort_order',
            ['id' => $id->value()],
        )->fetchAllAssociative();
        return $this->hydrate($row, $phaseRows);
    }
    public function findByInquiry(string $inquiryId): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, inquiry_id, valid_until, status, pdf_path, notes, total_price_amount, total_price_currency
             FROM sales_quotes WHERE inquiry_id = :iid ORDER BY created_at DESC',
            ['iid' => $inquiryId],
        )->fetchAllAssociative();
        return array_map(fn($row) => $this->get(QuoteId::fromString($row['id'])), $rows);
    }
    public function save(Quote $quote): void
    {
        $this->connection->executeStatement(
            'INSERT INTO sales_quotes (id, inquiry_id, valid_until, status, pdf_path, notes, total_price_amount, total_price_currency, created_at)
             VALUES (:id, :inquiry_id, :valid_until, :status, :pdf_path, :notes, :tpa, :tpc, NOW())
             ON CONFLICT (id) DO UPDATE SET
                valid_until = EXCLUDED.valid_until, status = EXCLUDED.status,
                pdf_path = EXCLUDED.pdf_path, notes = EXCLUDED.notes,
                total_price_amount = EXCLUDED.total_price_amount, total_price_currency = EXCLUDED.total_price_currency',
            [
                'id'         => $quote->id()->value(),
                'inquiry_id' => $quote->inquiryId()->value(),
                'valid_until'=> $quote->validUntil()->format('Y-m-d'),
                'status'     => $quote->status()->value,
                'pdf_path'   => $quote->pdfPath(),
                'notes'      => $quote->notes(),
                'tpa'        => $quote->totalPrice()->amount,
                'tpc'        => $quote->totalPrice()->currency,
            ],
        );
        $this->connection->executeStatement('DELETE FROM sales_quote_phases WHERE quote_id = :id', ['id' => $quote->id()->value()]);
        foreach (array_values($quote->phases()) as $i => $phase) {
            $this->connection->executeStatement(
                'INSERT INTO sales_quote_phases (id, quote_id, name, required_role, duration_days, daily_rate_amount, daily_rate_currency, subtotal_amount, subtotal_currency, sort_order)
                 VALUES (:id, :quote_id, :name, :role, :days, :dra, :drc, :sa, :sc, :sort)',
                [
                    'id'       => $phase->id()->value(),
                    'quote_id' => $quote->id()->value(),
                    'name'     => $phase->name(),
                    'role'     => $phase->requiredRole()->value,
                    'days'     => $phase->durationDays(),
                    'dra'      => $phase->dailyRate()->amount,
                    'drc'      => $phase->dailyRate()->currency,
                    'sa'       => $phase->subtotal->amount,
                    'sc'       => $phase->subtotal->currency,
                    'sort'     => $i,
                ],
            );
        }
    }
    private function hydrate(array $row, array $phaseRows): Quote
    {
        $phases = array_map(fn($p) => QuotePhase::reconstruct(
            QuotePhaseId::fromString($p['id']),
            $p['name'],
            SalesRole::from($p['required_role']),
            (int) $p['duration_days'],
            new Money((int) $p['daily_rate_amount'], $p['daily_rate_currency']),
        ), $phaseRows);
        return Quote::reconstruct(
            QuoteId::fromString($row['id']),
            InquiryId::fromString($row['inquiry_id']),
            new \DateTimeImmutable($row['valid_until']),
            QuoteStatus::from($row['status']),
            $row['pdf_path'],
            $row['notes'],
            $phases,
        );
    }
}
```

- [ ] **Step 2: Create Quote HTTP Request DTOs**

`packages/sales/src/Quote/Infrastructure/Http/Request/CreateQuoteRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class CreateQuoteRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $valid_until = '',
        public readonly string $notes = '',
    ) {}
}
```

`packages/sales/src/Quote/Infrastructure/Http/Request/AddQuotePhaseRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class AddQuotePhaseRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $name = '',
        #[Assert\NotBlank] public readonly string $required_role = '',
        #[Assert\Positive] public readonly int    $duration_days = 1,
        #[Assert\Positive] public readonly int    $daily_rate_amount = 0,
        public readonly string $daily_rate_currency = 'CZK',
    ) {}
}
```

`packages/sales/src/Quote/Infrastructure/Http/Request/UpdateQuotePhaseRequest.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class UpdateQuotePhaseRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $name = '',
        #[Assert\NotBlank] public readonly string $required_role = '',
        #[Assert\Positive] public readonly int    $duration_days = 1,
        #[Assert\Positive] public readonly int    $daily_rate_amount = 0,
        public readonly string $daily_rate_currency = 'CZK',
    ) {}
}
```

- [ ] **Step 3: Create Quote HTTP controllers**

`packages/sales/src/Quote/Infrastructure/Http/CreateQuoteController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\CreateQuote\CreateQuoteCommand;
use Sales\Quote\Domain\QuoteId;
use Sales\Quote\Infrastructure\Http\Request\CreateQuoteRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class CreateQuoteController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, #[MapRequestPayload] CreateQuoteRequest $request): JsonResponse
    {
        $id = QuoteId::generate()->value();
        $this->commandBus->dispatch(new CreateQuoteCommand($id, $inquiryId, $request->valid_until, $request->notes));
        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }
}
```

`packages/sales/src/Quote/Infrastructure/Http/GetQuoteDetailController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\GetQuoteDetail\GetQuoteDetailQuery;
use SharedKernel\Application\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class GetQuoteDetailController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus) {}
    public function __invoke(string $inquiryId, string $quoteId): JsonResponse
    {
        $dto = $this->queryBus->dispatch(new GetQuoteDetailQuery($quoteId));
        return new JsonResponse([
            'id'                   => $dto->id,
            'inquiry_id'           => $dto->inquiryId,
            'valid_until'          => $dto->validUntil,
            'status'               => $dto->status,
            'pdf_path'             => $dto->pdfPath,
            'notes'                => $dto->notes,
            'phases'               => array_map(fn($p) => [
                'id' => $p->id, 'name' => $p->name, 'required_role' => $p->requiredRole,
                'duration_days' => $p->durationDays,
                'daily_rate_amount' => $p->dailyRateAmount,
                'daily_rate_currency' => $p->dailyRateCurrency,
                'subtotal_amount' => $p->subtotalAmount,
                'subtotal_currency' => $p->subtotalCurrency,
            ], $dto->phases),
            'total_price_amount'   => $dto->totalPriceAmount,
            'total_price_currency' => $dto->totalPriceCurrency,
        ]);
    }
}
```

`packages/sales/src/Quote/Infrastructure/Http/AddQuotePhaseController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\AddQuotePhase\AddQuotePhaseCommand;
use Sales\Quote\Domain\QuotePhaseId;
use Sales\Quote\Infrastructure\Http\Request\AddQuotePhaseRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/phases', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class AddQuotePhaseController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId, #[MapRequestPayload] AddQuotePhaseRequest $req): JsonResponse
    {
        $pid = QuotePhaseId::generate()->value();
        $this->commandBus->dispatch(new AddQuotePhaseCommand($quoteId, $pid, $req->name, $req->required_role, $req->duration_days, $req->daily_rate_amount, $req->daily_rate_currency));
        return new JsonResponse(['id' => $pid], Response::HTTP_CREATED);
    }
}
```

`packages/sales/src/Quote/Infrastructure/Http/UpdateQuotePhaseController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\UpdateQuotePhase\UpdateQuotePhaseCommand;
use Sales\Quote\Infrastructure\Http\Request\UpdateQuotePhaseRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/phases/{phaseId}', methods: ['PUT'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class UpdateQuotePhaseController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId, string $phaseId, #[MapRequestPayload] UpdateQuotePhaseRequest $req): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateQuotePhaseCommand($quoteId, $phaseId, $req->name, $req->required_role, $req->duration_days, $req->daily_rate_amount, $req->daily_rate_currency));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

For `SendQuoteController`, `AcceptQuoteController`, `RejectQuoteController` — all follow same pattern (POST, no request body):
```php
// SendQuoteController — route: /api/sales/inquiries/{inquiryId}/quotes/{quoteId}/commands/send
// dispatches: new SendQuoteCommand($quoteId), returns 204

// AcceptQuoteController — route: .../commands/accept
// dispatches: new AcceptQuoteCommand($quoteId), returns 204

// RejectQuoteController — route: .../commands/reject
// dispatches: new RejectQuoteCommand($quoteId), returns 204
```

Create all three following the same pattern as `AdvanceInquiryStatusController` but without request body (use `#[Route(..., methods: ['POST'])]`, inject `CommandBusInterface`, dispatch respective command, return 204).

`packages/sales/src/Quote/Infrastructure/Http/ExportQuotePdfController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\ExportQuotePdf\ExportQuotePdfCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/commands/export-pdf', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class ExportQuotePdfController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}
    public function __invoke(string $inquiryId, string $quoteId): JsonResponse
    {
        $this->commandBus->dispatch(new ExportQuotePdfCommand($quoteId));
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

`packages/sales/src/Quote/Infrastructure/Http/GetQuotePdfController.php`:
```php
<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http;
use Sales\Infrastructure\Security\SalesPermission;
use Sales\Quote\Application\GetQuoteDetail\{GetQuoteDetailHandler, GetQuoteDetailQuery};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{inquiryId}/quotes/{quoteId}/pdf', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageQuotes->value)]
final class GetQuotePdfController extends AbstractController
{
    public function __construct(private readonly GetQuoteDetailHandler $handler) {}
    public function __invoke(string $inquiryId, string $quoteId): Response
    {
        $dto = ($this->handler)(new GetQuoteDetailQuery($quoteId));
        if ($dto->pdfPath === null || !file_exists($dto->pdfPath)) {
            return new Response(json_encode(['error' => 'PDF not generated yet. Call export-pdf first.']), 404, ['Content-Type' => 'application/json']);
        }
        return new BinaryFileResponse($dto->pdfPath);
    }
}
```

- [ ] **Step 4: Verify container**

```bash
php bin/console cache:clear && php bin/console debug:router | grep sales
```

Expected: all sales routes listed.

- [ ] **Step 5: Commit**

```bash
git add packages/sales/src/Quote/Infrastructure/ packages/sales/src/Infrastructure/Security/
git commit -m "feat(sales): add Quote infrastructure — DBAL repository and HTTP controllers"
```

---

## Task 12: Cross-BC integration — QuoteAccepted → Planning Order

**Files:**
- Create: `packages/planning/src/Order/Application/CreateOrderFromQuote/CreateOrderFromQuoteHandler.php`
- Modify: `config/packages/messenger.yaml`

- [ ] **Step 1: Create `CreateOrderFromQuoteHandler.php` in Planning**

```php
<?php
declare(strict_types=1);

namespace Planning\Order\Application\CreateOrderFromQuote;

use Planning\Order\Application\AddPhase\AddPhaseCommand;
use Planning\Order\Application\AddPhase\AddPhaseHandler;
use Planning\Order\Application\CreateOrder\CreateOrderCommand;
use Planning\Order\Application\CreateOrder\CreateOrderHandler;
use Planning\Order\Domain\OrderId;
use Planning\Order\Domain\PhaseId;
use Sales\Quote\Domain\QuoteAccepted;
use Sales\Quote\Domain\QuoteRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class CreateOrderFromQuoteHandler
{
    public function __construct(
        private readonly QuoteRepository    $quoteRepository,
        private readonly CreateOrderHandler $createOrderHandler,
        private readonly AddPhaseHandler    $addPhaseHandler,
    ) {}

    public function __invoke(QuoteAccepted $event): void
    {
        $quote  = $this->quoteRepository->get($event->quoteId);
        $detail = $this->buildDetail($quote);

        $orderId = OrderId::generate()->value();

        ($this->createOrderHandler)(new CreateOrderCommand(
            orderId: $orderId,
            name: 'Zakázka z nabídky ' . $event->quoteId->value(),
            clientName: '',   // will be populated from Inquiry if needed
            plannedStartDate: date('Y-m-d'),
        ));

        foreach ($quote->phases() as $phase) {
            ($this->addPhaseHandler)(new AddPhaseCommand(
                orderId: $orderId,
                phaseId: PhaseId::generate()->value(),
                name: $phase->name(),
                requiredRole: $phase->requiredRole()->value,
                requiredSkills: [],
                headcount: 1,
                durationDays: $phase->durationDays(),
                dependsOn: [],
            ));
        }
    }
}
```

Note: `AddPhaseCommand` and `AddPhaseHandler` are already in the planning package. Check `packages/planning/src/Order/Application/AddPhase/` for exact constructor signature before writing this handler, and adjust accordingly.

- [ ] **Step 2: Register Planning→Sales dependency in planning composer.json**

Add to `packages/planning/composer.json` repositories:
```json
{"type": "path", "url": "../sales", "options": {"symlink": true}}
```

Add to `require`:
```json
"ddd-erp/sales": "*"
```

Then run:
```bash
cd packages/planning && composer update ddd-erp/sales && cd ../..
```

- [ ] **Step 3: Register event handler in `config/packages/messenger.yaml`**

Check existing `config/packages/messenger.yaml` structure and add under the event bus routing:

```yaml
Sales\Quote\Domain\QuoteAccepted:
    - Planning\Order\Application\CreateOrderFromQuote\CreateOrderFromQuoteHandler
```

- [ ] **Step 4: Add handler to services.yaml**

The `Planning\:` wildcard in services.yaml already covers this handler. Verify:

```bash
php bin/console cache:clear && php bin/console debug:container | grep CreateOrderFromQuote
```

Expected: service listed.

- [ ] **Step 5: Commit**

```bash
git add packages/planning/src/Order/Application/CreateOrderFromQuote/ packages/planning/composer.json config/packages/messenger.yaml
git commit -m "feat(planning): add CreateOrderFromQuoteHandler to listen to QuoteAccepted event"
```

---

## Task 13: Database migration

**Files:**
- Create: `migrations/Version{timestamp}.php`

- [ ] **Step 1: Generate migration**

```bash
php bin/console doctrine:migrations:diff
```

Expected: generates a new `migrations/Version{timestamp}.php` file.

- [ ] **Step 2: Review and edit the generated migration**

Open the generated file and ensure it contains the following SQL (edit if needed):

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE sales_inquiries (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        customer_id VARCHAR(36) DEFAULT NULL,
        customer_name VARCHAR(255) NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        requested_deadline DATE DEFAULT NULL,
        required_roles JSON NOT NULL DEFAULT \'[]\',
        status VARCHAR(20) NOT NULL DEFAULT \'new\',
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )');
    $this->addSql('CREATE TABLE sales_inquiry_attachments (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        inquiry_id VARCHAR(36) NOT NULL REFERENCES sales_inquiries(id) ON DELETE CASCADE,
        path VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )');
    $this->addSql('CREATE INDEX idx_sales_inquiry_attachments_inquiry ON sales_inquiry_attachments (inquiry_id)');
    $this->addSql('CREATE TABLE sales_quotes (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        inquiry_id VARCHAR(36) NOT NULL REFERENCES sales_inquiries(id) ON DELETE CASCADE,
        valid_until DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'draft\',
        pdf_path VARCHAR(500) DEFAULT NULL,
        notes TEXT NOT NULL DEFAULT \'\',
        total_price_amount INT NOT NULL DEFAULT 0,
        total_price_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )');
    $this->addSql('CREATE INDEX idx_sales_quotes_inquiry ON sales_quotes (inquiry_id)');
    $this->addSql('CREATE TABLE sales_quote_phases (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        quote_id VARCHAR(36) NOT NULL REFERENCES sales_quotes(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        required_role VARCHAR(50) NOT NULL,
        duration_days INT NOT NULL,
        daily_rate_amount INT NOT NULL,
        daily_rate_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
        subtotal_amount INT NOT NULL,
        subtotal_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
        sort_order INT NOT NULL DEFAULT 0
    )');
    $this->addSql('CREATE INDEX idx_sales_quote_phases_quote ON sales_quote_phases (quote_id)');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE sales_quote_phases');
    $this->addSql('DROP TABLE sales_quotes');
    $this->addSql('DROP TABLE sales_inquiry_attachments');
    $this->addSql('DROP TABLE sales_inquiries');
}
```

- [ ] **Step 3: Run migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: migration applied, "1 migration executed".

- [ ] **Step 4: Commit**

```bash
git add migrations/
git commit -m "feat(sales): add database migration for sales tables"
```

---

## Task 14: Frontend — sales.ts API client

**Files:**
- Create: `frontend/src/app/api/sales.ts`

- [ ] **Step 1: Create `sales.ts`**

```typescript
import { apiGet, apiPost, apiPut } from './client'

export type SalesRole = 'designer' | 'frontend' | 'backend' | 'pm' | 'qa' | 'devops'

export const SALES_ROLE_LABELS: Record<SalesRole, string> = {
  designer: 'Designér',
  frontend: 'Frontend',
  backend: 'Backend',
  pm: 'PM',
  qa: 'QA',
  devops: 'DevOps',
}

export type InquiryStatus = 'new' | 'in_progress' | 'quoted' | 'won' | 'lost' | 'cancelled'

export const INQUIRY_STATUS_LABELS: Record<InquiryStatus, string> = {
  new: 'Nová',
  in_progress: 'Zpracovává se',
  quoted: 'Nabídnuto',
  won: 'Vyhráno',
  lost: 'Prohráno',
  cancelled: 'Zrušeno',
}

export type QuoteStatus = 'draft' | 'sent' | 'accepted' | 'rejected'

export const QUOTE_STATUS_LABELS: Record<QuoteStatus, string> = {
  draft: 'Rozpracována',
  sent: 'Odeslaná',
  accepted: 'Přijatá',
  rejected: 'Odmítnuta',
}

// ---- Inquiry ----

export interface RequiredRoleDTO {
  role: SalesRole
  skills: string[]
}

export interface AttachmentDTO {
  id: string
  path: string
  mimeType: string
  originalName: string
}

export interface InquiryListItem {
  id: string
  customer_name: string
  description: string
  status: InquiryStatus
  requested_deadline: string | null
  created_at: string
}

export interface InquiryDetail {
  id: string
  customer_id: string | null
  customer_name: string
  contact_email: string
  description: string
  requested_deadline: string | null
  required_roles: RequiredRoleDTO[]
  attachments: AttachmentDTO[]
  status: InquiryStatus
  created_at: string
}

// ---- Quote ----

export interface QuotePhaseDetail {
  id: string
  name: string
  required_role: SalesRole
  duration_days: number
  daily_rate_amount: number
  daily_rate_currency: string
  subtotal_amount: number
  subtotal_currency: string
}

export interface QuoteDetail {
  id: string
  inquiry_id: string
  valid_until: string
  status: QuoteStatus
  pdf_path: string | null
  notes: string
  phases: QuotePhaseDetail[]
  total_price_amount: number
  total_price_currency: string
}

// ---- API ----

export const salesApi = {
  // Inquiries
  getInquiries: (status?: string) =>
    apiGet<InquiryListItem[]>(`/api/sales/inquiries${status ? `?status=${status}` : ''}`),

  getInquiry: (id: string) =>
    apiGet<InquiryDetail>(`/api/sales/inquiries/${id}`),

  createInquiry: (data: {
    customer_name: string
    contact_email: string
    description: string
    customer_id?: string
    requested_deadline?: string
    required_roles: RequiredRoleDTO[]
  }) => apiPost<{ id: string }>('/api/sales/inquiries', data),

  updateInquiry: (id: string, data: {
    customer_name: string
    contact_email: string
    description: string
    customer_id?: string
    requested_deadline?: string
    required_roles: RequiredRoleDTO[]
  }) => apiPut<void>(`/api/sales/inquiries/${id}`, data),

  advanceInquiryStatus: (id: string, targetStatus?: string) =>
    apiPost<void>(`/api/sales/inquiries/${id}/commands/advance-status`, { target_status: targetStatus ?? null }),

  uploadAttachment: async (inquiryId: string, file: File): Promise<{ path: string }> => {
    const { useAuthStore } = await import('../auth/authStore')
    const store = useAuthStore.getState()
    const formData = new FormData()
    formData.append('file', file)
    const res = await fetch(`/api/sales/inquiries/${inquiryId}/attachments`, {
      method: 'POST',
      headers: store.accessToken ? { Authorization: `Bearer ${store.accessToken}` } : {},
      body: formData,
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return res.json()
  },

  getAttachmentUrl: (path: string) => `/api/sales/attachments/${path}`,

  // Quotes
  getQuote: (inquiryId: string, quoteId: string) =>
    apiGet<QuoteDetail>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}`),

  createQuote: (inquiryId: string, data: { valid_until: string; notes: string }) =>
    apiPost<{ id: string }>(`/api/sales/inquiries/${inquiryId}/quotes`, data),

  addQuotePhase: (inquiryId: string, quoteId: string, data: {
    name: string; required_role: SalesRole; duration_days: number
    daily_rate_amount: number; daily_rate_currency: string
  }) => apiPost<{ id: string }>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/phases`, data),

  updateQuotePhase: (inquiryId: string, quoteId: string, phaseId: string, data: {
    name: string; required_role: SalesRole; duration_days: number
    daily_rate_amount: number; daily_rate_currency: string
  }) => apiPut<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/phases/${phaseId}`, data),

  sendQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/send`, {}),

  acceptQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/accept`, {}),

  rejectQuote: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/reject`, {}),

  exportQuotePdf: (inquiryId: string, quoteId: string) =>
    apiPost<void>(`/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/commands/export-pdf`, {}),

  getQuotePdfUrl: (inquiryId: string, quoteId: string) =>
    `/api/sales/inquiries/${inquiryId}/quotes/${quoteId}/pdf`,
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/app/api/sales.ts
git commit -m "feat(sales): add sales API client (TypeScript)"
```

---

## Task 15: Frontend — InquiriesPage + InquiryDetailPage + QuoteDetailPage

**Files:**
- Create: `frontend/src/app/modules/sales/InquiriesPage.tsx` + `.module.css`
- Create: `frontend/src/app/modules/sales/InquiryDetailPage.tsx` + `.module.css`
- Create: `frontend/src/app/modules/sales/QuoteDetailPage.tsx` + `.module.css`
- Modify: `frontend/src/app/router.tsx`
- Modify: `frontend/src/app/components/AppLayout/AppLayout.tsx`

- [ ] **Step 1: Create `InquiriesPage.tsx`**

```tsx
import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Badge, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { salesApi, INQUIRY_STATUS_LABELS, type InquiryListItem } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './InquiriesPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  new: 'primary', in_progress: 'primary', quoted: 'primary',
  won: 'success', lost: 'danger', cancelled: 'danger',
}

export function InquiriesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [customerName, setCustomerName] = useState('')
  const [contactEmail, setContactEmail] = useState('')
  const [description, setDescription] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['sales-inquiries'],
    queryFn: () => salesApi.getInquiries(),
  })

  const createMutation = useMutation({
    mutationFn: () => salesApi.createInquiry({ customer_name: customerName, contact_email: contactEmail, description, required_roles: [] }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['sales-inquiries'] }); handleClose() },
  })

  const handleClose = () => { setOpen(false); setCustomerName(''); setContactEmail(''); setDescription(''); createMutation.reset() }

  const columns: Column<InquiryListItem>[] = [
    { key: 'customer_name', header: 'Zákazník', render: r => r.customer_name },
    { key: 'description', header: 'Popis', render: r => r.description.length > 60 ? r.description.slice(0, 60) + '…' : r.description },
    { key: 'status', header: 'Stav', render: r => <Badge label={INQUIRY_STATUS_LABELS[r.status] ?? r.status} variant={STATUS_VARIANTS[r.status] ?? 'primary'} /> },
    { key: 'created_at', header: 'Vytvořeno', render: r => new Date(r.created_at).toLocaleDateString('cs-CZ') },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Poptávky</h1>
          <Button onClick={() => setOpen(true)}>Nová poptávka</Button>
        </div>
        <Table columns={columns} data={data as (InquiryListItem & Record<string, unknown>)[]}
          loading={isLoading} rowKey={r => r.id}
          onRowClick={async r => navigate({ to: '/sales/inquiries/$inquiryId', params: { inquiryId: r.id } })} />
        <Modal open={open} onClose={handleClose} title="Nová poptávka">
          <form className={styles.form} onSubmit={e => { e.preventDefault(); createMutation.mutate() }}>
            {createMutation.isError && <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>Nepodařilo se vytvořit poptávku</p>}
            <FormField label="Zákazník" htmlFor="custName"><Input id="custName" value={customerName} onChange={e => setCustomerName(e.target.value)} placeholder="Název firmy" /></FormField>
            <FormField label="E-mail" htmlFor="custEmail"><Input id="custEmail" type="email" value={contactEmail} onChange={e => setContactEmail(e.target.value)} /></FormField>
            <FormField label="Popis" htmlFor="descr"><Input id="descr" value={description} onChange={e => setDescription(e.target.value)} /></FormField>
            <div className={styles.actions}>
              <Button variant="secondary" type="button" onClick={handleClose}>Zrušit</Button>
              <Button type="submit" loading={createMutation.isPending} disabled={!customerName.trim() || !contactEmail.trim() || !description.trim()}>Vytvořit</Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
```

`InquiriesPage.module.css`:
```css
.page { padding: var(--spacing-6); }
.header { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--spacing-4); }
.title { font-size: var(--font-size-xl); font-weight: 600; }
.form { display: flex; flex-direction: column; gap: var(--spacing-4); }
.actions { display: flex; justify-content: flex-end; gap: var(--spacing-2); }
```

- [ ] **Step 2: Create `InquiryDetailPage.tsx`**

```tsx
import { useRef, useState } from 'react'
import { useParams, useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, Badge, Modal, FormField, Input } from '../../../design-system'
import { salesApi, INQUIRY_STATUS_LABELS, QUOTE_STATUS_LABELS } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './InquiryDetailPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  new: 'primary', in_progress: 'primary', quoted: 'primary',
  won: 'success', lost: 'danger', cancelled: 'danger',
}

export function InquiryDetailPage() {
  const { inquiryId } = useParams({ from: '/sales/inquiries/$inquiryId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [quoteOpen, setQuoteOpen] = useState(false)
  const [validUntil, setValidUntil] = useState('')
  const [notes, setNotes] = useState('')

  const { data: inquiry, isLoading } = useQuery({
    queryKey: ['sales-inquiry', inquiryId],
    queryFn: () => salesApi.getInquiry(inquiryId),
  })

  const advanceMutation = useMutation({
    mutationFn: (target?: string) => salesApi.advanceInquiryStatus(inquiryId, target),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-inquiry', inquiryId] }),
  })

  const uploadMutation = useMutation({
    mutationFn: (file: File) => salesApi.uploadAttachment(inquiryId, file),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-inquiry', inquiryId] }),
  })

  const createQuoteMutation = useMutation({
    mutationFn: () => salesApi.createQuote(inquiryId, { valid_until: validUntil, notes }),
    onSuccess: async data => {
      setQuoteOpen(false)
      await navigate({ to: '/sales/inquiries/$inquiryId/quotes/$quoteId', params: { inquiryId, quoteId: data.id } })
    },
  })

  if (isLoading || !inquiry) return <AppLayout><div style={{ padding: 32 }}>Načítám…</div></AppLayout>

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.breadcrumb}>
          <button className={styles.back} onClick={() => navigate({ to: '/sales/inquiries' })}>← Poptávky</button>
          <span> › {inquiry.customer_name}</span>
        </div>
        <div className={styles.header}>
          <h1 className={styles.title}>{inquiry.customer_name}</h1>
          <Badge label={INQUIRY_STATUS_LABELS[inquiry.status] ?? inquiry.status} variant={STATUS_VARIANTS[inquiry.status] ?? 'primary'} />
          {['new','in_progress'].includes(inquiry.status) && (
            <Button size="sm" onClick={() => advanceMutation.mutate(undefined)} loading={advanceMutation.isPending}>Posunout stav</Button>
          )}
        </div>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Požadavky</h2>
          <p className={styles.label}>E-mail: <span>{inquiry.contact_email}</span></p>
          <p className={styles.label}>Popis: <span>{inquiry.description}</span></p>
          {inquiry.requested_deadline && <p className={styles.label}>Termín: <span>{inquiry.requested_deadline}</span></p>}
          {inquiry.required_roles.length > 0 && (
            <div className={styles.tags}>
              {inquiry.required_roles.map((r, i) => (
                <span key={i} className={styles.tag}>{r.role}{r.skills.length > 0 ? ` (${r.skills.join(', ')})` : ''}</span>
              ))}
            </div>
          )}
        </section>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Přílohy</h2>
            <Button size="sm" variant="secondary" onClick={() => fileInputRef.current?.click()}>Nahrát</Button>
            <input ref={fileInputRef} type="file" hidden accept=".pdf,.png,.jpg,.jpeg,.webp"
              onChange={e => { const f = e.target.files?.[0]; if (f) uploadMutation.mutate(f) }} />
          </div>
          {inquiry.attachments.length === 0 && <p className={styles.empty}>Žádné přílohy</p>}
          {inquiry.attachments.map(a => (
            <div key={a.id} className={styles.attachment}>
              <a href={salesApi.getAttachmentUrl(a.path)} target="_blank" rel="noreferrer">{a.originalName}</a>
              {a.mimeType.startsWith('image/') && (
                <img src={salesApi.getAttachmentUrl(a.path)} alt={a.originalName} className={styles.preview} />
              )}
              {a.mimeType === 'application/pdf' && (
                <iframe src={salesApi.getAttachmentUrl(a.path)} title={a.originalName} className={styles.pdfPreview} />
              )}
            </div>
          ))}
        </section>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Nabídky</h2>
            {inquiry.status !== 'won' && inquiry.status !== 'lost' && inquiry.status !== 'cancelled' && (
              <Button size="sm" onClick={() => setQuoteOpen(true)}>Vytvořit nabídku</Button>
            )}
          </div>
          <p className={styles.empty}>Pro zobrazení nabídek klikněte na detail z odkazu v e-mailu nebo přejděte přímo na URL nabídky.</p>
        </section>
      </div>

      <Modal open={quoteOpen} onClose={() => setQuoteOpen(false)} title="Vytvořit nabídku">
        <form className={styles.form} onSubmit={e => { e.preventDefault(); createQuoteMutation.mutate() }}>
          <FormField label="Platná do" htmlFor="validUntil"><Input id="validUntil" type="date" value={validUntil} onChange={e => setValidUntil(e.target.value)} /></FormField>
          <FormField label="Poznámky" htmlFor="qnotes"><Input id="qnotes" value={notes} onChange={e => setNotes(e.target.value)} /></FormField>
          <div className={styles.actions}>
            <Button variant="secondary" type="button" onClick={() => setQuoteOpen(false)}>Zrušit</Button>
            <Button type="submit" loading={createQuoteMutation.isPending} disabled={!validUntil}>Vytvořit</Button>
          </div>
        </form>
      </Modal>
    </AppLayout>
  )
}
```

`InquiryDetailPage.module.css`:
```css
.page { padding: var(--spacing-6); max-width: 900px; }
.breadcrumb { font-size: var(--font-size-sm); color: var(--color-neutral-500); margin-bottom: var(--spacing-4); }
.back { background: none; border: none; cursor: pointer; color: var(--color-primary-600); font-size: var(--font-size-sm); padding: 0; }
.header { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-6); }
.title { font-size: var(--font-size-xl); font-weight: 600; }
.section { margin-bottom: var(--spacing-6); }
.sectionHeader { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-3); }
.sectionTitle { font-size: var(--font-size-lg); font-weight: 500; }
.label { font-size: var(--font-size-sm); color: var(--color-neutral-600); margin-bottom: var(--spacing-1); }
.label span { color: var(--color-neutral-900); }
.tags { display: flex; flex-wrap: wrap; gap: var(--spacing-2); margin-top: var(--spacing-2); }
.tag { background: var(--color-neutral-100); padding: 2px 8px; border-radius: 4px; font-size: var(--font-size-xs); }
.empty { color: var(--color-neutral-400); font-size: var(--font-size-sm); }
.attachment { margin-bottom: var(--spacing-3); }
.preview { max-width: 200px; max-height: 150px; display: block; margin-top: var(--spacing-1); border: 1px solid var(--color-neutral-200); border-radius: 4px; }
.pdfPreview { width: 100%; height: 400px; border: 1px solid var(--color-neutral-200); border-radius: 4px; margin-top: var(--spacing-1); }
.form { display: flex; flex-direction: column; gap: var(--spacing-4); }
.actions { display: flex; justify-content: flex-end; gap: var(--spacing-2); }
```

- [ ] **Step 3: Create `QuoteDetailPage.tsx`**

```tsx
import { useState } from 'react'
import { useParams, useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, Badge, Modal, FormField, Input } from '../../../design-system'
import { salesApi, QUOTE_STATUS_LABELS, SALES_ROLE_LABELS, type SalesRole } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './QuoteDetailPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  draft: 'primary', sent: 'primary', accepted: 'success', rejected: 'danger',
}

export function QuoteDetailPage() {
  const { inquiryId, quoteId } = useParams({ from: '/sales/inquiries/$inquiryId/quotes/$quoteId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [addPhaseOpen, setAddPhaseOpen] = useState(false)
  const [phaseName, setPhaseName] = useState('')
  const [phaseRole, setPhaseRole] = useState<SalesRole>('backend')
  const [phaseDays, setPhaseDays] = useState(1)
  const [phaseDailyRate, setPhaseDailyRate] = useState(0)

  const { data: quote, isLoading } = useQuery({
    queryKey: ['sales-quote', quoteId],
    queryFn: () => salesApi.getQuote(inquiryId, quoteId),
  })

  const addPhaseMutation = useMutation({
    mutationFn: () => salesApi.addQuotePhase(inquiryId, quoteId, {
      name: phaseName, required_role: phaseRole,
      duration_days: phaseDays, daily_rate_amount: phaseDailyRate * 100, daily_rate_currency: 'CZK',
    }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }); setAddPhaseOpen(false); setPhaseName(''); setPhaseDays(1); setPhaseDailyRate(0) },
  })

  const sendMutation = useMutation({
    mutationFn: () => salesApi.sendQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const acceptMutation = useMutation({
    mutationFn: () => salesApi.acceptQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const rejectMutation = useMutation({
    mutationFn: () => salesApi.rejectQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const exportPdfMutation = useMutation({
    mutationFn: () => salesApi.exportQuotePdf(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })

  if (isLoading || !quote) return <AppLayout><div style={{ padding: 32 }}>Načítám…</div></AppLayout>

  const totalCzk = (quote.total_price_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.breadcrumb}>
          <button className={styles.back} onClick={() => navigate({ to: '/sales/inquiries/$inquiryId', params: { inquiryId } })}>← Poptávka</button>
          <span> › Nabídka</span>
        </div>
        <div className={styles.header}>
          <h1 className={styles.title}>Nabídka</h1>
          <Badge label={QUOTE_STATUS_LABELS[quote.status] ?? quote.status} variant={STATUS_VARIANTS[quote.status] ?? 'primary'} />
          <div className={styles.actions}>
            {quote.status === 'draft' && <Button size="sm" onClick={() => sendMutation.mutate()} loading={sendMutation.isPending}>Odeslat zákazníkovi</Button>}
            {quote.status === 'sent' && <Button size="sm" onClick={() => acceptMutation.mutate()} loading={acceptMutation.isPending}>Přijmout</Button>}
            {quote.status === 'sent' && <Button size="sm" variant="secondary" onClick={() => rejectMutation.mutate()} loading={rejectMutation.isPending}>Odmítnout</Button>}
            <Button size="sm" variant="secondary" onClick={() => exportPdfMutation.mutate()} loading={exportPdfMutation.isPending}>Generovat PDF</Button>
            {quote.pdf_path && <a href={salesApi.getQuotePdfUrl(inquiryId, quoteId)} target="_blank" rel="noreferrer"><Button size="sm" variant="secondary">Stáhnout PDF</Button></a>}
          </div>
        </div>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Fáze nabídky</h2>
            {quote.status === 'draft' && <Button size="sm" onClick={() => setAddPhaseOpen(true)}>Přidat fázi</Button>}
          </div>
          {quote.phases.length === 0 && <p className={styles.empty}>Žádné fáze. Přidejte první fázi.</p>}
          <table className={styles.table}>
            <thead><tr><th>Název</th><th>Role</th><th>Dny</th><th>Sazba/den</th><th>Mezisoučet</th></tr></thead>
            <tbody>
              {quote.phases.map(p => (
                <tr key={p.id}>
                  <td>{p.name}</td>
                  <td>{SALES_ROLE_LABELS[p.required_role as SalesRole] ?? p.required_role}</td>
                  <td>{p.duration_days}</td>
                  <td>{(p.daily_rate_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })} {p.daily_rate_currency}</td>
                  <td>{(p.subtotal_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })} {p.subtotal_currency}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className={styles.total}>Celkem: <strong>{totalCzk} {quote.total_price_currency}</strong></div>
        </section>

        {quote.notes && (
          <section className={styles.section}>
            <h2 className={styles.sectionTitle}>Poznámky</h2>
            <p>{quote.notes}</p>
          </section>
        )}
      </div>

      <Modal open={addPhaseOpen} onClose={() => setAddPhaseOpen(false)} title="Přidat fázi">
        <form className={styles.form} onSubmit={e => { e.preventDefault(); addPhaseMutation.mutate() }}>
          <FormField label="Název fáze" htmlFor="pname"><Input id="pname" value={phaseName} onChange={e => setPhaseName(e.target.value)} /></FormField>
          <FormField label="Role" htmlFor="prole">
            <select id="prole" value={phaseRole} onChange={e => setPhaseRole(e.target.value as SalesRole)} className={styles.select}>
              {Object.entries(SALES_ROLE_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Počet dní" htmlFor="pdays"><Input id="pdays" type="number" min="1" value={phaseDays} onChange={e => setPhaseDays(Number(e.target.value))} /></FormField>
          <FormField label="Sazba / den (CZK)" htmlFor="prate"><Input id="prate" type="number" min="0" step="0.01" value={phaseDailyRate} onChange={e => setPhaseDailyRate(Number(e.target.value))} /></FormField>
          <div className={styles.actions}>
            <Button variant="secondary" type="button" onClick={() => setAddPhaseOpen(false)}>Zrušit</Button>
            <Button type="submit" loading={addPhaseMutation.isPending} disabled={!phaseName.trim()}>Přidat</Button>
          </div>
        </form>
      </Modal>
    </AppLayout>
  )
}
```

`QuoteDetailPage.module.css`:
```css
.page { padding: var(--spacing-6); max-width: 1000px; }
.breadcrumb { font-size: var(--font-size-sm); color: var(--color-neutral-500); margin-bottom: var(--spacing-4); }
.back { background: none; border: none; cursor: pointer; color: var(--color-primary-600); font-size: var(--font-size-sm); padding: 0; }
.header { display: flex; align-items: center; gap: var(--spacing-3); flex-wrap: wrap; margin-bottom: var(--spacing-6); }
.title { font-size: var(--font-size-xl); font-weight: 600; }
.actions { display: flex; gap: var(--spacing-2); margin-left: auto; }
.section { margin-bottom: var(--spacing-6); }
.sectionHeader { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-3); }
.sectionTitle { font-size: var(--font-size-lg); font-weight: 500; }
.table { width: 100%; border-collapse: collapse; font-size: var(--font-size-sm); }
.table th, .table td { border: 1px solid var(--color-neutral-200); padding: var(--spacing-2) var(--spacing-3); text-align: left; }
.table th { background: var(--color-neutral-50); font-weight: 500; }
.total { text-align: right; margin-top: var(--spacing-3); font-size: var(--font-size-sm); }
.empty { color: var(--color-neutral-400); font-size: var(--font-size-sm); }
.form { display: flex; flex-direction: column; gap: var(--spacing-4); }
.actions-form { display: flex; justify-content: flex-end; gap: var(--spacing-2); }
.select { width: 100%; padding: var(--spacing-2); border: 1px solid var(--color-neutral-300); border-radius: 4px; font-size: var(--font-size-sm); }
```

- [ ] **Step 4: Update `router.tsx`**

Add imports at top:
```tsx
import { InquiriesPage } from './modules/sales/InquiriesPage'
import { InquiryDetailPage } from './modules/sales/InquiryDetailPage'
import { QuoteDetailPage } from './modules/sales/QuoteDetailPage'
```

Add route definitions (before `const routeTree`):
```tsx
const salesInquiriesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/sales/inquiries',
  beforeLoad: () => requirePermission('sales.inquiries.manage'),
  component: InquiriesPage,
})

const salesInquiryDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/sales/inquiries/$inquiryId',
  beforeLoad: () => requirePermission('sales.inquiries.manage'),
  component: InquiryDetailPage,
})

const salesQuoteDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/sales/inquiries/$inquiryId/quotes/$quoteId',
  beforeLoad: () => requirePermission('sales.quotes.manage'),
  component: QuoteDetailPage,
})
```

Add to `routeTree`:
```tsx
const routeTree = rootRoute.addChildren([
  // ... existing routes ...
  salesInquiriesRoute,
  salesInquiryDetailRoute,
  salesQuoteDetailRoute,
])
```

- [ ] **Step 5: Update `AppLayout.tsx` — add sidebar section**

Add "Obchod" section before the CRM section:

```tsx
{hasPermission('sales.inquiries.manage') && (
  <div className={styles.section}>
    <p className={styles.sectionLabel}>Obchod</p>
    <Link
      to="/sales/inquiries"
      activeProps={{ className: `${styles.link} ${styles.linkActive}` }}
      inactiveProps={{ className: styles.link }}
    >
      Poptávky
    </Link>
  </div>
)}
```

- [ ] **Step 6: Build and verify**

```bash
cd frontend && npm run build
```

Expected: build succeeds with no TypeScript errors.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/app/modules/sales/ frontend/src/app/router.tsx frontend/src/app/components/AppLayout/
git commit -m "feat(sales): add frontend pages — InquiriesPage, InquiryDetailPage, QuoteDetailPage"
```

---

## Task 16: Add `sales.inquiries.manage` and `sales.quotes.manage` permissions to admin role

**Files:**
- Check existing role/permission seeding mechanism and add permissions

- [ ] **Step 1: Find how permissions are assigned to roles**

```bash
grep -r "sales\|planning.orders" /home/michal/ddd-erp/migrations/ | head -10
```

Look for a migration or seed that adds role permissions. Follow the same pattern to add:
- `ROLE_SALES_INQUIRIES_MANAGE`
- `ROLE_SALES_QUOTES_MANAGE`

to the admin role.

- [ ] **Step 2: Create migration for permissions**

Generate or create manually a migration that inserts these permissions into the role/permission table. Find the exact table and column names from existing permission migrations.

- [ ] **Step 3: Run migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 4: Commit**

```bash
git add migrations/
git commit -m "feat(sales): add sales permissions to admin role"
```

---

## Task 17: End-to-end smoke test

- [ ] **Step 1: Start the app**

```bash
docker-compose up -d
```

- [ ] **Step 2: Run all unit and application tests**

```bash
vendor/bin/phpunit packages/sales/tests/ --testdox
```

Expected: all tests pass.

- [ ] **Step 3: Run integration tests (if applicable)**

If controller tests have been added to `phpunit.xml`, run:
```bash
vendor/bin/phpunit --testsuite Integration
```

- [ ] **Step 4: Manual smoke test via browser**

1. Login → verify "Obchod" section appears in sidebar
2. Navigate to Poptávky → create a new inquiry
3. Open inquiry detail → upload a PDF attachment → verify preview
4. Create a quote → add 2 phases → verify total price computed correctly
5. Send quote → accept → verify inquiry status becomes "Vyhráno"
6. Navigate to Planning > Zakázky → verify a new Order was created

- [ ] **Step 5: Final commit**

```bash
git add -p  # stage any remaining changes
git commit -m "feat(sales): complete Sales module — Inquiries, Quotes, Planning integration"
```
