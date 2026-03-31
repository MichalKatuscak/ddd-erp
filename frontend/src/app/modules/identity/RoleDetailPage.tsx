import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button } from '../../../design-system'
import { identityApi } from '../../api/identity'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './RoleDetailPage.module.css'

const ALL_PERMISSIONS = [
  { value: 'crm.contacts.view_customers', label: 'CRM: Zobrazit zákazníky' },
  { value: 'crm.contacts.create_customer', label: 'CRM: Vytvořit zákazníka' },
  { value: 'crm.contacts.update_customer', label: 'CRM: Upravit zákazníka' },
  { value: 'identity.users.view', label: 'Identita: Zobrazit uživatele' },
  { value: 'identity.users.manage', label: 'Identita: Spravovat uživatele' },
  { value: 'identity.roles.manage', label: 'Identita: Spravovat role' },
]

export function RoleDetailPage() {
  const { roleId } = useParams({ from: '/identity/roles/$roleId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [permissions, setPermissions] = useState<string[]>([])

  const { data: role, isLoading } = useQuery({
    queryKey: ['role', roleId],
    queryFn: () => identityApi.getRole(roleId),
  })

  useEffect(() => {
    if (role) {
      setPermissions(role.permissions)
    }
  }, [role])

  const updateMutation = useMutation({
    mutationFn: () => identityApi.updateRolePermissions(roleId, permissions),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['role', roleId] })
      queryClient.invalidateQueries({ queryKey: ['roles'] })
    },
  })

  const togglePermission = (p: string) => {
    setPermissions((prev) =>
      prev.includes(p) ? prev.filter((x) => x !== p) : [...prev, p]
    )
  }

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/identity/roles" className={styles.breadcrumbLink}>Role</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{role?.name ?? '…'}</span>
        </nav>

        {isLoading ? (
          <p className={styles.loading}>Načítám…</p>
        ) : (
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); updateMutation.mutate() }}
          >
            <h1 className={styles.title}>{role?.name}</h1>

            <div className={styles.section}>
              <p className={styles.sectionTitle}>Oprávnění</p>
              <div className={styles.permissionsList}>
                {ALL_PERMISSIONS.map((p) => (
                  <label key={p.value} className={styles.permissionRow}>
                    <input
                      type="checkbox"
                      checked={permissions.includes(p.value)}
                      onChange={() => togglePermission(p.value)}
                    />
                    {p.label}
                  </label>
                ))}
              </div>
            </div>

            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={async () => { await navigate({ to: '/identity/roles' }) }}
              >
                Zpět
              </Button>
              <Button type="submit" loading={updateMutation.isPending}>
                Uložit oprávnění
              </Button>
            </div>
          </form>
        )}
      </div>
    </AppLayout>
  )
}
