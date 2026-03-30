import { useAuthStore } from '../auth/authStore'

const BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const store = useAuthStore.getState()

  const doRequest = async (token: string | null) => {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(init.headers as Record<string, string> ?? {}),
    }
    return fetch(`${BASE_URL}${path}`, { ...init, headers })
  }

  let response = await doRequest(store.accessToken)

  if (response.status === 401 && store.refreshToken) {
    const refreshRes = await fetch(`${BASE_URL}/api/identity/commands/refresh-token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: store.refreshToken }),
    })

    if (refreshRes.ok) {
      const refreshData = await refreshRes.json() as { access_token: string; refresh_token: string }
      store.setTokens(refreshData.access_token, refreshData.refresh_token)
      response = await doRequest(refreshData.access_token)
    } else {
      store.clear()
      window.location.href = '/login'
      throw new Error('Session expired')
    }
  }

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`)
  }

  if (response.status === 204) return undefined as T
  return response.json() as Promise<T>
}

export const apiGet  = <T>(path: string)                  => apiFetch<T>(path)
export const apiPost = <T>(path: string, body: unknown)   => apiFetch<T>(path, { method: 'POST',  body: JSON.stringify(body) })
export const apiPut  = <T>(path: string, body: unknown)   => apiFetch<T>(path, { method: 'PUT',   body: JSON.stringify(body) })
