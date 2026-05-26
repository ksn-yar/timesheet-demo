export interface User {
  id: string
  name: string
  email: string
  systemRole: string
  systemRoleLabel: string
  groupId: string | null
  groupName: string | null
  roleId: string | null
  roleName: string | null
  isActive: boolean
}

interface UserList {
  items: User[]
  total: number
  page: number
  perPage: number
}

export interface CreateUserPayload {
  name: string
  email: string
  password: string
  systemRole: string
  groupId: string | null
  roleId: string | null
}

export interface UpdateUserPayload {
  name: string
  systemRole: string
  roleId: string | null
}

export const useIdentityUsers = () => {
  const { authFetch } = useAuth()

  const fetchUsers = async (): Promise<UserList> => {
    const res = await authFetch('/api/identity/users?perPage=100')
    if (!res.ok) throw new Error(`Ошибка ${res.status}`)
    return res.json()
  }

  const createUser = async (data: CreateUserPayload): Promise<void> => {
    const res = await authFetch('/api/identity/users', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const updateUser = async (id: string, data: UpdateUserPayload): Promise<void> => {
    const res = await authFetch(`/api/identity/users/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const deleteUser = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/identity/users/${id}`, { method: 'DELETE' })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const deactivateUser = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/identity/users/${id}/deactivate`, { method: 'POST' })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  const changeUserGroup = async (id: string, groupId: string | null): Promise<void> => {
    const res = await authFetch(`/api/identity/users/${id}/group`, {
      method: 'PATCH',
      body: JSON.stringify({ groupId }),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({})) as Record<string, unknown>
      throw new Error(String(err.error ?? `Ошибка ${res.status}`))
    }
  }

  return { fetchUsers, createUser, updateUser, deleteUser, deactivateUser, changeUserGroup }
}
