import { useState, useEffect } from 'react'
import { useParams, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, FormField, Input } from '../../../design-system'
import { planningApi } from '../../api/planning'
import type { WorkerRole } from '../../api/planning'
import { WORKER_ROLE_LABELS } from '../../api/planning'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './WorkerDetailPage.module.css'

const ROLES: WorkerRole[] = ['designer', 'frontend', 'backend', 'pm', 'qa', 'devops']

export function WorkerDetailPage() {
  const { workerId } = useParams({ from: '/planning/workers/$workerId' })
  const queryClient = useQueryClient()

  const [primaryRole, setPrimaryRole] = useState<WorkerRole>('frontend')
  const [skills, setSkills] = useState('')

  const { data: worker, isLoading } = useQuery({
    queryKey: ['planning-worker', workerId],
    queryFn: () => planningApi.getWorker(workerId),
  })

  useEffect(() => {
    if (worker) {
      setPrimaryRole(worker.primary_role)
      setSkills(worker.skills.join(', '))
    }
  }, [worker])

  const updateMutation = useMutation({
    mutationFn: () => planningApi.updateWorkerSkills(workerId, {
      primaryRole,
      skills: skills.split(',').map(s => s.trim()).filter(Boolean),
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-worker', workerId] })
      queryClient.invalidateQueries({ queryKey: ['planning-workers'] })
    },
  })

  if (isLoading) {
    return (
      <AppLayout>
        <p className={styles.loading}>Načítám…</p>
      </AppLayout>
    )
  }

  if (!worker) return null

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/planning/workers" className={styles.breadcrumbLink}>Pracovníci</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{worker.name}</span>
        </nav>

        <h1 className={styles.title}>{worker.name}</h1>

        <div>
          <h2 className={styles.sectionTitle}>Kompetence</h2>
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); updateMutation.mutate() }}
          >
            <FormField label="Primární role" htmlFor="detRole">
              <select
                id="detRole"
                value={primaryRole}
                onChange={(e) => setPrimaryRole(e.target.value as WorkerRole)}
                style={{ width: '100%', padding: 'var(--space-2)', borderRadius: 'var(--radius-sm)', border: '1px solid var(--color-neutral-300)' }}
              >
                {ROLES.map(role => (
                  <option key={role} value={role}>{WORKER_ROLE_LABELS[role]}</option>
                ))}
              </select>
            </FormField>
            <FormField
              label="Dovednosti (oddělené čárkou)"
              htmlFor="detSkills"
              error={updateMutation.isError ? 'Nepodařilo se uložit změny' : undefined}
            >
              <Input
                id="detSkills"
                value={skills}
                onChange={(e) => setSkills(e.target.value)}
                placeholder="např. React, TypeScript"
                error={updateMutation.isError}
              />
            </FormField>
            <div className={styles.actions}>
              <Button type="submit" loading={updateMutation.isPending}>
                Uložit
              </Button>
            </div>
          </form>
        </div>

        <div>
          <h2 className={styles.sectionTitle}>Alokace</h2>
          {worker.allocations.length === 0 ? (
            <p className={styles.loading}>Žádné aktivní alokace.</p>
          ) : (
            <table className={styles.allocTable}>
              <thead>
                <tr>
                  <th>Zakázka</th>
                  <th>Fáze</th>
                  <th>Alokace</th>
                  <th>Od</th>
                  <th>Do</th>
                </tr>
              </thead>
              <tbody>
                {worker.allocations.map((alloc, i) => (
                  <tr key={i}>
                    <td>{alloc.order_name}</td>
                    <td>{alloc.phase_name}</td>
                    <td>{alloc.allocation_percent}%</td>
                    <td>{alloc.start_date}</td>
                    <td>{alloc.end_date}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </AppLayout>
  )
}
