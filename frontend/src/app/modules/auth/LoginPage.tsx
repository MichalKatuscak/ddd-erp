import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { useMutation } from '@tanstack/react-query'
import { Button } from '../../../design-system'
import { FormField } from '../../../design-system'
import { Input } from '../../../design-system'
import { identityApi } from '../../api/identity'
import { useAuthStore } from '../../auth/authStore'
import styles from './LoginPage.module.css'

export function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const navigate = useNavigate()
  const { setTokens, setUser } = useAuthStore()

  const loginMutation = useMutation({
    mutationFn: () => identityApi.login(email, password),
    onSuccess: async (data) => {
      setTokens(data.access_token, data.refresh_token)
      const me = await identityApi.me()
      setUser(
        { id: me.id, email: me.email, firstName: me.first_name, lastName: me.last_name },
        me.permissions,
      )
      navigate({ to: '/' })
    },
  })

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <h1 className={styles.title}>ERP</h1>
        <p className={styles.subtitle}>Přihlaste se ke svému účtu</p>
        <form
          className={styles.form}
          onSubmit={(e) => { e.preventDefault(); loginMutation.mutate() }}
        >
          <FormField
            label="E-mail"
            htmlFor="email"
            error={loginMutation.isError ? 'Nesprávné přihlašovací údaje' : undefined}
          >
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@erp.local"
              error={loginMutation.isError}
            />
          </FormField>
          <FormField label="Heslo" htmlFor="password">
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </FormField>
          <Button type="submit" variant="primary" size="md" loading={loginMutation.isPending}>
            Přihlásit se
          </Button>
        </form>
      </div>
    </div>
  )
}
