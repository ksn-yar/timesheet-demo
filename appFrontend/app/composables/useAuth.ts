export const useAuth = () => {
  const accessToken = useCookie<string | null>('access_token', { default: () => null })
  const refreshToken = useCookie<string | null>('refresh_token', { default: () => null })

  const setTokens = (token: string, refresh: string) => {
    accessToken.value = token
    refreshToken.value = refresh
  }

  const clearTokens = () => {
    accessToken.value = null
    refreshToken.value = null
  }

  const tryRefresh = async (): Promise<boolean> => {
    if (!refreshToken.value) return false

    try {
      const response = await fetch('/api/auth/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: refreshToken.value }),
      })

      if (!response.ok) {
        clearTokens()
        return false
      }

      const result = await response.json()
      setTokens(result.token, result.refresh_token)
      return true
    } catch {
      clearTokens()
      return false
    }
  }

  const buildHeaders = (token: string | null, extra?: HeadersInit): Record<string, string> => {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(extra as Record<string, string> ?? {}),
    }
    if (token) headers['Authorization'] = `Bearer ${token}`
    return headers
  }

  const authFetch = async (url: string, options: RequestInit = {}): Promise<Response> => {
    let response = await fetch(url, {
      ...options,
      headers: buildHeaders(accessToken.value, options.headers),
    })

    if (response.status !== 401) return response

    const refreshed = await tryRefresh()

    if (!refreshed) {
      await navigateTo('/login')
      return response
    }

    return fetch(url, {
      ...options,
      headers: buildHeaders(accessToken.value, options.headers),
    })
  }

  const isAdmin = (): boolean => {
    if (!accessToken.value) return false
    try {
      const payload = JSON.parse(atob(accessToken.value.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
      return Array.isArray(payload.roles) && payload.roles.includes('ROLE_ADMIN')
    } catch {
      return false
    }
  }

  return { accessToken, refreshToken, setTokens, clearTokens, authFetch, isAdmin }
}
