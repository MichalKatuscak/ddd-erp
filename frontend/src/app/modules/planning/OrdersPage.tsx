import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Badge, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { planningApi } from '../../api/planning'
import type { OrderListItem } from '../../api/planning'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './OrdersPage.module.css'

const STATUS_LABELS: Record<string, string> = {
  new: 'Nová',
  confirmed: 'Potvrzená',
  in_progress: 'Ve výrobě',
  completed: 'Dokončená',
  shipped: 'Expedovaná',
}

const STATUS_VARIANTS: Record<string, 'primary' | 'success' | 'danger'> = {
  new: 'primary',
  confirmed: 'primary',
  in_progress: 'primary',
  completed: 'success',
  shipped: 'success',
}

export function OrdersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [name, setName] = useState('')
  const [clientName, setClientName] = useState('')
  const [plannedStartDate, setPlannedStartDate] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['planning-orders'],
    queryFn: () => planningApi.getOrders(),
  })

  const createMutation = useMutation({
    mutationFn: () => planningApi.createOrder({ name, clientName, plannedStartDate }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['planning-orders'] })
      handleClose()
    },
  })

  const handleClose = () => {
    setOpen(false)
    setName('')
    setClientName('')
    setPlannedStartDate('')
    createMutation.reset()
  }

  const columns: Column<OrderListItem>[] = [
    { key: 'name', header: 'Název zakázky', render: (row) => row.name },
    { key: 'client_name', header: 'Zákazník', render: (row) => row.client_name },
    {
      key: 'status',
      header: 'Stav',
      render: (row) => (
        <Badge
          label={STATUS_LABELS[row.status] ?? row.status}
          variant={STATUS_VARIANTS[row.status] ?? 'primary'}
        />
      ),
    },
    { key: 'phase_count', header: 'Fáze', render: (row) => String(row.phase_count) },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Zakázky</h1>
          <Button onClick={() => setOpen(true)}>Vytvořit zakázku</Button>
        </div>
        <Table
          columns={columns}
          data={data as (OrderListItem & Record<string, unknown>)[]}
          loading={isLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/planning/orders/$orderId', params: { orderId: row.id } })
          }}
        />
        <Modal open={open} onClose={handleClose} title="Vytvořit zakázku">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); createMutation.mutate() }}
          >
            {createMutation.isError && (
              <p style={{ color: 'var(--color-danger-600)', fontSize: 'var(--font-size-sm)' }}>
                Nepodařilo se vytvořit zakázku
              </p>
            )}
            <FormField label="Název zakázky" htmlFor="orderName">
              <Input
                id="orderName"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Název projektu"
              />
            </FormField>
            <FormField label="Zákazník" htmlFor="orderClient">
              <Input
                id="orderClient"
                value={clientName}
                onChange={(e) => setClientName(e.target.value)}
                placeholder="Název firmy"
              />
            </FormField>
            <FormField label="Plánované zahájení" htmlFor="orderStartDate">
              <Input
                id="orderStartDate"
                type="date"
                value={plannedStartDate}
                onChange={(e) => setPlannedStartDate(e.target.value)}
              />
            </FormField>
            <div className={styles.actions}>
              <Button variant="secondary" type="button" onClick={handleClose}>Zrušit</Button>
              <Button
                type="submit"
                loading={createMutation.isPending}
                disabled={!name.trim() || !clientName.trim() || !plannedStartDate}
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
