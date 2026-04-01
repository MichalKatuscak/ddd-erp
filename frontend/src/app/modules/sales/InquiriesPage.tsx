import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Badge, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { salesApi, INQUIRY_STATUS_LABELS, type InquiryListItem } from '../../api/sales'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './InquiriesPage.module.css'

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  new: 'primary', in_progress: 'primary', quoted: 'primary',
  won: 'success', lost: 'danger', cancelled: 'danger',
}

export function InquiriesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [customerName, setCustomerName] = useState('')
  const [contactEmail, setContactEmail] = useState('')
  const [description, setDescription] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['sales-inquiries'],
    queryFn: () => salesApi.getInquiries(),
  })

  const createMutation = useMutation({
    mutationFn: () => salesApi.createInquiry({ customer_name: customerName, contact_email: contactEmail, description, required_roles: [] }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['sales-inquiries'] }); handleClose() },
  })

  const handleClose = () => { setOpen(false); setCustomerName(''); setContactEmail(''); setDescription(''); createMutation.reset() }

  const columns: Column<InquiryListItem>[] = [
    { key: 'customer_name', header: 'Zákazník', render: r => r.customer_name },
    { key: 'description', header: 'Popis', render: r => r.description.length > 60 ? r.description.slice(0, 60) + '…' : r.description },
    { key: 'status', header: 'Stav', render: r => <Badge label={INQUIRY_STATUS_LABELS[r.status] ?? r.status} variant={STATUS_VARIANTS[r.status] ?? 'primary'} /> },
    { key: 'created_at', header: 'Vytvořeno', render: r => new Date(r.created_at).toLocaleDateString('cs-CZ') },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Poptávky</h1>
          <Button onClick={() => setOpen(true)}>Nová poptávka</Button>
        </div>
        <Table columns={columns} data={data as (InquiryListItem & Record<string, unknown>)[]}
          loading={isLoading} rowKey={r => r.id}
          onRowClick={async r => navigate({ to: '/sales/inquiries/$inquiryId', params: { inquiryId: r.id } })} />
        <Modal open={open} onClose={handleClose} title="Nová poptávka">
          <form className={styles.form} onSubmit={e => { e.preventDefault(); createMutation.mutate() }}>
            {createMutation.isError && <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>Nepodařilo se vytvořit poptávku</p>}
            <FormField label="Zákazník" htmlFor="custName"><Input id="custName" value={customerName} onChange={e => setCustomerName(e.target.value)} placeholder="Název firmy" /></FormField>
            <FormField label="E-mail" htmlFor="custEmail"><Input id="custEmail" type="email" value={contactEmail} onChange={e => setContactEmail(e.target.value)} /></FormField>
            <FormField label="Popis" htmlFor="descr"><Input id="descr" value={description} onChange={e => setDescription(e.target.value)} /></FormField>
            <div className={styles.actions}>
              <Button variant="secondary" type="button" onClick={handleClose}>Zrušit</Button>
              <Button type="submit" loading={createMutation.isPending} disabled={!customerName.trim() || !contactEmail.trim() || !description.trim()}>Vytvořit</Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
