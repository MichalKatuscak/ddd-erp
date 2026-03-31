import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from '@tanstack/react-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button, FormField, Input } from '../../../design-system'
import { crmApi } from '../../api/crm'
import { AppLayout } from '../../components/AppLayout/AppLayout'
import styles from './CustomerDetailPage.module.css'

export function CustomerDetailPage() {
  const { customerId } = useParams({ from: '/crm/customers/$customerId' })
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [email, setEmail] = useState('')

  const { data: customer, isLoading } = useQuery({
    queryKey: ['customer', customerId],
    queryFn: () => crmApi.getCustomer(customerId),
  })

  useEffect(() => {
    if (customer) {
      setFirstName(customer.first_name)
      setLastName(customer.last_name)
      setEmail(customer.email)
    }
  }, [customer])

  const updateMutation = useMutation({
    mutationFn: () => crmApi.updateCustomer(customerId, { firstName, lastName, email }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customer', customerId] })
      queryClient.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  const fullName = customer ? `${customer.first_name} ${customer.last_name}` : '…'

  return (
    <AppLayout>
      <div className={styles.page}>
        <nav className={styles.breadcrumb}>
          <Link to="/crm/customers" className={styles.breadcrumbLink}>Zákazníci</Link>
          <span className={styles.breadcrumbSep}>›</span>
          <span className={styles.breadcrumbCurrent}>{fullName}</span>
        </nav>

        {isLoading ? (
          <p className={styles.loading}>Načítám…</p>
        ) : (
          <form
            className={styles.form}
            onSubmit={(e) => { e.preventDefault(); updateMutation.mutate() }}
          >
            <h1 className={styles.title}>{fullName}</h1>
            <div className={styles.fields}>
              <FormField label="Jméno" htmlFor="detFirstName">
                <Input
                  id="detFirstName"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                />
              </FormField>
              <FormField label="Příjmení" htmlFor="detLastName">
                <Input
                  id="detLastName"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                />
              </FormField>
              <FormField
                label="E-mail"
                htmlFor="detEmail"
                error={updateMutation.isError ? 'Nepodařilo se uložit změny' : undefined}
              >
                <Input
                  id="detEmail"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  error={updateMutation.isError}
                />
              </FormField>
            </div>
            <div className={styles.actions}>
              <Button
                variant="secondary"
                type="button"
                onClick={async () => { await navigate({ to: '/crm/customers' }) }}
              >
                Zpět
              </Button>
              <Button
                type="submit"
                loading={updateMutation.isPending}
              >
                Uložit
              </Button>
            </div>
          </form>
        )}
      </div>
    </AppLayout>
  )
}
