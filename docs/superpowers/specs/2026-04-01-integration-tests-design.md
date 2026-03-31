# Integration Tests — Missing Coverage Design

**Date:** 2026-04-01
**Scope:** HTTP integration tests for untested CRM and Identity controllers + domain error scenarios

---

## Overview

Add integration tests for 8 untested controllers (GET endpoints + DeactivateUser) and domain error scenarios (duplicate email → 422, entity not found → 404) to existing command controller test files. Planning module is out of scope (separate spec).

---

## What's Being Added

### New test files (8 controllers)

| Controller | Route | Tests |
|---|---|---|
| `GetCustomerListController` | `GET /api/crm/contacts/customers` | 200 returns array, 401 no auth |
| `GetCustomerDetailController` | `GET /api/crm/contacts/customers/{id}` | 200 returns data, 404 not found, 401 no auth |
| `GetUserListController` | `GET /api/identity/users` | 200 returns array, 401 no auth |
| `GetUserDetailController` | `GET /api/identity/users/{id}` | 200 returns data, 404 not found, 401 no auth |
| `DeactivateUserController` | `POST /api/identity/users/commands/deactivate-user/{id}` | 204 success, 404 not found, 401 no auth |
| `GetCurrentUserController` | `GET /api/identity/me` | 200 returns current user data, 401 no auth |
| `GetRoleListController` | `GET /api/identity/roles` | 200 returns array, 401 no auth |
| `GetRoleDetailController` | `GET /api/identity/roles/{id}` | 200 returns data, 404 not found, 401 no auth |

### Domain error additions to existing test files (5 files)

| File | New tests |
|---|---|
| `RegisterCustomerControllerTest` | 422 on duplicate email |
| `RegisterUserControllerTest` | 422 on duplicate email |
| `UpdateCustomerControllerTest` | 404 on non-existent customer |
| `UpdateUserControllerTest` | 404 on non-existent user |
| `UpdateRolePermissionsControllerTest` | 404 on non-existent role |

---

## Test Patterns

### GET list test pattern
```php
// 1. Login as admin → get token
// 2. GET list endpoint → assert 200, assert response is array
// 3. GET list without token → assert 401
```

### GET detail test pattern
```php
// 1. Login → create entity via POST → GET it by returned ID
// 2. Assert 200, assert response has expected keys
// 3. GET with non-existent UUID → assert 404 RFC 7807 format
// 4. GET without token → assert 401
```

### Non-existent ID for 404 tests
Use `00000000-0000-7000-8000-000000000001` — valid UUID format, guaranteed not in DB.

### RFC 7807 format for 404 errors
```json
{"type": "/errors/not-found", "title": "Resource Not Found", "status": 404}
```

### RFC 7807 format for 422 domain errors
```json
{"type": "/errors/domain", "title": "Business Rule Violation", "status": 422}
```

---

## Response Shape Assertions

### GetCustomerList — array items have: `id`, `email`, `full_name`, `registered_at`
### GetCustomerDetail — has: `id`, `email`, `first_name`, `last_name`, `registered_at`
### GetUserList — array items have: `id`, `email`, `name`, `role_ids`, `active`
### GetUserDetail — has: `id`, `email`, `first_name`, `last_name`, `role_ids`, `active`, `created_at`
### GetCurrentUser — has: `id`, `email`, `first_name`, `last_name`, `permissions`
### GetRoleList — array items have: `id`, `name`, `permissions`
### GetRoleDetail — has: `id`, `name`, `permissions`

---

## Out of Scope

- Planning module controllers (separate spec)
- Permission-level tests (403 Forbidden)
- Expired/invalid token edge cases
- Pagination edge cases
- DeactivateUser happy path requires creating a fresh user (to avoid deactivating the seeded admin)
