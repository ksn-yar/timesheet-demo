<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-list-task me-2"></i>Задачи</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать задачу
      </button>
    </div>

    <div class="row g-2 mb-4">
      <div class="col-auto">
        <select v-model="filterProjectId" class="form-select form-select-sm" style="min-width: 200px;" @change="load">
          <option value="">Все проекты</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>
      <div class="col-auto" v-if="filterProjectId">
        <button class="btn btn-sm btn-outline-secondary" @click="filterProjectId = ''; load()">
          <i class="bi bi-x-lg me-1"></i>Сбросить
        </button>
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
              <th>Название</th>
              <th>Проект</th>
              <th>Change Request</th>
              <th class="text-end" style="width: 90px;">Оценка, ч</th>
              <th class="text-end" style="width: 120px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="tasks.length === 0">
              <td colspan="5" class="text-center text-muted py-4">Задачи не найдены</td>
            </tr>
            <tr v-for="task in tasks" :key="task.id">
              <td class="align-middle fw-medium">{{ task.name }}</td>
              <td class="align-middle text-muted">{{ task.projectName }}</td>
              <td class="align-middle text-muted">{{ task.crName ?? '—' }}</td>
              <td class="align-middle text-end">{{ task.estimate ?? '—' }}</td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(task)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(task)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модал создания -->
    <Teleport to="body">
      <template v-if="activeModal === 'create'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Новая задача</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Проект <span class="text-danger">*</span></label>
                  <select v-model="createForm.projectId" class="form-select" @change="onCreateProjectChange">
                    <option value="">— Выберите проект —</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Change Request</label>
                  <select v-model="createForm.crId" class="form-select" :disabled="!createForm.projectId">
                    <option :value="null">— Без CR —</option>
                    <option v-for="cr in filteredCrs" :key="cr.id" :value="cr.id">{{ cr.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="createForm.name" type="text" class="form-control" placeholder="Название задачи" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Описание</label>
                  <textarea v-model="createForm.description" class="form-control" rows="2" placeholder="Описание (необязательно)"></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Оценка, ч</label>
                  <input v-model.number="createForm.estimate" type="number" min="0" step="0.5" class="form-control" placeholder="Количество часов" />
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
                <h5 class="modal-title">Редактировать задачу</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="editForm.name" type="text" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Описание</label>
                  <textarea v-model="editForm.description" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Оценка, ч</label>
                  <input v-model.number="editForm.estimate" type="number" min="0" step="0.5" class="form-control" />
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

    <!-- Модал удаления -->
    <Teleport to="body">
      <template v-if="activeModal === 'delete'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить задачу</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить задачу <strong>{{ target?.name }}</strong>? Действие нельзя отменить.</p>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-danger" :disabled="submitting" @click="submitDelete">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  Удалить
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
import type { PmTask, PmProject, PmChangeRequest } from '~/composables/useProjectManagement'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'delete' | null

const { fetchTasks, createTask, updateTask, deleteTask, fetchProjects, fetchChangeRequests } = useProjectManagement()

const tasks = ref<PmTask[]>([])
const projects = ref<PmProject[]>([])
const allCrs = ref<PmChangeRequest[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const filterProjectId = ref('')
const activeModal = ref<ModalType>(null)
const target = ref<PmTask | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const createForm = reactive({
  projectId: '',
  crId: null as string | null,
  name: '',
  description: '',
  estimate: null as number | null,
})

const editForm = reactive({
  name: '',
  description: '',
  estimate: null as number | null,
})

const filteredCrs = computed(() =>
  createForm.projectId ? allCrs.value.filter(cr => cr.projectId === createForm.projectId) : [],
)

const onCreateProjectChange = () => { createForm.crId = null }

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    tasks.value = (await fetchTasks({ projectId: filterProjectId.value || undefined })).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  createForm.projectId = filterProjectId.value
  createForm.crId = null
  createForm.name = ''
  createForm.description = ''
  createForm.estimate = null
  formError.value = null
  target.value = null
  activeModal.value = 'create'
}

const openEdit = (item: PmTask) => {
  editForm.name = item.name
  editForm.description = item.description ?? ''
  editForm.estimate = item.estimate
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openDelete = (item: PmTask) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitCreate = async () => {
  if (!createForm.projectId) { formError.value = 'Выберите проект'; return }
  if (!createForm.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    await createTask({
      projectId: createForm.projectId,
      crId: createForm.crId,
      name: createForm.name.trim(),
      description: createForm.description.trim() || null,
      estimate: createForm.estimate ?? null,
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
  if (!editForm.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    await updateTask(target.value!.id, {
      name: editForm.name.trim(),
      description: editForm.description.trim() || null,
      estimate: editForm.estimate ?? null,
    })
    closeModal()
    await load()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitDelete = async () => {
  submitting.value = true
  formError.value = null
  try {
    await deleteTask(target.value!.id)
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
  const [p, cr] = await Promise.all([
    fetchProjects().catch(() => ({ items: [] })),
    fetchChangeRequests().catch(() => ({ items: [] })),
  ])
  projects.value = p.items
  allCrs.value = cr.items
  await load()
})
</script>
