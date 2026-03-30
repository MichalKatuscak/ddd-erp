import { createRouter, createRoute, createRootRoute, Outlet, redirect } from '@tanstack/react-router'
import { useAuthStore } from './auth/authStore'
import { LoginPage } from './modules/auth/LoginPage'

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
    // No known route accessible — stay on '/' and render a fallback
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
  component: () => <div style={{ padding: 32 }}>CRM Zákazníci — připravuje se</div>,
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
  identityUsersRoute,
  identityRolesRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register { router: typeof router }
}
