# Input Validation Design

**Date:** 2026-03-31
**Scope:** HTTP input validation across all bounded contexts (CRM, Identity, Planning)

---

## Overview

Introduce a two-layer validation strategy:

1. **HTTP layer** — Request DTOs with Symfony Validator constraints, deserialized automatically via `#[MapRequestPayload]`
2. **Domain layer** — Value Objects continue to enforce domain invariants (unchanged)

Error responses follow RFC 7807 Problem Details standard, formatted by the existing `DomainExceptionListener` (extended).

---

## Architecture

```
HTTP Request
    ↓
#[MapRequestPayload] → deserializes JSON into Request DTO → validates constraints
    ↓ (if invalid: throws HttpException 422 automatically)
Controller → creates Command from validated DTO → dispatches to CommandBus
    ↓ (if domain violation: throws DomainException)
DomainExceptionListener → formats all errors as RFC 7807 JSON
```

No changes to: Commands, Command Handlers, Domain layer, Value Objects.

---

## Request DTOs

One DTO per command endpoint that accepts a JSON body. Placed in each package's `Infrastructure/Http/Request/` directory.

### CRM — Contacts

**`RegisterCustomerRequest`**
- `email`: `#[NotBlank, Email]`
- `first_name`: `#[NotBlank, Length(min:1, max:100)]`
- `last_name`: `#[NotBlank, Length(min:1, max:100)]`

**`UpdateCustomerRequest`**
- `email`: `#[NotBlank, Email]`
- `first_name`: `#[NotBlank, Length(min:1, max:100)]`
- `last_name`: `#[NotBlank, Length(min:1, max:100)]`

### Identity — Auth

**`LoginRequest`**
- `email`: `#[NotBlank, Email]`
- `password`: `#[NotBlank, Length(min:8)]`

**`RefreshAccessTokenRequest`**
- `refresh_token`: `#[NotBlank, Uuid]`

**`LogoutRequest`**
- `refresh_token`: `#[NotBlank, Uuid]`

### Identity — User

**`RegisterUserRequest`**
- `email`: `#[NotBlank, Email]`
- `password`: `#[NotBlank, Length(min:8, max:255)]`
- `first_name`: `#[NotBlank, Length(min:1, max:100)]`
- `last_name`: `#[NotBlank, Length(min:1, max:100)]`

**`UpdateUserRequest`**
- `email`: `#[NotBlank, Email]`
- `first_name`: `#[NotBlank, Length(min:1, max:100)]`
- `last_name`: `#[NotBlank, Length(min:1, max:100)]`

**`AssignRolesToUserRequest`**
- `role_ids`: `#[NotNull, Count(min:0), All([new Uuid()])]` — empty array is valid (removes all roles from user)

### Identity — Role

**`CreateRoleRequest`**
- `name`: `#[NotBlank, Length(min:1, max:100)]`
- `permissions`: `#[NotNull, Count(min:1), All([new NotBlank()])]`

**`UpdateRolePermissionsRequest`**
- `permissions`: `#[NotNull, Count(min:1), All([new NotBlank()])]`

### Endpoints without Request DTOs (no JSON body)

- `DeactivateUserController` — only route param `{id}`
- `GetCustomerListController` — only query params
- `GetCustomerDetailController` — only route param
- `GetCurrentUserController` — no params
- `GetUserListController` — only query params
- `GetUserDetailController` — only route param
- `GetRoleListController` — only query params
- `GetRoleDetailController` — only route param

---

## DomainExceptionListener Extension

The existing listener in `shared-kernel` is extended to format all errors as RFC 7807.

### Response Formats

**Validation error (HTTP 422):**
```json
{
    "type": "/errors/validation",
    "title": "Validation Failed",
    "status": 422,
    "violations": {
        "email": ["This value should not be blank.", "This value is not a valid email address."],
        "first_name": ["This value should not be blank."]
    }
}
```

**Domain rule violation (HTTP 422):**
```json
{
    "type": "/errors/domain",
    "title": "Business Rule Violation",
    "status": 422,
    "detail": "Customer with this email already exists."
}
```

**Not found (HTTP 404):**
```json
{
    "type": "/errors/not-found",
    "title": "Resource Not Found",
    "status": 404,
    "detail": "Customer not found."
}
```

**Listener priority:** `onKernelException` method handles three cases in order:
1. `HttpException` with status 422 and validation violations → validation format
2. `HandlerFailedException` wrapping `DomainException` → domain/not-found format (existing logic, reformatted to RFC 7807)
3. Everything else → passed through unchanged

---

## Testing

### Unit Tests — Request DTOs

Each DTO gets a unit test class in the same package's test directory:

- `test_accepts_valid_data()` — happy path
- `test_rejects_blank_required_field()` — per required field
- `test_rejects_invalid_email()` — for email fields
- `test_rejects_short_password()` — for password fields

Uses Symfony Validator component directly (no HTTP stack needed).

### Unit Tests — DomainExceptionListener

Extended to cover new RFC 7807 format cases:
- Validation exception → correct violations structure
- Domain exception → correct detail format
- Not found exception → correct 404 format

### Integration Tests — Controller Layer

Existing controller tests extended with invalid input scenarios:
- Missing required fields → 422 with violations
- Invalid email format → 422 with violations
- Valid input still works → existing happy path tests unchanged

---

## File Structure

```
packages/
  crm/src/Contacts/Infrastructure/Http/
    Request/
      RegisterCustomerRequest.php
      UpdateCustomerRequest.php

  identity/src/Auth/Infrastructure/Http/
    Request/
      LoginRequest.php
      RefreshAccessTokenRequest.php
      LogoutRequest.php

  identity/src/User/Infrastructure/Http/
    Request/
      RegisterUserRequest.php
      UpdateUserRequest.php
      AssignRolesToUserRequest.php

  identity/src/Role/Infrastructure/Http/
    Request/
      CreateRoleRequest.php
      UpdateRolePermissionsRequest.php

  shared-kernel/src/Infrastructure/Http/
    DomainExceptionListener.php  ← modified
```

---

## Out of Scope

- Planning module controllers (to be addressed in a separate spec once Planning HTTP layer is complete)
- Frontend form validation (separate concern)
- Database-level unique constraints (separate spec)
- Rate limiting / security headers
