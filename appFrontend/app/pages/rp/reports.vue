<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-bar-chart-line me-2"></i>Отчёты</h1>
      <div class="d-flex gap-2">
        <button
          v-if="selected.size > 0"
          class="btn btn-outline-success btn-sm"
          @click="openExport"
        >
          <i class="bi bi-download me-1"></i>Экспорт ({{ selected.size }})
        </button>
        <button class="btn btn-primary btn-sm" @click="openCreate">
          <i class="bi bi-plus-lg me-1"></i>Создать отчёт
        </button>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="card shadow-sm mb-4">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small mb-1">Поиск по названию</label>
            <input v-model="filter.name" type="text" class="form-control form-control-sm" placeholder="Название отчёта..." @keyup.enter="load" />
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Период с</label>
            <input v-model="filter.periodFrom" type="date" class="form-control form-control-sm" @change="load" />
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Период по</label>
            <input v-model="filter.periodTo" type="date" class="form-control form-control-sm" @change="load" />
          </div>
          <div class="col-auto d-flex gap-2">
            <button class="btn btn-sm btn-primary" @click="load">
              <i class="bi bi-search me-1"></i>Найти
            </button>
            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="loadError" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ loadError }}
    </div>

    <div v-else class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:36px;">
                <input type="checkbox" class="form-check-input" :checked="allSelected" :indeterminate="someSelected && !allSelected" @change="toggleAll" />
              </th>
              <th>Название</th>
              <th style="width:200px;">Период</th>
              <th style="width:160px;">Автор</th>
              <th style="width:170px;">Создан</th>
              <th class="text-end" style="width:100px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="reports.length === 0">
              <td colspan="6" class="text-center text-muted py-4">Отчёты не найдены</td>
            </tr>
            <tr v-for="r in reports" :key="r.id">
              <td class="align-middle">
                <input type="checkbox" class="form-check-input" :checked="selected.has(r.id)" @change="toggleSelect(r.id)" />
              </td>
              <td class="align-middle fw-medium">{{ r.name }}</td>
              <td class="align-middle text-nowrap text-muted small">{{ r.periodFrom }} — {{ r.periodTo }}</td>
              <td class="align-middle text-muted small">{{ resolveUser(r.createdBy) }}</td>
              <td class="align-middle text-muted small text-nowrap">{{ formatDatetime(r.createdAt) }}</td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary" title="Просмотреть данные" @click="openView(r.id)">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="total > 0" class="card-footer text-muted small d-flex align-items-center justify-content-between">
        <span>Показано {{ reports.length }} из {{ total }}</span>
        <div v-if="totalPages > 1" class="d-flex gap-1">
          <button class="btn btn-sm btn-outline-secondary" :disabled="page === 1" @click="changePage(page - 1)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="px-2 py-1">{{ page }} / {{ totalPages }}</span>
          <button class="btn btn-sm btn-outline-secondary" :disabled="page >= totalPages" @click="changePage(page + 1)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Модал создания отчёта -->
    <Teleport to="body">
      <template v-if="activeModal === 'create'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Создать отчёт</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="createForm.name" type="text" class="form-control" placeholder="Название отчёта" />
                </div>
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Период с <span class="text-danger">*</span></label>
                    <input v-model="createForm.periodFrom" type="date" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Период по <span class="text-danger">*</span></label>
                    <input v-model="createForm.periodTo" type="date" class="form-control" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Группировка <span class="text-danger">*</span></label>
                  <div class="d-flex flex-wrap gap-3">
                    <div v-for="dim in groupByOptions" :key="dim.value" class="form-check">
                      <input
                        :id="`gb-${dim.value}`"
                        v-model="createForm.groupBy"
                        type="checkbox"
                        class="form-check-input"
                        :value="dim.value"
                      />
                      <label :for="`gb-${dim.value}`" class="form-check-label">{{ dim.label }}</label>
                    </div>
                  </div>
                </div>
                <hr />
                <p class="text-muted small mb-2">Фильтры (необязательно)</p>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small">Сотрудник</label>
                    <select v-model="createForm.filterEmployeeId" class="form-select form-select-sm">
                      <option value="">Все</option>
                      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Проект</label>
                    <select v-model="createForm.filterProjectId" class="form-select form-select-sm" @change="createForm.filterTaskId = ''">
                      <option value="">Все</option>
                      <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Задача</label>
                    <select v-model="createForm.filterTaskId" class="form-select form-select-sm" :disabled="!createForm.filterProjectId">
                      <option value="">Все</option>
                      <option v-for="t in createFilteredTasks" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Вид работ</label>
                    <select v-model="createForm.filterWorkId" class="form-select form-select-sm">
                      <option value="">Все</option>
                      <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-primary" :disabled="submitting" @click="submitCreate">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  Сформировать
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop show"></div>
      </template>
    </Teleport>

    <!-- Модал просмотра отчёта -->
    <Teleport to="body">
      <template v-if="activeModal === 'view'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="bi bi-bar-chart-line me-2"></i>{{ viewReport?.name ?? '...' }}
                </h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="viewLoading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                </div>
                <template v-else-if="viewReport">
                  <div class="row g-3 mb-3 text-muted small">
                    <div class="col-auto">
                      <strong>Период:</strong> {{ viewReport.periodFrom }} — {{ viewReport.periodTo }}
                    </div>
                    <div class="col-auto">
                      <strong>Группировка:</strong> {{ viewReport.groupBy.map(resolveGroupBy).join(', ') }}
                    </div>
                    <div class="col-auto">
                      <strong>Создан:</strong> {{ formatDatetime(viewReport.createdAt) }}
                    </div>
                  </div>
                  <div v-if="viewReport.data.length === 0" class="text-center text-muted py-3">
                    Данные отсутствуют
                  </div>
                  <div v-else class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                      <thead class="table-light">
                        <tr>
                          <th v-for="col in dataColumns" :key="col">{{ col }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, i) in viewReport.data" :key="i">
                          <td v-for="col in dataColumns" :key="col" class="align-middle small">
                            {{ row[col] ?? '—' }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </template>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Закрыть</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop show"></div>
      </template>
    </Teleport>

    <!-- Модал экспорта -->
    <Teleport to="body">
      <template v-if="activeModal === 'export'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-download me-2"></i>Экспорт отчётов</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="text-muted small mb-3">Выбрано отчётов: <strong>{{ selected.size }}</strong></p>
                <div class="mb-3">
                  <label class="form-label">Формат <span class="text-danger">*</span></label>
                  <div class="d-flex gap-3">
                    <div v-for="fmt in exportFormats" :key="fmt.value" class="form-check">
                      <input :id="`fmt-${fmt.value}`" v-model="exportFormat" type="radio" class="form-check-input" :value="fmt.value" />
                      <label :for="`fmt-${fmt.value}`" class="form-check-label">{{ fmt.label }}</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-success" :disabled="submitting" @click="submitExport">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  <i v-else class="bi bi-download me-1"></i>
                  Экспортировать
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop show"></div>
      </template>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import type { RpReportListItem, RpReport } from '~/composables/useReporting'
import type { PmProject, PmTask } from '~/composables/useProjectManagement'
import type { WcWork } from '~/composables/useWorkCatalog'
import type { User } from '~/composables/useIdentityUsers'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'view' | 'export' | null

const { fetchReports, getReport, createReport, exportReports } = useReporting()
const { fetchProjects, fetchTasks } = useProjectManagement()
const { fetchWorks } = useWorkCatalog()
const { fetchUsers } = useIdentityUsers()

const groupByOptions = [
  { value: 'employee', label: 'Сотрудник' },
  { value: 'group', label: 'Группа' },
  { value: 'project', label: 'Проект' },
  { value: 'cr', label: 'CR' },
  { value: 'task', label: 'Задача' },
  { value: 'work', label: 'Вид работ' },
]

const exportFormats = [
  { value: 'csv', label: 'CSV' },
  { value: 'xlsx', label: 'XLSX' },
  { value: 'pdf', label: 'PDF' },
]

const reports = ref<RpReportListItem[]>([])
const total = ref(0)
const page = ref(1)
const perPage = 20
const totalPages = computed(() => Math.ceil(total.value / perPage))
const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const selected = ref(new Set<string>())
const allSelected = computed(() => reports.value.length > 0 && reports.value.every(r => selected.value.has(r.id)))
const someSelected = computed(() => reports.value.some(r => selected.value.has(r.id)))

const projects = ref<PmProject[]>([])
const allTasks = ref<PmTask[]>([])
const works = ref<WcWork[]>([])
const users = ref<User[]>([])
const userMap = computed(() => Object.fromEntries(users.value.map(u => [u.id, u.name])))

const filter = reactive({ name: '', periodFrom: '', periodTo: '' })

const createForm = reactive({
  name: '',
  periodFrom: '',
  periodTo: '',
  groupBy: [] as string[],
  filterEmployeeId: '',
  filterProjectId: '',
  filterTaskId: '',
  filterWorkId: '',
})

const createFilteredTasks = computed(() =>
  createForm.filterProjectId ? allTasks.value.filter(t => t.projectId === createForm.filterProjectId) : [],
)

const exportFormat = ref('csv')

const viewReport = ref<RpReport | null>(null)
const viewLoading = ref(false)
const dataColumns = computed(() =>
  viewReport.value?.data.length ? Object.keys(viewReport.value.data[0]) : [],
)

const resolveUser = (id: string) => userMap.value[id] ?? id.slice(0, 8) + '...'
const resolveGroupBy = (v: string) => groupByOptions.find(o => o.value === v)?.label ?? v

const formatDatetime = (iso: string) => {
  try {
    return new Date(iso).toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const toggleSelect = (id: string) => {
  const s = new Set(selected.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selected.value = s
}

const toggleAll = () => {
  if (allSelected.value) {
    selected.value = new Set()
  } else {
    selected.value = new Set(reports.value.map(r => r.id))
  }
}

const load = async () => {
  loading.value = true
  loadError.value = null
  selected.value = new Set()
  try {
    const result = await fetchReports({
      name: filter.name || undefined,
      periodFrom: filter.periodFrom || undefined,
      periodTo: filter.periodTo || undefined,
      page: page.value,
      perPage,
    })
    reports.value = result.items
    total.value = result.total
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const changePage = async (p: number) => {
  page.value = p
  await load()
}

const resetFilters = () => {
  filter.name = ''
  filter.periodFrom = ''
  filter.periodTo = ''
  page.value = 1
  load()
}

const openCreate = () => {
  createForm.name = ''
  createForm.periodFrom = ''
  createForm.periodTo = ''
  createForm.groupBy = ['employee']
  createForm.filterEmployeeId = ''
  createForm.filterProjectId = ''
  createForm.filterTaskId = ''
  createForm.filterWorkId = ''
  formError.value = null
  activeModal.value = 'create'
}

const openView = async (id: string) => {
  viewReport.value = null
  viewLoading.value = true
  activeModal.value = 'view'
  try {
    viewReport.value = await getReport(id)
  } catch {
    closeModal()
  } finally {
    viewLoading.value = false
  }
}

const openExport = () => {
  exportFormat.value = 'csv'
  formError.value = null
  activeModal.value = 'export'
}

const closeModal = () => { activeModal.value = null; viewReport.value = null }

const buildFilters = () => {
  const f: Record<string, string[]> = {}
  if (createForm.filterEmployeeId) f.employeeIds = [createForm.filterEmployeeId]
  if (createForm.filterProjectId) f.projectIds = [createForm.filterProjectId]
  if (createForm.filterTaskId) f.taskIds = [createForm.filterTaskId]
  if (createForm.filterWorkId) f.workIds = [createForm.filterWorkId]
  return Object.keys(f).length > 0 ? f : null
}

const submitCreate = async () => {
  if (!createForm.name.trim()) { formError.value = 'Название обязательно'; return }
  if (!createForm.periodFrom) { formError.value = 'Укажите дату начала периода'; return }
  if (!createForm.periodTo) { formError.value = 'Укажите дату окончания периода'; return }
  if (createForm.groupBy.length === 0) { formError.value = 'Выберите хотя бы одно измерение группировки'; return }
  submitting.value = true
  formError.value = null
  try {
    await createReport({
      name: createForm.name.trim(),
      periodFrom: createForm.periodFrom,
      periodTo: createForm.periodTo,
      groupBy: createForm.groupBy,
      filters: buildFilters(),
    })
    closeModal()
    await load()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitExport = async () => {
  submitting.value = true
  formError.value = null
  try {
    await exportReports({
      reportIds: Array.from(selected.value),
      format: exportFormat.value,
    })
    closeModal()
    selected.value = new Set()
    await navigateTo('/rp/exports')
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка при экспорте'
  } finally {
    submitting.value = false
  }
}

watch(activeModal, val => { if (import.meta.client) document.body.style.overflow = val ? 'hidden' : '' })
onUnmounted(() => { if (import.meta.client) document.body.style.overflow = '' })

onMounted(async () => {
  const [p, t, w, u] = await Promise.all([
    fetchProjects().catch(() => ({ items: [] as PmProject[] })),
    fetchTasks().catch(() => ({ items: [] as PmTask[] })),
    fetchWorks().catch(() => ({ items: [] as WcWork[] })),
    fetchUsers().catch(() => ({ items: [] as User[] })),
  ])
  projects.value = p.items
  allTasks.value = t.items
  works.value = w.items
  users.value = u.items
  await load()
})
</script>
