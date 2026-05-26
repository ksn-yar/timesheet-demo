export interface PmClient {
  id: string
  name: string
  description: string | null
}

export interface PmProject {
  id: string
  clientId: string
  clientName: string
  name: string
  status: string
  statusLabel: string
  description: string | null
}

export interface PmChangeRequest {
  id: string
  projectId: string
  projectName: string
  name: string
  description: string | null
}

export interface PmTask {
  id: string
  projectId: string
  projectName: string
  crId: string | null
  crName: string | null
  name: string
  description: string | null
  estimate: number | null
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

export const useProjectManagement = () => {
  const { authFetch } = useAuth()

  // --- Clients ---

  const fetchClients = async (): Promise<PaginatedList<PmClient>> => {
    const res = await authFetch('/api/project-management/clients?perPage=100')
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createClient = async (data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/project-management/clients', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateClient = async (id: string, data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch(`/api/project-management/clients/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteClient = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/project-management/clients/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Projects ---

  const fetchProjects = async (params?: { clientId?: string; status?: string }): Promise<PaginatedList<PmProject>> => {
    const q = new URLSearchParams({ perPage: '100' })
    if (params?.clientId) q.set('clientId', params.clientId)
    if (params?.status) q.set('status', params.status)
    const res = await authFetch(`/api/project-management/projects?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createProject = async (data: { clientId: string; name: string; status: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/project-management/projects', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateProject = async (id: string, data: { name: string; status: string; description: string | null }): Promise<void> => {
    const res = await authFetch(`/api/project-management/projects/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteProject = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/project-management/projects/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Change Requests ---

  const fetchChangeRequests = async (params?: { projectId?: string }): Promise<PaginatedList<PmChangeRequest>> => {
    const q = new URLSearchParams({ perPage: '100' })
    if (params?.projectId) q.set('projectId', params.projectId)
    const res = await authFetch(`/api/project-management/change-requests?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createChangeRequest = async (data: { projectId: string; name: string; description: string | null }): Promise<void> => {
    const res = await authFetch('/api/project-management/change-requests', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateChangeRequest = async (id: string, data: { name: string; description: string | null }): Promise<void> => {
    const res = await authFetch(`/api/project-management/change-requests/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteChangeRequest = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/project-management/change-requests/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Tasks ---

  const fetchTasks = async (params?: { projectId?: string }): Promise<PaginatedList<PmTask>> => {
    const q = new URLSearchParams({ perPage: '100' })
    if (params?.projectId) q.set('projectId', params.projectId)
    const res = await authFetch(`/api/project-management/tasks?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createTask = async (data: { projectId: string; crId: string | null; name: string; description: string | null; estimate: number | null }): Promise<void> => {
    const res = await authFetch('/api/project-management/tasks', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateTask = async (id: string, data: { name: string; description: string | null; estimate: number | null }): Promise<void> => {
    const res = await authFetch(`/api/project-management/tasks/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const deleteTask = async (id: string): Promise<void> => {
    const res = await authFetch(`/api/project-management/tasks/${id}`, { method: 'DELETE' })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  return {
    fetchClients, createClient, updateClient, deleteClient,
    fetchProjects, createProject, updateProject, deleteProject,
    fetchChangeRequests, createChangeRequest, updateChangeRequest, deleteChangeRequest,
    fetchTasks, createTask, updateTask, deleteTask,
  }
}
