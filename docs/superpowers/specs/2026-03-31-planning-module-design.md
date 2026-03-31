# Planning Module Design

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Resource planning for software orders — orders have sequenced phases (DAG), each phase requires a role and skills, the system suggests available workers and tracks capacity allocations.

**Architecture:** New DDD package `packages/planning/` with two aggregates: `Order` (with phases as DAG) and `Worker` (wrapping Identity UserId with role/skills/allocations). A suggestion engine computes candidate workers per phase based on role match and available capacity. New frontend module mirrors the identity/crm pattern.

**Tech Stack:** PHP 8.4, Symfony 7, Doctrine ORM, PostgreSQL, React 19, TanStack Router v1, TanStack Query v5, CSS Modules

---

## Domain Model

### Order (aggregate root)

- `OrderId` — UUID
- `name: string`
- `clientName: string`
- `plannedStartDate: DateTimeImmutable`
- `status: OrderStatus` — enum: `new | confirmed | in_progress | completed | shipped`
- `phases: Phase[]` — ordered collection of phase entities

### Phase (entity, owned by Order)

- `PhaseId` — UUID
- `name: string`
- `requiredRole: WorkerRole` — enum value
- `requiredSkills: string[]` — optional skill tags (e.g. "React", "Node.js")
- `headcount: int` — number of workers needed (≥ 1)
- `durationDays: int`
- `dependsOn: PhaseId[]` — direct predecessors in DAG
- `startDate: ?DateTimeImmutable` — computed on planning
- `endDate: ?DateTimeImmutable` — computed on planning
- `assignments: Assignment[]`

### Assignment (value object)

- `userId: UserId` — references Identity user
- `allocationPercent: int` — 1–100

### Worker (aggregate root)

- `WorkerId` = `UserId` from Identity (same UUID)
- `primaryRole: WorkerRole`
- `skills: string[]`
- `allocations: WorkerAllocation[]`

### WorkerAllocation (value object)

- `orderId: OrderId`
- `phaseId: PhaseId`
- `allocationPercent: int`
- `startDate: DateTimeImmutable`
- `endDate: DateTimeImmutable`

### WorkerRole (enum)

Values: `designer`, `frontend`, `backend`, `pm`, `qa`, `devops`

---

## Planning & Scheduling Logic

### Schedule computation

When planning an order, phases are topologically sorted by their `dependsOn` DAG. Each phase's `startDate = max(endDate of all predecessor phases)`. Phases with no predecessors start at `order.plannedStartDate`. `endDate = startDate + durationDays`.

If a cycle is detected in `dependsOn`, the command is rejected with a domain exception.

### Candidate suggestion

For a given phase in its computed `[startDate, endDate]` window, the system finds workers where:

1. `worker.primaryRole == phase.requiredRole` OR `worker.skills` intersects `phase.requiredSkills` (at least one match)
2. Sum of `worker.allocations` overlapping the phase window < 100%

Results are sorted descending by available capacity (most free first). Top candidates up to `phase.headcount * 3` are returned (so the manager has options).

### Assignment confirmation

Manager selects one worker per slot. On confirmation:
- `Assignment` is added to the phase
- `WorkerAllocation` is added to the worker aggregate
- System validates total allocation does not exceed 100% for the worker in that window; if exceeded, command is rejected

### Re-planning

When phase dependencies or durations change, the schedule is recomputed. Existing confirmed assignments are preserved but the system emits a `PhaseConflictDetected` domain event for each assignment where the worker's new allocation window would exceed 100%.

---

## Backend Architecture

### Package structure

```
packages/planning/
  composer.json
  src/
    Order/
      Domain/
        Order.php
        Phase.php
        Assignment.php
        OrderId.php
        PhaseId.php
        OrderStatus.php          — enum
        OrderRepository.php      — interface
        CycleDetectedException.php
        OverAllocationException.php
      Application/
        CreateOrder/             — CreateOrderCommand + Handler
        AddPhase/                — AddPhaseCommand + Handler
        UpdatePhaseDependencies/ — UpdatePhaseDependenciesCommand + Handler
        ConfirmOrder/            — ConfirmOrderCommand + Handler
        AdvanceStatus/           — AdvanceStatusCommand + Handler
        AssignWorker/            — AssignWorkerCommand + Handler
        GetOrderList/            — GetOrderListQuery + Handler + OrderListItemDTO
        GetOrderDetail/          — GetOrderDetailQuery + Handler + OrderDetailDTO
        ScheduleOrder/           — ScheduleOrderCommand + Handler (computes start/end dates)
      Infrastructure/
        Persistence/
          DoctrineOrderRepository.php
          Doctrine/xml/          — XML mappings
        Http/
          CreateOrderController.php
          AddPhaseController.php
          ConfirmOrderController.php
          AdvanceStatusController.php
          AssignWorkerController.php
          GetOrderListController.php
          GetOrderDetailController.php
          GetPhaseSuggestionsController.php
    Worker/
      Domain/
        Worker.php
        WorkerAllocation.php
        WorkerId.php
        WorkerRole.php           — enum
        WorkerRepository.php     — interface
      Application/
        RegisterWorker/          — RegisterWorkerCommand + Handler
        UpdateWorkerSkills/      — UpdateWorkerSkillsCommand + Handler
        SuggestCandidates/       — SuggestCandidatesQuery + Handler + CandidateDTO
        GetWorkerList/           — GetWorkerListQuery + Handler + WorkerListItemDTO
      Infrastructure/
        Persistence/
          DoctrineWorkerRepository.php
          Doctrine/xml/
        Http/
          RegisterWorkerController.php
          UpdateWorkerSkillsController.php
          GetWorkerListController.php
          GetSuggestionsController.php   — delegates to SuggestCandidates query
  tests/
    Order/
      Domain/
      Application/
    Worker/
      Domain/
      Application/
```

### HTTP API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/planning/orders` | List orders |
| POST | `/api/planning/orders` | Create order |
| GET | `/api/planning/orders/{id}` | Order detail with phases |
| POST | `/api/planning/orders/{id}/phases` | Add phase |
| PUT | `/api/planning/orders/{id}/phases/{phaseId}/dependencies` | Update phase dependencies |
| POST | `/api/planning/orders/{id}/commands/confirm` | Confirm order |
| POST | `/api/planning/orders/{id}/commands/advance-status` | Advance to next status (one step forward only: new→confirmed→in_progress→completed→shipped) |
| POST | `/api/planning/orders/{id}/commands/schedule` | Compute phase start/end dates |
| GET | `/api/planning/orders/{id}/phases/{phaseId}/suggestions` | Suggest candidates |
| POST | `/api/planning/orders/{id}/phases/{phaseId}/assignments` | Assign worker to phase |
| GET | `/api/planning/workers` | List workers |
| POST | `/api/planning/workers` | Register worker |
| PUT | `/api/planning/workers/{id}/skills` | Update worker skills |

### Permissions

New enum `Planning\Infrastructure\Security\PlanningPermission`:
- `planning.orders.manage`
- `planning.workers.manage`

`PermissionVoter` in the Identity package already handles any dotted permission string, so no additional voter needed.

### Database tables

- `planning_orders` — id, name, client_name, planned_start_date, status
- `planning_phases` — id, order_id, name, required_role, required_skills (json), headcount, duration_days, depends_on (json of UUIDs), start_date, end_date
- `planning_assignments` — phase_id, user_id, allocation_percent
- `planning_workers` — id (= user_id), primary_role, skills (json)
- `planning_worker_allocations` — worker_id, order_id, phase_id, allocation_percent, start_date, end_date

---

## Frontend Architecture

### File structure

```
frontend/src/app/
  api/
    planning.ts                  — API types + planningApi object
  modules/
    planning/
      OrdersPage.tsx
      OrdersPage.module.css
      OrderDetailPage.tsx
      OrderDetailPage.module.css
      WorkersPage.tsx
      WorkersPage.module.css
      WorkerDetailPage.tsx
      WorkerDetailPage.module.css
```

### Pages

**OrdersPage** (`/planning/orders`)
- Table: name, clientName, status badge, phase count
- "Vytvořit zakázku" button → modal (name, clientName, plannedStartDate)
- Row click → OrderDetailPage

**OrderDetailPage** (`/planning/orders/:orderId`)
- Breadcrumb: Zakázky › {name}
- Heading + status badge + advance-status button
- Section "Fáze": list of phases showing role, skills badges, headcount, durationDays, startDate–endDate (if scheduled), assigned workers
  - "Přidat fázi" button → modal (name, requiredRole, requiredSkills, headcount, durationDays, dependsOn checkboxes from existing phases)
  - Per unassigned phase: "Naplánovat" button → triggers schedule command, then shows suggestions modal
- Section "Přiřazení": for each phase with open slots, suggestion modal shows candidate name, role, skills, available capacity %; manager clicks to assign

**WorkersPage** (`/planning/workers`)
- Table: name (from Identity), primaryRole, skills badges, current total allocation %
- "Přidat pracovníka" button → modal (user select from Identity users not yet registered as workers, primaryRole, skills)
- Row click → WorkerDetailPage

**WorkerDetailPage** (`/planning/workers/:workerId`)
- Breadcrumb: Pracovníci › {name}
- Personal data (read-only from Identity)
- Section "Kompetence": primaryRole select + skills tags editor → save
- Section "Alokace": table of active and future allocations (order name, phase name, %, start–end)

### Navigation

New sidebar section "Plánování" between CRM and Administrace:
- Zakázky → `/planning/orders`
- Pracovníci → `/planning/workers`

### Permissions

Router guards use `planning.orders.manage` and `planning.workers.manage` (same `requirePermission` pattern as identity routes).

---

## Error Handling

| Domain exception | HTTP status | Message |
|-----------------|-------------|---------|
| `CycleDetectedException` | 422 | "Fáze tvoří cyklus závislostí" |
| `OverAllocationException` | 422 | "Pracovník by byl alokován na více než 100 %" |
| `InvalidStatusTransitionException` | 422 | "Neplatný přechod stavu" |

All domain exceptions are caught by the existing `SharedKernel` `DomainExceptionListener` and returned as `{"error": "..."}` JSON.

---

## Testing Strategy

- Domain unit tests: Order phase DAG (cycle detection, topological sort, schedule computation), Worker allocation conflict detection, status transitions
- Application unit tests: all command/query handlers with test doubles for repositories
- No integration tests (follow existing project pattern — unit tests only in packages)
