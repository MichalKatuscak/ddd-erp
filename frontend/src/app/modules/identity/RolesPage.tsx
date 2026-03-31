import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { identityApi } from '../../api/identity'
import type { RoleListItem } from '../../api/identity'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './RolesPage.module.css'

export function RolesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [name, setName] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['roles'],
    queryFn: () => identityApi.getRoles(),
  })

  const createMutation = useMutation({
    mutationFn: () => identityApi.createRole(name, []),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['roles'] })
      handleClose()
    },
  })

  const handleClose = () => {
    setOpen(false)
    setName('')
    createMutation.reset()
  }

  const columns: Column<RoleListItem>[] = [
    { key: 'name', header: 'Název', render: (row) => row.name },
    {
      key: 'permissions',
      header: 'Oprávnění',
      render: (row) => String(row.permissions.length),
    },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Role</h1>
          <Button onClick={() => setOpen(true)}>Vytvořit roli</Button>
        </div>
        <Table
          columns={columns}
          data={data as (RoleListItem & Record<string, unknown>)[]}
          loading={isLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/identity/roles/$roleId', params: { roleId: row.id } })
          }}
        />
        <Modal open={open} onClose={handleClose} title="Vytvořit roli">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); createMutation.mutate() }}
          >
            <FormField
              label="Název"
              htmlFor="roleName"
              error={createMutation.isError ? 'Nepodařilo se vytvořit roli' : undefined}
            >
              <Input
                id="roleName"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Správce zákazníků"
                error={createMutation.isError}
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
              <Button type="submit" loading={createMutation.isPending} disabled={!name.trim()}>
                Vytvořit
              </Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
