export interface Group {
  id: string
  name: string
  description: string | null
}

interface GroupList {
  items: Group[]
  total: number
  page: number
  perPage: number
}

export const useIdentityGroups = () => {
  const { authFetch } = useAuth()

  const fetchGroups = async (): Promise<GroupList> => {
    const res = await authFetch('/api/identity/groups?perPage=100')
    if (!res.ok) throw new Error(`Ошибка ${res.status}`)
    return res.json()
  }

  const createGroup = async (data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/identity/groups', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const updateGroup = async (id: string, data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch(`/api/identity/groups/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const deleteGroup = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/identity/groups/${id}`, { method: 'DELETE' })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  return { fetchGroups, createGroup, updateGroup, deleteGroup }
}
