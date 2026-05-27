<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-clock-history me-2"></i>Тикеты</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Добавить тикет
      </button>
    </div>

    <!-- Фильтры -->
    <div class="card shadow-sm mb-4">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-auto">
            <label class="form-label small mb-1">Дата с</label>
            <input v-model="filter.dateFrom" type="date" class="form-control form-control-sm" @change="load" />
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Дата по</label>
            <input v-model="filter.dateTo" type="date" class="form-control form-control-sm" @change="load" />
          </div>
          <div v-if="admin || manager" class="col-auto">
            <label class="form-label small mb-1">Сотрудник</label>
            <select v-model="filter.employeeId" class="form-select form-select-sm" style="min-width:160px;" @change="load">
              <option value="">Все</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Проект</label>
            <select v-model="filter.projectId" class="form-select form-select-sm" style="min-width:160px;" @change="onFilterProjectChange">
              <option value="">Все</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Задача</label>
            <select v-model="filter.taskId" class="form-select form-select-sm" style="min-width:160px;" @change="load">
              <option value="">Все</option>
              <option v-for="t in filteredTasks" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small mb-1">Вид работ</label>
            <select v-model="filter.workId" class="form-select form-select-sm" style="min-width:160px;" @change="load">
              <option value="">Все</option>
              <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
              <i class="bi bi-x-lg me-1"></i>Сбросить
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
              <th>Дата</th>
              <th v-if="admin || manager">Сотрудник</th>
              <th>Задача</th>
              <th>Вид работ</th>
              <th class="text-end" style="width:70px;">Часы</th>
              <th>Комментарий</th>
              <th style="width:90px;">Тип</th>
              <th class="text-end" style="width:70px;">Ставка</th>
              <th class="text-end" style="width:60px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="tickets.length === 0">
              <td :colspan="(admin || manager) ? 9 : 8" class="text-center text-muted py-4">Тикеты не найдены</td>
            </tr>
            <tr v-for="t in tickets" :key="t.id">
              <td class="align-middle text-nowrap">{{ t.date }}</td>
              <td v-if="admin || manager" class="align-middle">{{ t.employeeName }}</td>
              <td class="align-middle">{{ t.taskName }}</td>
              <td class="align-middle">{{ t.workName }}</td>
              <td class="align-middle text-end fw-medium">{{ t.hours }}</td>
              <td class="align-middle text-muted" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                {{ t.comment ?? '—' }}
              </td>
              <td class="align-middle">
                <span :class="t.type === 'manual' ? 'badge bg-primary' : 'badge bg-secondary'">
                  {{ t.type === 'manual' ? 'Вручную' : 'Импорт' }}
                </span>
              </td>
              <td class="align-middle text-end text-muted small">{{ t.rateSnapshot }}</td>
              <td class="align-middle text-end">
                <button
                  v-if="t.isEditable"
                  class="btn btn-sm btn-outline-secondary"
                  title="Редактировать"
                  @click="openEdit(t)"
                >
                  <i class="bi bi-pencil"></i>
                </button>
                <span v-else class="text-muted small">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="total > 0" class="card-footer text-muted small d-flex align-items-center justify-content-between">
        <span>Показано {{ tickets.length }} из {{ total }}</span>
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

    <!-- Модал создания -->
    <Teleport to="body">
      <template v-if="activeModal === 'create'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Новый тикет</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div v-if="admin" class="mb-3">
                  <label class="form-label">Сотрудник <span class="text-danger">*</span></label>
                  <select v-model="createForm.employeeId" class="form-select">
                    <option value="">— Выберите сотрудника —</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Проект (для фильтра задач)</label>
                  <select v-model="createForm.projectId" class="form-select" @change="createForm.taskId = ''">
                    <option value="">— Выберите проект —</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Задача <span class="text-danger">*</span></label>
                  <select v-model="createForm.taskId" class="form-select" :disabled="!createForm.projectId">
                    <option value="">— Выберите задачу —</option>
                    <option v-for="t in createTaskOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Вид работ <span class="text-danger">*</span></label>
                  <select v-model="createForm.workId" class="form-select">
                    <option value="">— Выберите вид работ —</option>
                    <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Дата <span class="text-danger">*</span></label>
                  <input v-model="createForm.date" type="date" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Часы <span class="text-danger">*</span></label>
                  <input v-model="createForm.hours" type="number" min="0.1" step="0.5" class="form-control" placeholder="Например: 2.5" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Комментарий</label>
                  <textarea v-model="createForm.comment" class="form-control" rows="2" placeholder="Необязательно"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-primary" :disabled="submitting" @click="submitCreate">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  Создать
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop show"></div>
      </template>
    </Teleport>

    <!-- Модал редактирования -->
    <Teleport to="body">
      <template v-if="activeModal === 'edit'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Редактировать тикет</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Дата</label>
                  <input v-model="editForm.date" type="date" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Часы</label>
                  <input v-model="editForm.hours" type="number" min="0.1" step="0.5" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Вид работ</label>
                  <select v-model="editForm.workId" class="form-select">
                    <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Комментарий</label>
                  <textarea v-model="editForm.comment" class="form-control" rows="2"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-primary" :disabled="submitting" @click="submitEdit">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  Сохранить
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
import type { TsTicket } from '~/composables/useTimesheet'
import type { PmProject, PmTask } from '~/composables/useProjectManagement'
import type { WcWork } from '~/composables/useWorkCatalog'
import type { User } from '~/composables/useIdentityUsers'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | null

const { isAdmin, getUserId } = useAuth()
const { fetchTickets, createTicket, updateTicket } = useTimesheet()
const { fetchProjects, fetchTasks } = useProjectManagement()
const { fetchWorks } = useWorkCatalog()
const { fetchUsers } = useIdentityUsers()

const admin = isAdmin()
const manager = false // расширить при добавлении роли manager

const tickets = ref<TsTicket[]>([])
const total = ref(0)
const page = ref(1)
const perPage = 50
const totalPages = computed(() => Math.ceil(total.value / perPage))

const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const target = ref<TsTicket | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const projects = ref<PmProject[]>([])
const allTasks = ref<PmTask[]>([])
const works = ref<WcWork[]>([])
const users = ref<User[]>([])

const filter = reactive({
  dateFrom: '',
  dateTo: '',
  employeeId: '',
  projectId: '',
  taskId: '',
  workId: '',
})

const filteredTasks = computed(() =>
  filter.projectId ? allTasks.value.filter(t => t.projectId === filter.projectId) : allTasks.value,
)

const createForm = reactive({
  projectId: '',
  taskId: '',
  workId: '',
  date: '',
  hours: '',
  comment: '',
  employeeId: '',
})

const createTaskOptions = computed(() =>
  createForm.projectId ? allTasks.value.filter(t => t.projectId === createForm.projectId) : [],
)

const editForm = reactive({
  date: '',
  hours: '',
  workId: '',
  comment: '',
})

const onFilterProjectChange = () => {
  filter.taskId = ''
  load()
}

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    const result = await fetchTickets({
      employeeId: filter.employeeId || undefined,
      projectId: filter.projectId || undefined,
      taskId: filter.taskId || undefined,
      workId: filter.workId || undefined,
      dateFrom: filter.dateFrom || undefined,
      dateTo: filter.dateTo || undefined,
      page: page.value,
      perPage,
    })
    tickets.value = result.items
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
  filter.dateFrom = ''
  filter.dateTo = ''
  filter.employeeId = ''
  filter.projectId = ''
  filter.taskId = ''
  filter.workId = ''
  page.value = 1
  load()
}

const openCreate = () => {
  const today = new Date().toISOString().slice(0, 10)
  createForm.projectId = filter.projectId
  createForm.taskId = filter.taskId
  createForm.workId = filter.workId
  createForm.date = today
  createForm.hours = ''
  createForm.comment = ''
  createForm.employeeId = admin ? (filter.employeeId || '') : (getUserId() ?? '')
  formError.value = null
  activeModal.value = 'create'
}

const openEdit = (item: TsTicket) => {
  editForm.date = item.date
  editForm.hours = item.hours
  editForm.workId = item.workId
  editForm.comment = item.comment ?? ''
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitCreate = async () => {
  const employeeId = admin ? createForm.employeeId : (getUserId() ?? '')
  if (!employeeId) { formError.value = 'Выберите сотрудника'; return }
  if (!createForm.taskId) { formError.value = 'Выберите задачу'; return }
  if (!createForm.workId) { formError.value = 'Выберите вид работ'; return }
  if (!createForm.date) { formError.value = 'Укажите дату'; return }
  if (!createForm.hours || Number(createForm.hours) <= 0) { formError.value = 'Укажите количество часов (> 0)'; return }
  submitting.value = true
  formError.value = null
  try {
    await createTicket({
      employeeId,
      taskId: createForm.taskId,
      workId: createForm.workId,
      date: createForm.date,
      hours: String(createForm.hours),
      comment: createForm.comment.trim() || null,
    })
    closeModal()
    await load()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitEdit = async () => {
  if (!editForm.hours || Number(editForm.hours) <= 0) { formError.value = 'Укажите количество часов (> 0)'; return }
  submitting.value = true
  formError.value = null
  try {
    await updateTicket(target.value!.id, {
      date: editForm.date || null,
      hours: String(editForm.hours) || null,
      workId: editForm.workId || null,
      comment: editForm.comment.trim() || null,
    })
    closeModal()
    await load()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
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
    (admin || manager) ? fetchUsers().catch(() => ({ items: [] as User[] })) : Promise.resolve({ items: [] as User[] }),
  ])
  projects.value = p.items
  allTasks.value = t.items
  works.value = w.items
  users.value = u.items
  await load()
})
</script>
