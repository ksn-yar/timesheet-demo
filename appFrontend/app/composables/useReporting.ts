export interface RpReportListItem {
  id: string
  name: string
  createdBy: string
  createdAt: string
  periodFrom: string
  periodTo: string
}

export interface RpReport extends RpReportListItem {
  filters: Record<string, string[]> | null
  groupBy: string[]
  data: Array<Record<string, unknown>>
}

export interface RpExport {
  id: string
  reportIds: string[]
  format: string
  generatedAt: string
  fileRef: string
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

export const useReporting = () => {
  const { authFetch } = useAuth()

  // --- Reports ---

  const fetchReports = async (params?: {
    createdBy?: string
    periodFrom?: string
    periodTo?: string
    name?: string
    page?: number
    perPage?: number
  }): Promise<PaginatedList<RpReportListItem>> => {
    const q = new URLSearchParams()
    if (params?.createdBy) q.set('createdBy', params.createdBy)
    if (params?.periodFrom) q.set('periodFrom', params.periodFrom)
    if (params?.periodTo) q.set('periodTo', params.periodTo)
    if (params?.name) q.set('name', params.name)
    q.set('page', String(params?.page ?? 1))
    q.set('perPage', String(params?.perPage ?? 20))
    const res = await authFetch(`/api/reporting/reports?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const getReport = async (id: string): Promise<RpReport> => {
    const res = await authFetch(`/api/reporting/reports/${id}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const createReport = async (data: {
    name: string
    periodFrom: string
    periodTo: string
    groupBy: string[]
    filters?: Record<string, string[]> | null
  }): Promise<string> => {
    const res = await authFetch('/api/reporting/reports', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    const body = await res.json() as { id: string }
    return body.id
  }

  // --- Exports ---

  const fetchExports = async (params?: {
    format?: string
    page?: number
    perPage?: number
  }): Promise<PaginatedList<RpExport>> => {
    const q = new URLSearchParams()
    if (params?.format) q.set('format', params.format)
    q.set('page', String(params?.page ?? 1))
    q.set('perPage', String(params?.perPage ?? 20))
    const res = await authFetch(`/api/reporting/exports?${q}`)
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    return res.json()
  }

  const exportReports = async (data: {
    reportIds: string[]
    format: string
  }): Promise<string> => {
    const res = await authFetch('/api/reporting/exports', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!res.ok) await apiError(res, `Ошибка ${res.status}`)
    const body = await res.json() as { exportId: string }
    return body.exportId
  }

  const downloadExport = async (exportId: string, filename: string): Promise<void> => {
    const res = await authFetch(`/api/reporting/exports/${exportId}/download`)
    if (!res.ok) await apiError(res, `Ошибка скачивания`)
    const blob = await res.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }

  return {
    fetchReports, getReport, createReport,
    fetchExports, exportReports, downloadExport,
  }
}
