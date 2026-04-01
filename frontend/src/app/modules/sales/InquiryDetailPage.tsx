import { useRef, useState } from 'react'
import { useParams, useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, Badge, Modal, FormField, Input } from '../../../design-system'
import { salesApi, INQUIRY_STATUS_LABELS } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './InquiryDetailPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  new: 'primary', in_progress: 'primary', quoted: 'primary',
  won: 'success', lost: 'danger', cancelled: 'danger',
}

export function InquiryDetailPage() {
  const { inquiryId } = useParams({ from: '/sales/inquiries/$inquiryId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [quoteOpen, setQuoteOpen] = useState(false)
  const [validUntil, setValidUntil] = useState('')
  const [notes, setNotes] = useState('')

  const { data: inquiry, isLoading } = useQuery({
    queryKey: ['sales-inquiry', inquiryId],
    queryFn: () => salesApi.getInquiry(inquiryId),
  })

  const advanceMutation = useMutation({
    mutationFn: (target?: string) => salesApi.advanceInquiryStatus(inquiryId, target),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-inquiry', inquiryId] }),
  })

  const uploadMutation = useMutation({
    mutationFn: (file: File) => salesApi.uploadAttachment(inquiryId, file),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales-inquiry', inquiryId] }),
  })

  const createQuoteMutation = useMutation({
    mutationFn: () => salesApi.createQuote(inquiryId, { valid_until: validUntil, notes }),
    onSuccess: async data => {
      setQuoteOpen(false)
      await navigate({ to: '/sales/inquiries/$inquiryId/quotes/$quoteId', params: { inquiryId, quoteId: data.id } })
    },
  })

  if (isLoading || !inquiry) return <AppLayout><div style={{ padding: 32 }}>Načítám…</div></AppLayout>

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.breadcrumb}>
          <button className={styles.back} onClick={() => navigate({ to: '/sales/inquiries' })}>← Poptávky</button>
          <span> › {inquiry.customer_name}</span>
        </div>
        <div className={styles.header}>
          <h1 className={styles.title}>{inquiry.customer_name}</h1>
          <Badge label={INQUIRY_STATUS_LABELS[inquiry.status] ?? inquiry.status} variant={STATUS_VARIANTS[inquiry.status] ?? 'primary'} />
          {['new', 'in_progress'].includes(inquiry.status) && (
            <Button size="sm" onClick={() => advanceMutation.mutate(undefined)} loading={advanceMutation.isPending}>Posunout stav</Button>
          )}
        </div>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Požadavky</h2>
          <p className={styles.label}>E-mail: <span>{inquiry.contact_email}</span></p>
          <p className={styles.label}>Popis: <span>{inquiry.description}</span></p>
          {inquiry.requested_deadline && <p className={styles.label}>Termín: <span>{inquiry.requested_deadline}</span></p>}
          {inquiry.required_roles.length > 0 && (
            <div className={styles.tags}>
              {inquiry.required_roles.map((r, i) => (
                <span key={i} className={styles.tag}>{r.role}{r.skills.length > 0 ? ` (${r.skills.join(', ')})` : ''}</span>
              ))}
            </div>
          )}
        </section>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Přílohy</h2>
            <Button size="sm" variant="secondary" onClick={() => fileInputRef.current?.click()}>Nahrát</Button>
            <input ref={fileInputRef} type="file" hidden accept=".pdf,.png,.jpg,.jpeg,.webp"
              onChange={e => { const f = e.target.files?.[0]; if (f) uploadMutation.mutate(f) }} />
          </div>
          {inquiry.attachments.length === 0 && <p className={styles.empty}>Žádné přílohy</p>}
          {inquiry.attachments.map(a => (
            <div key={a.id} className={styles.attachment}>
              <a href={salesApi.getAttachmentUrl(a.path)} target="_blank" rel="noreferrer">{a.originalName}</a>
              {a.mimeType.startsWith('image/') && (
                <img src={salesApi.getAttachmentUrl(a.path)} alt={a.originalName} className={styles.preview} />
              )}
              {a.mimeType === 'application/pdf' && (
                <iframe src={salesApi.getAttachmentUrl(a.path)} title={a.originalName} className={styles.pdfPreview} />
              )}
            </div>
          ))}
        </section>

        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Nabídky</h2>
            {inquiry.status !== 'won' && inquiry.status !== 'lost' && inquiry.status !== 'cancelled' && (
              <Button size="sm" onClick={() => setQuoteOpen(true)}>Vytvořit nabídku</Button>
            )}
          </div>
          <p className={styles.empty}>Pro zobrazení nabídek přejděte na URL nabídky nebo použijte odkaz v e-mailu.</p>
        </section>
      </div>

      <Modal open={quoteOpen} onClose={() => setQuoteOpen(false)} title="Vytvořit nabídku">
        <form className={styles.form} onSubmit={e => { e.preventDefault(); createQuoteMutation.mutate() }}>
          <FormField label="Platná do" htmlFor="validUntil"><input id="validUntil" type="date" value={validUntil} onChange={e => setValidUntil(e.target.value)} style={{ width: '100%', padding: 'var(--spacing-2)', border: '1px solid var(--color-neutral-300)', borderRadius: 4, fontSize: 'var(--font-size-sm)' }} /></FormField>
          <FormField label="Poznámky" htmlFor="qnotes"><Input id="qnotes" value={notes} onChange={e => setNotes(e.target.value)} /></FormField>
          <div className={styles.actions}>
            <Button variant="secondary" type="button" onClick={() => setQuoteOpen(false)}>Zrušit</Button>
            <Button type="submit" loading={createQuoteMutation.isPending} disabled={!validUntil}>Vytvořit</Button>
          </div>
        </form>
      </Modal>
    </AppLayout>
  )
}
