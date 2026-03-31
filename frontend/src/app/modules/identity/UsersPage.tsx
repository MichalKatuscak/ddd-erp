import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Badge, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { identityApi } from '../../api/identity'
import type { UserListItem } from '../../api/identity'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './UsersPage.module.css'

export function UsersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')

  const { data: users = [], isLoading } = useQuery({
    queryKey: ['users'],
    queryFn: () => identityApi.getUsers(),
  })

  const { data: roles = [] } = useQuery({
    queryKey: ['roles'],
    queryFn: () => identityApi.getRoles(),
  })

  const registerMutation = useMutation({
    mutationFn: () => identityApi.registerUser({ email, password, firstName, lastName }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      handleClose()
    },
  })

  const handleClose = () => {
    setOpen(false)
    setEmail('')
    setPassword('')
    setFirstName('')
    setLastName('')
    registerMutation.reset()
  }

  const columns: Column<UserListItem>[] = [
    { key: 'name', header: 'Jméno', render: (row) => row.name },
    { key: 'email', header: 'E-mail', render: (row) => row.email },
    {
      key: 'role_ids',
      header: 'Role',
      render: (row) => (
        <div className={styles.roleBadges}>
          {row.role_ids.map((id) => {
            const role = roles.find((r) => r.id === id)
            return <Badge key={id} label={role?.name ?? id} variant="primary" />
          })}
        </div>
      ),
    },
    {
      key: 'active',
      header: 'Stav',
      render: (row) => (
        <Badge
          label={row.active ? 'Aktivní' : 'Neaktivní'}
          variant={row.active ? 'success' : 'danger'}
        />
      ),
    },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Uživatelé</h1>
          <Button onClick={() => setOpen(true)}>Přidat uživatele</Button>
        </div>
        <Table
          columns={columns}
          data={users as (UserListItem & Record<string, unknown>)[]}
          loading={isLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/identity/users/$userId', params: { userId: row.id } })
          }}
        />
        <Modal open={open} onClose={handleClose} title="Přidat uživatele">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); registerMutation.mutate() }}
          >
            <FormField label="Jméno" htmlFor="regFirstName">
              <Input
                id="regFirstName"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                placeholder="Jan"
              />
            </FormField>
            <FormField label="Příjmení" htmlFor="regLastName">
              <Input
                id="regLastName"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                placeholder="Novák"
              />
            </FormField>
            <FormField label="E-mail" htmlFor="regEmail">
              <Input
                id="regEmail"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="jan@firma.cz"
              />
            </FormField>
            <FormField
              label="Heslo"
              htmlFor="regPassword"
              error={registerMutation.isError ? 'Nepodařilo se vytvořit uživatele' : undefined}
            >
              <Input
                id="regPassword"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                error={registerMutation.isError}
              />
            </FormField>
            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={handleClose}
              >
                Zrušit
              </Button>
              <Button
                type="submit"
                loading={registerMutation.isPending}
                disabled={!email.trim() || !password.trim() || !firstName.trim() || !lastName.trim()}
              >
                Vytvořit
              </Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
