# Frontend CRM Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement CustomersPage and CustomerDetailPage connected to the real CRM backend, plus the AppLayout sidebar used by all authenticated pages.

**Architecture:** AppLayout wraps all authenticated pages with a permission-aware sidebar (ERP logo, nav links, user info, logout). CustomersPage uses TanStack Query to fetch customers and opens a Modal to add a new one. CustomerDetailPage prefills a form from the API and persists updates.

**Tech Stack:** React 19, TypeScript, TanStack Router v1, TanStack Query v5, CSS Modules, design system from `src/design-system/index.ts`

---

## Important: actual backend response shapes

The `crm.ts` types were written speculatively and **do not match the backend**. The real shapes are:

**GET /api/crm/contacts/customers** returns:
```json
[{ "id": "uuid", "email": "string", "full_name": "string", "registered_at": "string" }]
```

**GET /api/crm/contacts/customers/{id}** returns:
```json
{ "id": "uuid", "email": "string", "first_name": "string", "last_name": "string", "registered_at": "string" }
```

**POST register-customer** and **PUT update-customer** only accept `{ email, first_name, last_name }` — no phone, address.

---

## File structure

| File | Action | Responsibility |
|------|--------|----------------|
| `frontend/src/app/api/crm.ts` | Modify | Fix types to match actual backend |
| `frontend/src/app/components/AppLayout/AppLayout.tsx` | Create | Sidebar nav + PageLayout wrapper |
| `frontend/src/app/components/AppLayout/AppLayout.module.css` | Create | Sidebar nav styles |
| `frontend/src/app/modules/crm/CustomersPage.tsx` | Create | Customer list + "Přidat zákazníka" modal |
| `frontend/src/app/modules/crm/CustomersPage.module.css` | Create | Page styles |
| `frontend/src/app/modules/crm/CustomerDetailPage.tsx` | Create | Edit form + breadcrumb |
| `frontend/src/app/modules/crm/CustomerDetailPage.module.css` | Create | Page styles |
| `frontend/src/app/router.tsx` | Modify | Real components, add `$customerId` route |

---

## Task 1 — Fix crm.ts API types

**Files:**
- Modify: `frontend/src/app/api/crm.ts`

- [ ] **1.1** Read the current file

```bash
cat /home/michal/ddd-erp/.worktrees/crm-module/frontend/src/app/api/crm.ts
```

- [ ] **1.2** Replace entire `frontend/src/app/api/crm.ts` with corrected types

```ts
import { apiGet, apiPost, apiPut } from './client'

export interface CustomerListItem {
  id: string
  email: string
  full_name: string
  registered_at: string
}

export interface CustomerDetail {
  id: string
  email: string
  first_name: string
  last_name: string
  registered_at: string
}

export const crmApi = {
  getCustomers: () =>
    apiGet<CustomerListItem[]>('/api/crm/contacts/customers'),

  getCustomer: (id: string) =>
    apiGet<CustomerDetail>(`/api/crm/contacts/customers/${id}`),

  registerCustomer: (data: { firstName: string; lastName: string; email: string }) =>
    apiPost<{ id: string }>('/api/crm/contacts/customers/commands/register-customer', {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
    }),

  updateCustomer: (id: string, data: { firstName: string; lastName: string; email: string }) =>
    apiPut<void>(`/api/crm/contacts/customers/commands/update-customer/${id}`, {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
    }),
}
```

- [ ] **1.3** Verify TypeScript compiles

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npx tsc --noEmit
```

Expected: 0 errors.

- [ ] **1.4** Commit

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module && git add frontend/src/app/api/crm.ts && git commit -m "fix(frontend): fix crmApi types to match actual backend response shapes"
```

---

## Task 2 — AppLayout (sidebar + navigation)

**Files:**
- Create: `frontend/src/app/components/AppLayout/AppLayout.tsx`
- Create: `frontend/src/app/components/AppLayout/AppLayout.module.css`

- [ ] **2.1** Create `frontend/src/app/components/AppLayout/AppLayout.tsx`

```tsx
import type { ReactNode } from 'react'
import { Link, useNavigate } from '@tanstack/react-router'
import { useAuthStore } from '../../auth/authStore'
import { identityApi } from '../../api/identity'
import { PageLayout } from '../../../design-system'
import styles from './AppLayout.module.css'

interface AppLayoutProps {
  children: ReactNode
}

export function AppLayout({ children }: AppLayoutProps) {
  const navigate = useNavigate()
  const user = useAuthStore(s => s.user)
  const hasPermission = useAuthStore(s => s.hasPermission)
  const refreshToken = useAuthStore(s => s.refreshToken)
  const clear = useAuthStore(s => s.clear)

  const handleLogout = async () => {
    if (refreshToken) {
      await identityApi.logout(refreshToken).catch(() => {})
    }
    clear()
    await navigate({ to: '/login' })
  }

  const sidebar = (
    <nav className={styles.nav}>
      <div className={styles.logo}>ERP</div>

      {hasPermission('crm.contacts.view_customers') && (
        <div className={styles.section}>
          <p className={styles.sectionLabel}>CRM</p>
          <Link
            to="/crm/customers"
            activeProps={{ className: `${styles.link} ${styles.linkActive}` }}
            inactiveProps={{ className: styles.link }}
          >
            Zákazníci
          </Link>
        </div>
      )}

      {(hasPermission('identity.users.manage') || hasPermission('identity.roles.manage')) && (
        <div className={styles.section}>
          <p className={styles.sectionLabel}>Administrace</p>
          {hasPermission('identity.users.manage') && (
            <Link
              to="/identity/users"
              activeProps={{ className: `${styles.link} ${styles.linkActive}` }}
              inactiveProps={{ className: styles.link }}
            >
              Uživatelé
            </Link>
          )}
          {hasPermission('identity.roles.manage') && (
            <Link
              to="/identity/roles"
              activeProps={{ className: `${styles.link} ${styles.linkActive}` }}
              inactiveProps={{ className: styles.link }}
            >
              Role
            </Link>
          )}
        </div>
      )}

      <div className={styles.userSection}>
        {user && (
          <div className={styles.userInfo}>
            <div className={styles.avatar}>
              {user.firstName[0]}{user.lastName[0]}
            </div>
            <span className={styles.userEmail}>{user.email}</span>
          </div>
        )}
        <button className={styles.logoutBtn} onClick={handleLogout} type="button">
          Odhlásit
        </button>
      </div>
    </nav>
  )

  return <PageLayout sidebar={sidebar}>{children}</PageLayout>
}
```

- [ ] **2.2** Create `frontend/src/app/components/AppLayout/AppLayout.module.css`

```css
.nav {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.logo {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-900);
  margin-bottom: var(--space-6);
  padding: 0 var(--space-2);
}

.section {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  margin-bottom: var(--space-4);
}

.sectionLabel {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-400);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0 var(--space-2);
  margin-bottom: var(--space-1);
}

.link {
  display: block;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  color: var(--color-neutral-700);
  text-decoration: none;
  transition: background-color 0.1s;
}

.link:hover {
  background: var(--color-neutral-100);
}

.linkActive {
  background: var(--color-primary-50);
  color: var(--color-primary-700);
  font-weight: var(--font-weight-medium);
}

.linkActive:hover {
  background: var(--color-primary-100);
}

.userSection {
  margin-top: auto;
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-neutral-200);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.userInfo {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.avatar {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-primary-100);
  color: var(--color-primary-700);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  display: flex;
  align-items: center;
  justify-content: center;
}

.userEmail {
  font-size: var(--font-size-xs);
  color: var(--color-neutral-600);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logoutBtn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
  padding: var(--space-1) 0;
  text-align: left;
  transition: color 0.1s;
}

.logoutBtn:hover {
  color: var(--color-neutral-900);
}
```

- [ ] **2.3** Verify TypeScript

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npx tsc --noEmit
```

Expected: 0 errors.

- [ ] **2.4** Commit

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module && git add frontend/src/app/components/ && git commit -m "feat(frontend): add AppLayout with permission-aware sidebar and logout"
```

---

## Task 3 — Update router with real CRM components

**Files:**
- Modify: `frontend/src/app/router.tsx`

- [ ] **3.1** Read current `frontend/src/app/router.tsx`

```bash
cat /home/michal/ddd-erp/.worktrees/crm-module/frontend/src/app/router.tsx
```

- [ ] **3.2** Replace entire `frontend/src/app/router.tsx`

```tsx
import { createRouter, createRoute, createRootRoute, Outlet, redirect } from '@tanstack/react-router'
import { useAuthStore } from './auth/authStore'
import { LoginPage } from './modules/auth/LoginPage'
import { CustomersPage } from './modules/crm/CustomersPage'
import { CustomerDetailPage } from './modules/crm/CustomerDetailPage'

function requireAuth() {
  if (!useAuthStore.getState().isAuthenticated()) {
    throw redirect({ to: '/login' })
  }
}

function requirePermission(permission: string) {
  requireAuth()
  if (!useAuthStore.getState().hasPermission(permission)) {
    throw redirect({ to: '/' })
  }
}

const rootRoute = createRootRoute({ component: () => <Outlet /> })

const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  component: LoginPage,
})

const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  beforeLoad: () => {
    requireAuth()
    const store = useAuthStore.getState()
    if (store.hasPermission('crm.contacts.view_customers')) {
      throw redirect({ to: '/crm/customers' })
    }
    if (store.hasPermission('identity.users.manage')) {
      throw redirect({ to: '/identity/users' })
    }
    if (store.hasPermission('identity.roles.manage')) {
      throw redirect({ to: '/identity/roles' })
    }
  },
  component: () => (
    <div style={{ padding: 32, color: 'var(--color-neutral-600)' }}>
      Nemáte přístup k žádné sekci. Kontaktujte správce.
    </div>
  ),
})

const crmCustomersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/crm/customers',
  beforeLoad: () => requirePermission('crm.contacts.view_customers'),
  component: CustomersPage,
})

const crmCustomerDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/crm/customers/$customerId',
  beforeLoad: () => requirePermission('crm.contacts.view_customers'),
  component: CustomerDetailPage,
})

const identityUsersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/users',
  beforeLoad: () => requirePermission('identity.users.manage'),
  component: () => <div style={{ padding: 32 }}>Uživatelé — připravuje se</div>,
})

const identityRolesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/roles',
  beforeLoad: () => requirePermission('identity.roles.manage'),
  component: () => <div style={{ padding: 32 }}>Role — připravuje se</div>,
})

const routeTree = rootRoute.addChildren([
  loginRoute,
  indexRoute,
  crmCustomersRoute,
  crmCustomerDetailRoute,
  identityUsersRoute,
  identityRolesRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register { router: typeof router }
}
```

- [ ] **3.3** Create stub files so TypeScript can resolve the imports (Tasks 4 and 5 will replace them)

Create `frontend/src/app/modules/crm/CustomersPage.tsx`:

```tsx
export function CustomersPage() {
  return <div style={{ padding: 32 }}>Zákazníci — načítám…</div>
}
```

Create `frontend/src/app/modules/crm/CustomerDetailPage.tsx`:

```tsx
export function CustomerDetailPage() {
  return <div style={{ padding: 32 }}>Detail zákazníka — načítám…</div>
}
```

- [ ] **3.4** Verify TypeScript and build

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npx tsc --noEmit && npm run build 2>&1 | tail -5
```

Expected: 0 TS errors, successful build.

- [ ] **3.5** Run tests

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npm test -- --run
```

Expected: 7 tests pass.

- [ ] **3.6** Commit

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module && git add frontend/src/app/router.tsx frontend/src/app/modules/crm/ && git commit -m "feat(frontend): add CRM customer routes to router (stub components)"
```

---

## Task 4 — CustomersPage

**Files:**
- Modify: `frontend/src/app/modules/crm/CustomersPage.tsx` (replace stub)
- Create: `frontend/src/app/modules/crm/CustomersPage.module.css`

- [ ] **4.1** Replace `frontend/src/app/modules/crm/CustomersPage.tsx`

```tsx
import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { crmApi } from '../../api/crm'
import type { CustomerListItem } from '../../api/crm'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './CustomersPage.module.css'

export function CustomersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [email, setEmail] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['customers'],
    queryFn: () => crmApi.getCustomers(),
  })

  const addMutation = useMutation({
    mutationFn: () => crmApi.registerCustomer({ firstName, lastName, email }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      setOpen(false)
      setFirstName('')
      setLastName('')
      setEmail('')
    },
  })

  const columns: Column<CustomerListItem>[] = [
    { key: 'full_name', header: 'Jméno' },
    { key: 'email', header: 'E-mail' },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Zákazníci</h1>
          <Button onClick={() => setOpen(true)}>Přidat zákazníka</Button>
        </div>
        <Table
          columns={columns}
          data={data}
          loading={isLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/crm/customers/$customerId', params: { customerId: row.id } })
          }}
        />
        <Modal open={open} onClose={() => setOpen(false)} title="Přidat zákazníka">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); addMutation.mutate() }}
          >
            <FormField label="Jméno" htmlFor="addFirstName">
              <Input
                id="addFirstName"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                placeholder="Jan"
              />
            </FormField>
            <FormField label="Příjmení" htmlFor="addLastName">
              <Input
                id="addLastName"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                placeholder="Novák"
              />
            </FormField>
            <FormField
              label="E-mail"
              htmlFor="addEmail"
              error={addMutation.isError ? 'Nepodařilo se uložit zákazníka' : undefined}
            >
              <Input
                id="addEmail"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="jan@firma.cz"
                error={addMutation.isError}
              />
            </FormField>
            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={() => setOpen(false)}
              >
                Zrušit
              </Button>
              <Button type="submit" loading={addMutation.isPending}>
                Uložit
              </Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
```

- [ ] **4.2** Create `frontend/src/app/modules/crm/CustomersPage.module.css`

```css
.page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-900);
}

.form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  margin-top: var(--space-2);
}
```

- [ ] **4.3** Verify TypeScript

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npx tsc --noEmit
```

Expected: 0 errors.

- [ ] **4.4** Run tests

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npm test -- --run
```

Expected: 7 tests pass.

- [ ] **4.5** Commit

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module && git add frontend/src/app/modules/crm/CustomersPage.tsx frontend/src/app/modules/crm/CustomersPage.module.css && git commit -m "feat(frontend): implement CustomersPage with table and add-customer modal"
```

---

## Task 5 — CustomerDetailPage

**Files:**
- Modify: `frontend/src/app/modules/crm/CustomerDetailPage.tsx` (replace stub)
- Create: `frontend/src/app/modules/crm/CustomerDetailPage.module.css`

- [ ] **5.1** Replace `frontend/src/app/modules/crm/CustomerDetailPage.tsx`

```tsx
import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, FormField, Input } from '../../../design-system'
import { crmApi } from '../../api/crm'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './CustomerDetailPage.module.css'

export function CustomerDetailPage() {
  const { customerId } = useParams({ from: '/crm/customers/$customerId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [email, setEmail] = useState('')

  const { data: customer, isLoading } = useQuery({
    queryKey: ['customer', customerId],
    queryFn: () => crmApi.getCustomer(customerId),
  })

  useEffect(() => {
    if (customer) {
      setFirstName(customer.first_name)
      setLastName(customer.last_name)
      setEmail(customer.email)
    }
  }, [customer])

  const updateMutation = useMutation({
    mutationFn: () => crmApi.updateCustomer(customerId, { firstName, lastName, email }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customer', customerId] })
      queryClient.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  const fullName = customer ? `${customer.first_name} ${customer.last_name}` : '…'

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/crm/customers" className={styles.breadcrumbLink}>Zákazníci</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{fullName}</span>
        </nav>

        {isLoading ? (
          <p className={styles.loading}>Načítám…</p>
        ) : (
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); updateMutation.mutate() }}
          >
            <h1 className={styles.title}>{fullName}</h1>
            <div className={styles.fields}>
              <FormField label="Jméno" htmlFor="detFirstName">
                <Input
                  id="detFirstName"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                />
              </FormField>
              <FormField label="Příjmení" htmlFor="detLastName">
                <Input
                  id="detLastName"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                />
              </FormField>
              <FormField
                label="E-mail"
                htmlFor="detEmail"
                error={updateMutation.isError ? 'Nepodařilo se uložit změny' : undefined}
              >
                <Input
                  id="detEmail"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  error={updateMutation.isError}
                />
              </FormField>
            </div>
            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={async () => { await navigate({ to: '/crm/customers' }) }}
              >
                Zpět
              </Button>
              <Button
                type="submit"
                loading={updateMutation.isPending}
              >
                Uložit
              </Button>
            </div>
          </form>
        )}
      </div>
    </AppLayout>
  )
}
```

- [ ] **5.2** Create `frontend/src/app/modules/crm/CustomerDetailPage.module.css`

```css
.page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
}

.breadcrumbLink {
  color: var(--color-primary-600);
  text-decoration: none;
}

.breadcrumbLink:hover {
  text-decoration: underline;
}

.breadcrumbSep {
  color: var(--color-neutral-300);
}

.breadcrumbCurrent {
  color: var(--color-neutral-700);
}

.loading {
  color: var(--color-neutral-500);
  font-size: var(--font-size-sm);
}

.title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-900);
}

.form {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.fields {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  max-width: 480px;
}

.actions {
  display: flex;
  gap: var(--space-3);
}
```

- [ ] **5.3** Verify TypeScript

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npx tsc --noEmit
```

Expected: 0 errors.

- [ ] **5.4** Run all tests

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npm test -- --run
```

Expected: 7 tests pass, 0 failures.

- [ ] **5.5** Build

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module/frontend && npm run build 2>&1 | tail -5
```

Expected: successful build, no errors.

- [ ] **5.6** Commit

```bash
cd /home/michal/ddd-erp/.worktrees/crm-module && git add frontend/src/app/modules/crm/CustomerDetailPage.tsx frontend/src/app/modules/crm/CustomerDetailPage.module.css && git commit -m "feat(frontend): implement CustomerDetailPage with prefilled form and breadcrumb"
```

---

## Final verification

- [ ] **V.1** TypeScript clean: `npx tsc --noEmit` → 0 errors
- [ ] **V.2** Tests: `npm test -- --run` → 7 passing
- [ ] **V.3** Build: `npm run build` → success
- [ ] **V.4** Manual: dev server starts, login redirects to `/crm/customers`, table loads, "Přidat zákazníka" opens modal, row click navigates to detail, form prefills and saves
