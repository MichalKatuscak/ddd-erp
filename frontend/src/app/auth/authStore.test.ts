import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from './authStore'

describe('authStore', () => {
  beforeEach(() => {
    sessionStorage.clear()
    useAuthStore.getState().clear()
  })

  it('starts unauthenticated when sessionStorage is empty', () => {
    expect(useAuthStore.getState().isAuthenticated()).toBe(false)
    expect(useAuthStore.getState().accessToken).toBeNull()
  })

  it('setTokens stores tokens in state and sessionStorage', () => {
    useAuthStore.getState().setTokens('access-123', 'refresh-456')
    expect(useAuthStore.getState().accessToken).toBe('access-123')
    expect(useAuthStore.getState().refreshToken).toBe('refresh-456')
    expect(sessionStorage.getItem('access_token')).toBe('access-123')
    expect(sessionStorage.getItem('refresh_token')).toBe('refresh-456')
    expect(useAuthStore.getState().isAuthenticated()).toBe(true)
  })

  it('setUser stores user and permissions', () => {
    useAuthStore.getState().setUser(
      { id: 'u1', email: 'a@b.cz', firstName: 'Jan', lastName: 'Novák' },
      ['crm.contacts.view_customers', 'identity.users.manage']
    )
    expect(useAuthStore.getState().user?.email).toBe('a@b.cz')
    expect(useAuthStore.getState().permissions).toContain('crm.contacts.view_customers')
  })

  it('hasPermission returns true for granted permission', () => {
    useAuthStore.getState().setUser({ id: 'u1', email: 'a@b.cz', firstName: 'A', lastName: 'B' }, ['crm.contacts.view_customers'])
    expect(useAuthStore.getState().hasPermission('crm.contacts.view_customers')).toBe(true)
    expect(useAuthStore.getState().hasPermission('identity.roles.manage')).toBe(false)
  })

  it('clear removes tokens from state and sessionStorage', () => {
    useAuthStore.getState().setTokens('access-123', 'refresh-456')
    useAuthStore.getState().clear()
    expect(useAuthStore.getState().isAuthenticated()).toBe(false)
    expect(sessionStorage.getItem('access_token')).toBeNull()
    expect(sessionStorage.getItem('refresh_token')).toBeNull()
  })
})
