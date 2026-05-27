export interface TsTicket {
  id: string
  employeeId: string
  employeeName: string
  taskId: string
  taskName: string
  workId: string
  workName: string
  date: string
  hours: string
  comment: string | null
  rateSnapshot: string
  type: string
  importSource: string | null
  externalId: string | null
  isEditable: boolean
}

export interface TsMappingRules {
  employeeMapping: { sourceField: string; matchBy: string }
  taskMapping: { sourceField: string; matchBy: string }
  workMapping: { sourceField: string; matchBy: string }
  dateField: string
  hoursField: string
  commentField: string | null
  externalIdField: string
}

export interface TsImportPolicy {
  id: string
  name: string
  sourceSystem: string
  mappingRules: TsMappingRules
  allowEdit: boolean
  isActive: boolean
}

export interface TsImportSummary {
  imported: number
  duplicates: number
  errors: number
  logEntries: Array<Record<string, unknown>>
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

export const useTimesheet = () => {
  const { authFetch } = useAuth()

  // --- Tickets ---

  const fetchTickets = async (params?: {
    employeeId?: string
    projectId?: string
    crId?: string
    taskId?: string
    workId?: string
    dateFrom?: string
    dateTo?: string
    page?: number
    perPage?: number
  }): Promise<PaginatedList<TsTicket>> => {
    const q = new URLSearchParams()
    if (params?.employeeId) q.set('employeeId', params.employeeId)
    if (params?.projectId) q.set('projectId', params.projectId)
    if (params?.crId) q.set('crId', params.crId)
    if (params?.taskId) q.set('taskId', params.taskId)
    if (params?.workId) q.set('workId', params.workId)
    if (params?.dateFrom) q.set('dateFrom', params.dateFrom)
    if (params?.dateTo) q.set('dateTo', params.dateTo)
    q.set('page', String(params?.page ?? 1))
    q.set('perPage', String(params?.perPage ?? 50))
    const res = await authFetch(`/api/timesheet/tickets?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createTicket = async (data: {
    employeeId: string
    taskId: string
    workId: string
    date: string
    hours: string
    comment?: string | null
  }): Promise<void> => {
    const res = await authFetch('/api/timesheet/tickets', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateTicket = async (id: string, data: {
    date?: string | null
    hours?: string | null
    workId?: string | null
    comment?: string | null
  }): Promise<void> => {
    const res = await authFetch(`/api/timesheet/tickets/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  // --- Import Policies ---

  const fetchImportPolicies = async (): Promise<PaginatedList<TsImportPolicy>> => {
    const res = await authFetch('/api/timesheet/import-policies?perPage=100')
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createImportPolicy = async (data: {
    name: string
    sourceSystem: string
    mappingRules: TsMappingRules
    allowEdit: boolean
  }): Promise<void> => {
    const res = await authFetch('/api/timesheet/import-policies', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const updateImportPolicy = async (id: string, data: {
    mappingRules?: TsMappingRules | null
    allowEdit?: boolean | null
    isActive?: boolean | null
  }): Promise<void> => {
    const res = await authFetch(`/api/timesheet/import-policies/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
  }

  const runImport = async (id: string, data: {
    dateFrom: string
    dateTo: string
  }): Promise<TsImportSummary> => {
    const res = await authFetch(`/api/timesheet/import-policies/${id}/run`, {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  return {
    fetchTickets, createTicket, updateTicket,
    fetchImportPolicies, createImportPolicy, updateImportPolicy, runImport,
  }
}
