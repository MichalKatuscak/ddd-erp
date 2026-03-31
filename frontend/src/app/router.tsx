import { createRouter, createRoute, createRootRoute, Outlet, redirect } from '@tanstack/react-router'
import { useAuthStore } from './auth/authStore'
import { LoginPage } from './modules/auth/LoginPage'
import { CustomersPage } from './modules/crm/CustomersPage'
import { CustomerDetailPage } from './modules/crm/CustomerDetailPage'
import { RolesPage } from './modules/identity/RolesPage'
import { RoleDetailPage } from './modules/identity/RoleDetailPage'

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

const identityUserDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/users/$userId',
  beforeLoad: () => requirePermission('identity.users.manage'),
  component: () => <div style={{ padding: 32 }}>Detail uživatele — připravuje se</div>,
})

const identityRolesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/roles',
  beforeLoad: () => requirePermission('identity.roles.manage'),
  component: RolesPage,
})

const identityRoleDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/roles/$roleId',
  beforeLoad: () => requirePermission('identity.roles.manage'),
  component: RoleDetailPage,
})

const routeTree = rootRoute.addChildren([
  loginRoute,
  indexRoute,
  crmCustomersRoute,
  crmCustomerDetailRoute,
  identityUsersRoute,
  identityUserDetailRoute,
  identityRolesRoute,
  identityRoleDetailRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register { router: typeof router }
}
