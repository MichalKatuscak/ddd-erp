import { useState } from 'react'
import { useParams, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, Badge, Modal, FormField, Input } from '../../../design-system'
import { planningApi } from '../../api/planning'
import type { PhaseDetail, CandidateDTO, WorkerRole } from '../../api/planning'
import { WORKER_ROLE_LABELS } from '../../api/planning'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './OrderDetailPage.module.css'

const STATUS_LABELS: Record<string, string> = {
  new: 'Nová',
  confirmed: 'Potvrzená',
  in_progress: 'Ve výrobě',
  completed: 'Dokončená',
  shipped: 'Expedovaná',
}

const ROLES: WorkerRole[] = ['designer', 'frontend', 'backend', 'pm', 'qa', 'devops']

export function OrderDetailPage() {
  const { orderId } = useParams({ from: '/planning/orders/$orderId' })
  const queryClient = useQueryClient()

  // Add phase modal state
  const [addPhaseOpen, setAddPhaseOpen] = useState(false)
  const [phaseName, setPhaseName] = useState('')
  const [requiredRole, setRequiredRole] = useState<WorkerRole>('frontend')
  const [requiredSkills, setRequiredSkills] = useState('')
  const [headcount, setHeadcount] = useState('1')
  const [durationDays, setDurationDays] = useState('5')
  const [dependsOn, setDependsOn] = useState<string[]>([])

  // Suggestions modal state
  const [suggestPhase, setSuggestPhase] = useState<PhaseDetail | null>(null)
  const [assignLoading, setAssignLoading] = useState<string | null>(null)

  const { data: order, isLoading } = useQuery({
    queryKey: ['planning-order', orderId],
    queryFn: () => planningApi.getOrder(orderId),
  })

  const { data: candidates = [], isLoading: candidatesLoading } = useQuery({
    queryKey: ['planning-suggestions', orderId, suggestPhase?.id],
    queryFn: () => planningApi.getSuggestions(orderId, suggestPhase!.id),
    enabled: suggestPhase !== null,
  })

  const addPhaseMutation = useMutation({
    mutationFn: () => planningApi.addPhase(orderId, {
      name: phaseName,
      requiredRole,
      requiredSkills: requiredSkills.split(',').map(s => s.trim()).filter(Boolean),
      headcount: parseInt(headcount, 10),
      durationDays: parseInt(durationDays, 10),
      dependsOn,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-order', orderId] })
      handleAddPhaseClose()
    },
  })

  const scheduleMutation = useMutation({
    mutationFn: () => planningApi.scheduleOrder(orderId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-order', orderId] })
    },
  })

  const advanceStatusMutation = useMutation({
    mutationFn: () => planningApi.advanceStatus(orderId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-order', orderId] })
      queryClient.invalidateQueries({ queryKey: ['planning-orders'] })
    },
  })

  const handleAddPhaseClose = () => {
    setAddPhaseOpen(false)
    setPhaseName('')
    setRequiredRole('frontend')
    setRequiredSkills('')
    setHeadcount('1')
    setDurationDays('5')
    setDependsOn([])
    addPhaseMutation.reset()
  }

  const handleAssign = async (candidate: CandidateDTO) => {
    if (!suggestPhase) return
    setAssignLoading(candidate.id)
    try {
      await planningApi.assignWorker(orderId, suggestPhase.id, {
        userId: candidate.id,
        allocationPercent: Math.min(candidate.available_percent, 100),
      })
      queryClient.invalidateQueries({ queryKey: ['planning-order', orderId] })
      setSuggestPhase(null)
    } finally {
      setAssignLoading(null)
    }
  }

  const handleScheduleAndSuggest = async (phase: PhaseDetail) => {
    await scheduleMutation.mutateAsync()
    setSuggestPhase(phase)
  }

  const toggleDependsOn = (phaseId: string) => {
    setDependsOn(prev =>
      prev.includes(phaseId) ? prev.filter(id => id !== phaseId) : [...prev, phaseId]
    )
  }

  if (isLoading) {
    return (
      <AppLayout>
        <p className={styles.loading}>Načítám…</p>
      </AppLayout>
    )
  }

  if (!order) return null

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/planning/orders" className={styles.breadcrumbLink}>Zakázky</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{order.name}</span>
        </nav>

        <div className={styles.orderHeader}>
          <div>
            <h1 className={styles.orderTitle}>{order.name}</h1>
            <p className={styles.orderMeta}>
              {order.client_name} · zahájení {order.planned_start_date}
            </p>
          </div>
          <div className={styles.orderActions}>
            <Badge
              label={STATUS_LABELS[order.status] ?? order.status}
              variant="primary"
            />
            {order.status !== 'shipped' && (
              <Button
                variant="secondary"
                loading={advanceStatusMutation.isPending}
                onClick={() => advanceStatusMutation.mutate()}
              >
                Posunout stav
              </Button>
            )}
            <Button
              variant="secondary"
              loading={scheduleMutation.isPending}
              onClick={() => scheduleMutation.mutate()}
            >
              Naplánovat
            </Button>
          </div>
        </div>

        <div>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Fáze</h2>
            <Button onClick={() => setAddPhaseOpen(true)}>Přidat fázi</Button>
          </div>

          <div className={styles.phaseList}>
            {order.phases.map((phase) => (
              <div key={phase.id} className={styles.phaseCard}>
                <div className={styles.phaseRow}>
                  <span className={styles.phaseName}>{phase.name}</span>
                  <Badge label={WORKER_ROLE_LABELS[phase.required_role]} variant="primary" />
                  <span className={styles.phaseMeta}>
                    {phase.headcount} {phase.headcount === 1 ? 'pracovník' : 'pracovníci'} · {phase.duration_days} dní
                  </span>
                  {phase.start_date && phase.end_date && (
                    <span className={styles.phaseMeta}>
                      {phase.start_date} – {phase.end_date}
                    </span>
                  )}
                </div>
                {phase.required_skills.length > 0 && (
                  <div className={styles.skillBadges}>
                    {phase.required_skills.map((skill) => (
                      <Badge key={skill} label={skill} variant="primary" />
                    ))}
                  </div>
                )}
                <div className={styles.phaseFooter}>
                  <span className={styles.assignedWorkers}>
                    {phase.assignments.length}/{phase.headcount} přiřazeno
                  </span>
                  {phase.assignments.length < phase.headcount && (
                    <Button
                      variant="secondary"
                      onClick={() => handleScheduleAndSuggest(phase)}
                      loading={scheduleMutation.isPending}
                    >
                      Přiřadit pracovníka
                    </Button>
                  )}
                </div>
              </div>
            ))}
            {order.phases.length === 0 && (
              <p className={styles.phaseMeta}>Žádné fáze. Přidejte první fázi zakázky.</p>
            )}
          </div>
        </div>

        {/* Add phase modal */}
        <Modal open={addPhaseOpen} onClose={handleAddPhaseClose} title="Přidat fázi">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); addPhaseMutation.mutate() }}
          >
            {addPhaseMutation.isError && (
              <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>
                Nepodařilo se přidat fázi
              </p>
            )}
            <FormField label="Název fáze" htmlFor="phaseName">
              <Input
                id="phaseName"
                value={phaseName}
                onChange={(e) => setPhaseName(e.target.value)}
                placeholder="např. Design UI"
              />
            </FormField>
            <FormField label="Požadovaná role" htmlFor="phaseRole">
              <select
                id="phaseRole"
                value={requiredRole}
                onChange={(e) => setRequiredRole(e.target.value as WorkerRole)}
                style={{ width: '100%', padding: 'var(--space-2)', borderRadius: 'var(--radius-sm)', border: '1px solid var(--color-neutral-300)' }}
              >
                {ROLES.map((role) => (
                  <option key={role} value={role}>{WORKER_ROLE_LABELS[role]}</option>
                ))}
              </select>
            </FormField>
            <FormField label="Dovednosti (oddělené čárkou)" htmlFor="phaseSkills">
              <Input
                id="phaseSkills"
                value={requiredSkills}
                onChange={(e) => setRequiredSkills(e.target.value)}
                placeholder="např. React, TypeScript"
              />
            </FormField>
            <FormField label="Počet pracovníků" htmlFor="phaseHeadcount">
              <input
                id="phaseHeadcount"
                type="number"
                min="1"
                value={headcount}
                onChange={(e) => setHeadcount(e.target.value)}
                style={{ width: '100%', padding: 'var(--spacing-2)', border: '1px solid var(--color-neutral-300)', borderRadius: 4, fontSize: 'var(--font-size-sm)' }}
              />
            </FormField>
            <FormField label="Délka (dní)" htmlFor="phaseDuration">
              <input
                id="phaseDuration"
                type="number"
                min="1"
                value={durationDays}
                onChange={(e) => setDurationDays(e.target.value)}
                style={{ width: '100%', padding: 'var(--spacing-2)', border: '1px solid var(--color-neutral-300)', borderRadius: 4, fontSize: 'var(--font-size-sm)' }}
              />
            </FormField>
            {order.phases.length > 0 && (
              <FormField label="Závisí na fázích" htmlFor="phaseDeps">
                <div className={styles.checkboxGroup}>
                  {order.phases.map((p) => (
                    <label key={p.id} className={styles.checkboxLabel}>
                      <input
                        type="checkbox"
                        checked={dependsOn.includes(p.id)}
                        onChange={() => toggleDependsOn(p.id)}
                      />
                      {p.name}
                    </label>
                  ))}
                </div>
              </FormField>
            )}
            <div className={styles.actions}>
              <Button variant="secondary" type="button" onClick={handleAddPhaseClose}>Zrušit</Button>
              <Button
                type="submit"
                loading={addPhaseMutation.isPending}
                disabled={!phaseName.trim()}
              >
                Přidat
              </Button>
            </div>
          </form>
        </Modal>

        {/* Suggestions modal */}
        <Modal
          open={suggestPhase !== null}
          onClose={() => setSuggestPhase(null)}
          title={`Přiřadit pracovníka — ${suggestPhase?.name ?? ''}`}
        >
          {candidatesLoading ? (
            <p className={styles.loading}>Načítám kandidáty…</p>
          ) : candidates.length === 0 ? (
            <p className={styles.loading}>Žádní dostupní kandidáti pro tuto fázi.</p>
          ) : (
            <div className={styles.candidateList}>
              {candidates.map((candidate) => (
                <div key={candidate.id} className={styles.candidateCard}>
                  <div className={styles.candidateInfo}>
                    <span className={styles.candidateName}>{candidate.name}</span>
                    <span className={styles.candidateMeta}>
                      {WORKER_ROLE_LABELS[candidate.primary_role]} · dostupná kapacita: {candidate.available_percent}%
                    </span>
                    <div className={styles.skillBadges}>
                      {candidate.skills.map((skill) => (
                        <Badge key={skill} label={skill} variant="primary" />
                      ))}
                    </div>
                  </div>
                  <Button
                    loading={assignLoading === candidate.id}
                    onClick={() => handleAssign(candidate)}
                  >
                    Přiřadit
                  </Button>
                </div>
              ))}
            </div>
          )}
        </Modal>
      </div>
    </AppLayout>
  )
}
