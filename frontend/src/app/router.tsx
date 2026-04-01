import { createRouter, createRoute, createRootRoute, Outlet, redirect } from '@tanstack/react-router'
import { useAuthStore } from './auth/authStore'
import { LoginPage } from './modules/auth/LoginPage'
import { CustomersPage } from './modules/crm/CustomersPage'
import { CustomerDetailPage } from './modules/crm/CustomerDetailPage'
import { RolesPage } from './modules/identity/RolesPage'
import { RoleDetailPage } from './modules/identity/RoleDetailPage'
import { UsersPage } from './modules/identity/UsersPage'
import { UserDetailPage } from './modules/identity/UserDetailPage'
import { OrdersPage } from './modules/planning/OrdersPage'
import { OrderDetailPage } from './modules/planning/OrderDetailPage'
import { WorkersPage } from './modules/planning/WorkersPage'
import { WorkerDetailPage } from './modules/planning/WorkerDetailPage'
import { InquiriesPage } from './modules/sales/InquiriesPage'
import { InquiryDetailPage } from './modules/sales/InquiryDetailPage'
import { QuoteDetailPage } from './modules/sales/QuoteDetailPage'

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
  component: UsersPage,
})

const identityUserDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/identity/users/$userId',
  beforeLoad: () => requirePermission('identity.users.manage'),
  component: UserDetailPage,
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

const planningOrdersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/planning/orders',
  beforeLoad: () => requirePermission('planning.orders.manage'),
  component: OrdersPage,
})

const planningOrderDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/planning/orders/$orderId',
  beforeLoad: () => requirePermission('planning.orders.manage'),
  component: OrderDetailPage,
})

const planningWorkersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/planning/workers',
  beforeLoad: () => requirePermission('planning.workers.manage'),
  component: WorkersPage,
})

const planningWorkerDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/planning/workers/$workerId',
  beforeLoad: () => requirePermission('planning.workers.manage'),
  component: WorkerDetailPage,
})

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
  beforeLoad: () => requirePermission('sales.inquiries.manage'),
  component: QuoteDetailPage,
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
  planningOrdersRoute,
  planningOrderDetailRoute,
  planningWorkersRoute,
  planningWorkerDetailRoute,
  salesInquiriesRoute,
  salesInquiryDetailRoute,
  salesQuoteDetailRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register { router: typeof router }
}
