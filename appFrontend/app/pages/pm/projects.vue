<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-kanban me-2"></i>Проекты</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать проект
      </button>
    </div>

    <div class="row g-2 mb-4">
      <div class="col-auto">
        <select v-model="filterClientId" class="form-select form-select-sm" style="min-width: 180px;" @change="load">
          <option value="">Все клиенты</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="col-auto">
        <select v-model="filterStatus" class="form-select form-select-sm" @change="load">
          <option value="">Все статусы</option>
          <option value="active">Активный</option>
          <option value="closed">Закрыт</option>
        </select>
      </div>
      <div class="col-auto" v-if="filterClientId || filterStatus">
        <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">
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
              <th>Клиент</th>
              <th>Статус</th>
              <th>Описание</th>
              <th class="text-end" style="width: 120px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="projects.length === 0">
              <td colspan="5" class="text-center text-muted py-4">Проекты не найдены</td>
            </tr>
            <tr v-for="p in projects" :key="p.id">
              <td class="align-middle fw-medium">{{ p.name }}</td>
              <td class="align-middle text-muted">{{ p.clientName }}</td>
              <td class="align-middle">
                <span :class="p.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ p.statusLabel }}
                </span>
              </td>
              <td class="align-middle text-muted small" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ p.description ?? '—' }}
              </td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(p)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(p)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модал создания / редактирования -->
    <Teleport to="body">
      <template v-if="activeModal === 'create' || activeModal === 'edit'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">{{ activeModal === 'create' ? 'Новый проект' : 'Редактировать проект' }}</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div v-if="activeModal === 'create'" class="mb-3">
                  <label class="form-label">Клиент <span class="text-danger">*</span></label>
                  <select v-model="form.clientId" class="form-select">
                    <option value="">— Выберите клиента —</option>
                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="Название проекта" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Статус <span class="text-danger">*</span></label>
                  <select v-model="form.status" class="form-select">
                    <option value="active">Активный</option>
                    <option value="closed">Закрыт</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Описание</label>
                  <textarea v-model="form.description" class="form-control" rows="3" placeholder="Описание (необязательно)"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-primary" :disabled="submitting" @click="submitForm">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  {{ activeModal === 'create' ? 'Создать' : 'Сохранить' }}
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить проект</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить проект <strong>{{ target?.name }}</strong>? Действие нельзя отменить.</p>
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
import type { PmProject, PmClient } from '~/composables/useProjectManagement'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'delete' | null

const { fetchProjects, createProject, updateProject, deleteProject, fetchClients } = useProjectManagement()

const projects = ref<PmProject[]>([])
const clients = ref<PmClient[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const filterClientId = ref('')
const filterStatus = ref('')
const activeModal = ref<ModalType>(null)
const target = ref<PmProject | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const form = reactive({ clientId: '', name: '', status: 'active', description: '' })

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    projects.value = (await fetchProjects({
      clientId: filterClientId.value || undefined,
      status: filterStatus.value || undefined,
    })).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const clearFilters = () => { filterClientId.value = ''; filterStatus.value = ''; load() }

const openCreate = () => {
  form.clientId = ''
  form.name = ''
  form.status = 'active'
  form.description = ''
  formError.value = null
  target.value = null
  activeModal.value = 'create'
}

const openEdit = (item: PmProject) => {
  form.clientId = item.clientId
  form.name = item.name
  form.status = item.status
  form.description = item.description ?? ''
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openDelete = (item: PmProject) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitForm = async () => {
  if (activeModal.value === 'create' && !form.clientId) { formError.value = 'Выберите клиента'; return }
  if (!form.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    const desc = form.description.trim() || null
    if (activeModal.value === 'create') {
      await createProject({ clientId: form.clientId, name: form.name.trim(), status: form.status, description: desc })
    } else {
      await updateProject(target.value!.id, { name: form.name.trim(), status: form.status, description: desc })
    }
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
    await deleteProject(target.value!.id)
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
  clients.value = (await fetchClients().catch(() => ({ items: [] }))).items
  await load()
})
</script>
