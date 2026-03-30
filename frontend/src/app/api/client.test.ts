import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAuthStore } from '../auth/authStore'

describe('API client', () => {
  beforeEach(() => {
    sessionStorage.clear()
    useAuthStore.getState().clear()
    vi.restoreAllMocks()
    vi.resetModules()
  })

  it('adds Authorization header when token exists', async () => {
    useAuthStore.getState().setTokens('my-token', 'ref-token')
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: 'ok' }),
    })
    vi.stubGlobal('fetch', mockFetch)

    const { apiGet } = await import('./client')
    await apiGet('/api/test')

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/test'),
      expect.objectContaining({
        headers: expect.objectContaining({ Authorization: 'Bearer my-token' }),
      })
    )
  })

  it('does not add Authorization header when no token', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({}),
    })
    vi.stubGlobal('fetch', mockFetch)

    const { apiGet } = await import('./client')
    await apiGet('/api/test')

    const callHeaders = mockFetch.mock.calls[0][1].headers
    expect(callHeaders.Authorization).toBeUndefined()
  })
})
