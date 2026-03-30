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
