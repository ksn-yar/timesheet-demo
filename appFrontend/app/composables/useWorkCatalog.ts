export interface WcWork {
  id: string
  name: string
  description: string | null
}

export interface WcRole {
  id: string
  name: string
  description: string | null
}

export interface WcRate {
  id: string
  amount: string
  currency: string
  effectiveFrom: string
  roleId: string | null
  roleName: string | null
  workId: string | null
  workName: string | null
}

interface PaginatedList<T> {
  items: T[]
  total: number
  page: number
  perPage: number
}

const apiError = async (res: Response, fallback: string): Promise<never> => {
  const err = await res.json().catch(() => ({})) as Record<string, unknown>
  throw new Error(String(err.error ?? fallback))
}

export const useWorkCatalog = () => {
  const { authFetch } = useAuth()

  // --- Works ---

  const fetchWorks = async (): Promise<PaginatedList<WcWork>> => {
    const res = await authFetch('/api/work-catalog/works?perPage=100')
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createWork = async (data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/work-catalog/works', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteWork = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/work-catalog/works/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Roles ---

  const fetchRoles = async (): Promise<PaginatedList<WcRole>> => {
    const res = await authFetch('/api/work-catalog/roles?perPage=100')
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createRole = async (data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/work-catalog/roles', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteRole = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/work-catalog/roles/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Rates ---

  const fetchRates = async (params?: { roleId?: string; workId?: string }): Promise<PaginatedList<WcRate>> => {
    const q = new URLSearchParams({ perPage: '100' })
    if (params?.roleId) q.set('roleId', params.roleId)
    if (params?.workId) q.set('workId', params.workId)
    const res = await authFetch(`/api/work-catalog/rates?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createRate = async (data: {
    amount: string
    currency: string
    effectiveFrom: string
    roleId: string | null
    workId: string | null
  }): Promise<void> => {
    const res = await authFetch('/api/work-catalog/rates', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateRate = async (id: string, data: {
    amount?: string
    currency?: string
    effectiveFrom?: string
    roleId?: string | null
    workId?: string | null
  }): Promise<void> => {
    const res = await authFetch(`/api/work-catalog/rates/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteRate = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/work-catalog/rates/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  return {
    fetchWorks, createWork, deleteWork,
    fetchRoles, createRole, deleteRole,
    fetchRates, createRate, updateRate, deleteRate,
  }
}
