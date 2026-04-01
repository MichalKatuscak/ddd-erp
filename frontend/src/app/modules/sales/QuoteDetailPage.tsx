import { useState } from 'react'
import { useParams, useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, Badge, Modal, FormField, Input } from '../../../design-system'
import { salesApi, QUOTE_STATUS_LABELS, SALES_ROLE_LABELS, type SalesRole } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './QuoteDetailPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  draft: 'primary', sent: 'primary', accepted: 'success', rejected: 'danger',
}

export function QuoteDetailPage() {
  const { inquiryId, quoteId } = useParams({ from: '/sales/inquiries/$inquiryId/quotes/$quoteId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [addPhaseOpen, setAddPhaseOpen] = useState(false)
  const [phaseName, setPhaseName] = useState('')
  const [phaseRole, setPhaseRole] = useState<SalesRole>('backend')
  const [phaseDays, setPhaseDays] = useState(1)
  const [phaseDailyRate, setPhaseDailyRate] = useState(0)

  const { data: quote, isLoading } = useQuery({
    queryKey: ['sales-quote', quoteId],
    queryFn: () => salesApi.getQuote(inquiryId, quoteId),
  })

  const addPhaseMutation = useMutation({
    mutationFn: () => salesApi.addQuotePhase(inquiryId, quoteId, {
      name: phaseName, required_role: phaseRole,
      duration_days: phaseDays, daily_rate_amount: phaseDailyRate * 100, daily_rate_currency: 'CZK',
    }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }); setAddPhaseOpen(false); setPhaseName(''); setPhaseDays(1); setPhaseDailyRate(0) },
  })

  const sendMutation = useMutation({
    mutationFn: () => salesApi.sendQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const acceptMutation = useMutation({
    mutationFn: () => salesApi.acceptQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const rejectMutation = useMutation({
    mutationFn: () => salesApi.rejectQuote(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })
  const exportPdfMutation = useMutation({
    mutationFn: () => salesApi.exportQuotePdf(inquiryId, quoteId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-quote', quoteId] }),
  })

  if (isLoading || !quote) return <AppLayout><div style={{ padding: 32 }}>Načítám…</div></AppLayout>

  const totalCzk = (quote.total_price_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.breadcrumb}>
          <button className={styles.back} onClick={() => navigate({ to: '/sales/inquiries/$inquiryId', params: { inquiryId } })}>← Poptávka</button>
          <span> › Nabídka</span>
        </div>
        <div className={styles.header}>
          <h1 className={styles.title}>Nabídka</h1>
          <Badge label={QUOTE_STATUS_LABELS[quote.status] ?? quote.status} variant={STATUS_VARIANTS[quote.status] ?? 'primary'} />
          <div className={styles.actions}>
            {quote.status === 'draft' && <Button size="sm" onClick={() => sendMutation.mutate()} loading={sendMutation.isPending}>Odeslat zákazníkovi</Button>}
            {quote.status === 'sent' && <Button size="sm" onClick={() => acceptMutation.mutate()} loading={acceptMutation.isPending}>Přijmout</Button>}
            {quote.status === 'sent' && <Button size="sm" variant="secondary" onClick={() => rejectMutation.mutate()} loading={rejectMutation.isPending}>Odmítnout</Button>}
            <Button size="sm" variant="secondary" onClick={() => exportPdfMutation.mutate()} loading={exportPdfMutation.isPending}>Generovat PDF</Button>
            {quote.pdf_path && <a href={salesApi.getQuotePdfUrl(inquiryId, quoteId)} target="_blank" rel="noreferrer"><Button size="sm" variant="secondary">Stáhnout PDF</Button></a>}
          </div>
        </div>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Fáze nabídky</h2>
            {quote.status === 'draft' && <Button size="sm" onClick={() => setAddPhaseOpen(true)}>Přidat fázi</Button>}
          </div>
          {quote.phases.length === 0 && <p className={styles.empty}>Žádné fáze. Přidejte první fázi.</p>}
          <table className={styles.table}>
            <thead><tr><th>Název</th><th>Role</th><th>Dny</th><th>Sazba/den</th><th>Mezisoučet</th></tr></thead>
            <tbody>
              {quote.phases.map(p => (
                <tr key={p.id}>
                  <td>{p.name}</td>
                  <td>{SALES_ROLE_LABELS[p.required_role as SalesRole] ?? p.required_role}</td>
                  <td>{p.duration_days}</td>
                  <td>{(p.daily_rate_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })} {p.daily_rate_currency}</td>
                  <td>{(p.subtotal_amount / 100).toLocaleString('cs-CZ', { minimumFractionDigits: 2 })} {p.subtotal_currency}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className={styles.total}>Celkem: <strong>{totalCzk} {quote.total_price_currency}</strong></div>
        </section>

        {quote.notes && (
          <section className={styles.section}>
            <h2 className={styles.sectionTitle}>Poznámky</h2>
            <p>{quote.notes}</p>
          </section>
        )}
      </div>

      <Modal open={addPhaseOpen} onClose={() => setAddPhaseOpen(false)} title="Přidat fázi">
        <form className={styles.form} onSubmit={e => { e.preventDefault(); addPhaseMutation.mutate() }}>
          <FormField label="Název fáze" htmlFor="pname"><Input id="pname" value={phaseName} onChange={e => setPhaseName(e.target.value)} /></FormField>
          <FormField label="Role" htmlFor="prole">
            <select id="prole" value={phaseRole} onChange={e => setPhaseRole(e.target.value as SalesRole)} className={styles.select}>
              {Object.entries(SALES_ROLE_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </FormField>
          <FormField label="Počet dní" htmlFor="pdays"><Input id="pdays" type="number" value={String(phaseDays)} onChange={e => setPhaseDays(Number(e.target.value))} /></FormField>
          <FormField label="Sazba / den (CZK)" htmlFor="prate"><Input id="prate" type="number" value={String(phaseDailyRate)} onChange={e => setPhaseDailyRate(Number(e.target.value))} /></FormField>
          <div className={styles.actions}>
            <Button variant="secondary" type="button" onClick={() => setAddPhaseOpen(false)}>Zrušit</Button>
            <Button type="submit" loading={addPhaseMutation.isPending} disabled={!phaseName.trim()}>Přidat</Button>
          </div>
        </form>
      </Modal>
    </AppLayout>
  )
}
