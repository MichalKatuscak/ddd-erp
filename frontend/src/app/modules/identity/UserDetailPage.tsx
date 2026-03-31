import { useState, useEffect, useMemo } from 'react'
import { useParams, useNavigate, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, FormField, Input } from '../../../design-system'
import { identityApi } from '../../api/identity'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './UserDetailPage.module.css'

export function UserDetailPage() {
  const { userId } = useParams({ from: '/identity/users/$userId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [email, setEmail] = useState('')
  const [selectedRoleIds, setSelectedRoleIds] = useState<string[]>([])

  const { data: user, isLoading, isError } = useQuery({
    queryKey: ['user', userId],
    queryFn: () => identityApi.getUser(userId),
  })

  const { data: roles = [] } = useQuery({
    queryKey: ['roles'],
    queryFn: () => identityApi.getRoles(),
  })

  useEffect(() => {
    if (user) {
      setFirstName(user.first_name)
      setLastName(user.last_name)
      setEmail(user.email)
      setSelectedRoleIds(user.role_ids)
    }
  }, [user])

  const updateMutation = useMutation({
    mutationFn: () => identityApi.updateUser(userId, { email, firstName, lastName }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user', userId] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })

  const assignRolesMutation = useMutation({
    mutationFn: () => identityApi.assignRoles(userId, selectedRoleIds),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user', userId] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })

  const deactivateMutation = useMutation({
    mutationFn: () => identityApi.deactivateUser(userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user', userId] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })

  const isDirtyRoles = useMemo(() => {
    const original = user?.role_ids ?? []
    return (
      selectedRoleIds.length !== original.length ||
      selectedRoleIds.some((id) => !original.includes(id))
    )
  }, [selectedRoleIds, user?.role_ids])

  const toggleRole = (roleId: string) => {
    setSelectedRoleIds((prev) =>
      prev.includes(roleId) ? prev.filter((id) => id !== roleId) : [...prev, roleId]
    )
  }

  const handleDeactivate = () => {
    if (window.confirm('Opravdu chcete deaktivovat tohoto uživatele? Tato akce je nevratná.')) {
      deactivateMutation.mutate()
    }
  }

  const fullName = user ? `${user.first_name} ${user.last_name}` : '…'

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/identity/users" className={styles.breadcrumbLink}>Uživatelé</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{fullName}</span>
        </nav>

        {isLoading ? (
          <p className={styles.loading}>Načítám…</p>
        ) : isError ? (
          <p className={styles.loading}>Nepodařilo se načíst uživatele.</p>
        ) : (
          <>
            <h1 className={styles.title}>{fullName}</h1>

            <div className={styles.sections}>
              <form
                className={styles.section}
                onSubmit={(e) => { e.preventDefault(); updateMutation.mutate() }}
              >
                <p className={styles.sectionTitle}>Osobní údaje</p>
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
                    onClick={() => navigate({ to: '/identity/users' })}
                  >
                    Zpět
                  </Button>
                  <Button type="submit" loading={updateMutation.isPending}>
                    Uložit údaje
                  </Button>
                </div>
              </form>

              <form
                className={styles.section}
                onSubmit={(e) => { e.preventDefault(); assignRolesMutation.mutate() }}
              >
                <p className={styles.sectionTitle}>Role</p>
                {assignRolesMutation.isError && (
                  <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>
                    Nepodařilo se uložit role
                  </p>
                )}
                <div className={styles.rolesList}>
                  {roles.map((role) => (
                    <label key={role.id} className={styles.roleRow}>
                      <input
                        type="checkbox"
                        checked={selectedRoleIds.includes(role.id)}
                        onChange={() => toggleRole(role.id)}
                      />
                      {role.name}
                    </label>
                  ))}
                </div>
                <div className={styles.actions}>
                  <Button
                    type="submit"
                    loading={assignRolesMutation.isPending}
                    disabled={!isDirtyRoles}
                  >
                    Uložit role
                  </Button>
                </div>
              </form>

              {user?.active && (
                <div className={styles.dangerZone}>
                  <p className={styles.dangerTitle}>Nebezpečná zóna</p>
                  <p className={styles.dangerText}>
                    Deaktivovaný uživatel se nemůže přihlásit. Akci nelze vrátit zpět.
                  </p>
                  <div>
                    <Button
                      variant="danger"
                      type="button"
                      loading={deactivateMutation.isPending}
                      onClick={handleDeactivate}
                    >
                      Deaktivovat uživatele
                    </Button>
                  </div>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </AppLayout>
  )
}
