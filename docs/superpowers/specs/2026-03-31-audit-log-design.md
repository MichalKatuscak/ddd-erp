# Audit Log Design

**Date:** 2026-03-31
**Scope:** Domain event audit logging across all bounded contexts (CRM, Identity)

---

## Overview

Introduce a dedicated `audit` package that listens to all 8 domain events and persists a structured audit trail to a PostgreSQL `audit_log` table. Each entry records what happened, which aggregate was affected, the event payload, who triggered it, and when.

---

## Architecture

```
packages/audit/                          ← new bounded context
  composer.json
  src/
    Infrastructure/
      AuditLogEntry.php                  ← Doctrine entity
      AuditLogEventHandler.php           ← 8 handler methods on event.bus
  tests/
    Infrastructure/
      AuditLogEventHandlerTest.php       ← unit tests (no DB)
      AuditLogIntegrationTest.php        ← integration test (real DB)
```

The `audit` package depends on:
- `ddd-erp/shared-kernel` — for `DomainEvent` base class
- `ddd-erp/crm` — for CRM domain events
- `ddd-erp/identity` — for Identity domain events

Registered in root `composer.json` as a path repository, autoloaded under `Audit\` namespace.

---

## Database Schema

Table: `audit_log`

| Column | Type | Notes |
|---|---|---|
| `id` | UUID (PK) | Generated |
| `event_type` | varchar(100) | Short class name, e.g. `"CustomerRegistered"` |
| `aggregate_id` | varchar(36) | UUID of the affected aggregate |
| `payload` | JSON | Event-specific data (see mapping below) |
| `performed_by` | varchar(36) nullable | UUID of authenticated user, null for system ops |
| `occurred_at` | timestamptz | From `DomainEvent::$occurredAt` |

---

## Event Handler

`AuditLogEventHandler` has 8 methods, each annotated with `#[AsMessageHandler(bus: 'event.bus')]`.

`performed_by` is read from `Symfony\Bundle\SecurityBundle\Security::getUser()?->getUserIdentifier()` — nullable (null when no authenticated user, e.g. seeding).

### Payload Mapping

| Event | `event_type` | `aggregate_id` | `payload` |
|---|---|---|---|
| `CustomerRegistered` | `"CustomerRegistered"` | `customerId` | `{email, first_name, last_name}` |
| `CustomerUpdated` | `"CustomerUpdated"` | `customerId` | `{email, first_name, last_name}` |
| `UserCreated` | `"UserCreated"` | `userId` | `{email, first_name, last_name}` |
| `UserUpdated` | `"UserUpdated"` | `userId` | `{email, first_name, last_name}` |
| `UserDeactivated` | `"UserDeactivated"` | `userId` | `{}` |
| `RoleAssignedToUser` | `"RoleAssignedToUser"` | `userId` | `{role_ids: [...]}` |
| `RoleCreated` | `"RoleCreated"` | `roleId` | `{name, permissions: [...]}` |
| `RolePermissionsUpdated` | `"RolePermissionsUpdated"` | `roleId` | `{permissions: [...]}` |

---

## Testing

### Unit Tests — `AuditLogEventHandlerTest`

Uses PHPUnit directly, no HTTP stack, no database. Mocks `EntityManagerInterface` and `Security`.

- For each of the 8 events: assert `EntityManager::persist()` called with correct `event_type`, `aggregate_id`, `payload`
- One test for null `performed_by` (unauthenticated context)

### Integration Test — `AuditLogIntegrationTest`

Uses Symfony `WebTestCase`, real PostgreSQL test database.

- Sends a `POST /api/crm/contacts/commands/register-customer` with valid data
- Queries `audit_log` table
- Asserts one row with `event_type = "CustomerRegistered"` and correct `aggregate_id`

---

## File Structure

```
packages/audit/
  composer.json
  src/Infrastructure/
    AuditLogEntry.php
    AuditLogEventHandler.php
  tests/Infrastructure/
    AuditLogEventHandlerTest.php
    AuditLogIntegrationTest.php

migrations/
  Version<timestamp>CreateAuditLogTable.php   ← new Doctrine migration
```

---

## Out of Scope

- Querying audit logs via HTTP API (separate feature)
- Async event processing (events remain synchronous)
- Retention / cleanup policy
- Frontend audit log viewer
