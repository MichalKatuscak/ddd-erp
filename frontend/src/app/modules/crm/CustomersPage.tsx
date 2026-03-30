import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Table, Button, Modal, FormField, Input } from '../../../design-system'
import type { Column } from '../../../design-system'
import { crmApi } from '../../api/crm'
import type { CustomerListItem } from '../../api/crm'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './CustomersPage.module.css'

export function CustomersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [email, setEmail] = useState('')

  const { data = [], isLoading } = useQuery({
    queryKey: ['customers'],
    queryFn: () => crmApi.getCustomers(),
  })

  const addMutation = useMutation({
    mutationFn: () => crmApi.registerCustomer({ firstName, lastName, email }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      setOpen(false)
      setFirstName('')
      setLastName('')
      setEmail('')
    },
  })

  const columns: Column<CustomerListItem>[] = [
    { key: 'full_name', header: 'Jméno' },
    { key: 'email', header: 'E-mail' },
  ]

  return (
    <AppLayout>
      <div className={styles.page}>
        <div className={styles.header}>
          <h1 className={styles.title}>Zákazníci</h1>
          <Button onClick={() => setOpen(true)}>Přidat zákazníka</Button>
        </div>
        <Table
          columns={columns}
          data={data}
          loading={isLoading}
          rowKey={(row) => row.id}
          onRowClick={async (row) => {
            await navigate({ to: '/crm/customers/$customerId', params: { customerId: row.id } })
          }}
        />
        <Modal open={open} onClose={() => setOpen(false)} title="Přidat zákazníka">
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); addMutation.mutate() }}
          >
            <FormField label="Jméno" htmlFor="addFirstName">
              <Input
                id="addFirstName"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                placeholder="Jan"
              />
            </FormField>
            <FormField label="Příjmení" htmlFor="addLastName">
              <Input
                id="addLastName"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                placeholder="Novák"
              />
            </FormField>
            <FormField
              label="E-mail"
              htmlFor="addEmail"
              error={addMutation.isError ? 'Nepodařilo se uložit zákazníka' : undefined}
            >
              <Input
                id="addEmail"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="jan@firma.cz"
                error={addMutation.isError}
              />
            </FormField>
            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={() => setOpen(false)}
              >
                Zrušit
              </Button>
              <Button type="submit" loading={addMutation.isPending}>
                Uložit
              </Button>
            </div>
          </form>
        </Modal>
      </div>
    </AppLayout>
  )
}
