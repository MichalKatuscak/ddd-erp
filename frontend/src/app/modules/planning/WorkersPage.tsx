import { useState, useMemo } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Badge, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { planningApi } from '../../api/planning'
import type { WorkerListItem, WorkerRole } from '../../api/planning'
import { WORKER_ROLE_LABELS } from '../../api/planning'
import { identityApi } from '../../api/identity'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './WorkersPage.module.css'

const ROLES: WorkerRole[] = ['designer', 'frontend', 'backend', 'pm', 'qa', 'devops']

export function WorkersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [selectedUserId, setSelectedUserId] = useState('')
  const [primaryRole, setPrimaryRole] = useState<WorkerRole>('frontend')
  const [skills, setSkills] = useState('')

  const { data: workers = [], isLoading: workersLoading } = useQuery({
    queryKey: ['planning-workers'],
    queryFn: () => planningApi.getWorkers(),
  })

  const { data: allUsers = [] } = useQuery({
    queryKey: ['users'],
    queryFn: () => identityApi.getUsers(),
  })

  // Users not yet registered as workers
  const workerIds = useMemo(() => new Set(workers.map(w => w.id)), [workers])
  const availableUsers = useMemo(
    () => allUsers.filter(u => !workerIds.has(u.id)),
    [allUsers, workerIds]
  )

  const registerMutation = useMutation({
    mutationFn: () => planningApi.registerWorker({
      userId: selectedUserId,
      primaryRole,
      skills: skills.split(',').map(s => s.trim()).filter(Boolean),
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-workers'] })
      handleClose()
    },
  })

  const handleClose = () => {
    setOpen(false)
    setSelectedUserId('')
    setPrimaryRole('frontend')
    setSkills('')
    registerMutation.reset()
  }

  const columns: Column<WorkerListItem>[] = [
    { key: 'name', header: 'Jméno', render: (row) => row.name },
    {
      key: 'primary_role',
      header: 'Role',
      render: (row) => <Badge label={WORKER_ROLE_LABELS[row.primary_role]} variant="primary" />,
    },
    {
      key: 'skills',
      header: 'Dovednosti',
      render: (row) => (
        <div className={styles.skillBadges}>
          {row.skills.map(skill => <Badge key={skill} label={skill} variant="primary" />)}
        </div>
      ),
    },
    {
      key: 'current_allocation_percent',
      header: 'Alokace',
      render: (row) => `${row.current_allocation_percent}%`,
    },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Pracovníci</h1>
          <Button onClick={() => setOpen(true)}>Přidat pracovníka</Button>
        </div>
        <Table
          columns={columns}
          data={workers as (WorkerListItem & Record<string, unknown>)[]}
          loading={workersLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/planning/workers/$workerId', params: { workerId: row.id } })
          }}
        />
        <Modal open={open} onClose={handleClose} title="Přidat pracovníka">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); registerMutation.mutate() }}
          >
            {registerMutation.isError && (
              <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>
                Nepodařilo se přidat pracovníka
              </p>
            )}
            <FormField label="Uživatel" htmlFor="workerUserId">
              <select
                id="workerUserId"
                value={selectedUserId}
                onChange={(e) => setSelectedUserId(e.target.value)}
                style={{ width: '100%', padding: 'var(--space-2)', borderRadius: 'var(--radius-sm)', border: '1px solid var(--color-neutral-300)' }}
              >
                <option value="">— vyberte uživatele —</option>
                {availableUsers.map(u => (
                  <option key={u.id} value={u.id}>{u.name}</option>
                ))}
              </select>
            </FormField>
            <FormField label="Primární role" htmlFor="workerRole">
              <select
                id="workerRole"
                value={primaryRole}
                onChange={(e) => setPrimaryRole(e.target.value as WorkerRole)}
                style={{ width: '100%', padding: 'var(--space-2)', borderRadius: 'var(--radius-sm)', border: '1px solid var(--color-neutral-300)' }}
              >
                {ROLES.map(role => (
                  <option key={role} value={role}>{WORKER_ROLE_LABELS[role]}</option>
                ))}
              </select>
            </FormField>
            <FormField label="Dovednosti (oddělené čárkou)" htmlFor="workerSkills">
              <Input
                id="workerSkills"
                value={skills}
                onChange={(e) => setSkills(e.target.value)}
                placeholder="např. React, TypeScript"
              />
            </FormField>
            <div className={styles.actions}>
              <Button variant="secondary" type="button" onClick={handleClose}>Zrušit</Button>
              <Button
                type="submit"
                loading={registerMutation.isPending}
                disabled={!selectedUserId}
              >
                Přidat
              </Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
