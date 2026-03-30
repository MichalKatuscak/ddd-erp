import { create } from 'zustand'

export interface AuthUser {
  id: string
  email: string
  firstName: string
  lastName: string
}

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  user: AuthUser | null
  permissions: string[]
  setTokens: (access: string, refresh: string) => void
  setUser: (user: AuthUser, permissions: string[]) => void
  clear: () => void
  hasPermission: (permission: string) => boolean
  isAuthenticated: () => boolean
}

export const useAuthStore = create<AuthState>((set, get) => ({
  accessToken: sessionStorage.getItem('access_token'),
  refreshToken: sessionStorage.getItem('refresh_token'),
  user: null,
  permissions: [],

  setTokens(access, refresh) {
    sessionStorage.setItem('access_token', access)
    sessionStorage.setItem('refresh_token', refresh)
    set({ accessToken: access, refreshToken: refresh })
  },

  setUser(user, permissions) {
    set({ user, permissions })
  },

  clear() {
    sessionStorage.removeItem('access_token')
    sessionStorage.removeItem('refresh_token')
    set({ accessToken: null, refreshToken: null, user: null, permissions: [] })
  },

  hasPermission(permission) {
    return get().permissions.includes(permission)
  },

  isAuthenticated() {
    return get().accessToken !== null
  },
}))
