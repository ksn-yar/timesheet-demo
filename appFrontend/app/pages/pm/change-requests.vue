<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-arrow-left-right me-2"></i>Change Requests</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать CR
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
              <th>Описание</th>
              <th class="text-end" style="width: 120px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="changeRequests.length === 0">
              <td colspan="4" class="text-center text-muted py-4">Change Requests не найдены</td>
            </tr>
            <tr v-for="cr in changeRequests" :key="cr.id">
              <td class="align-middle fw-medium">{{ cr.name }}</td>
              <td class="align-middle text-muted">{{ cr.projectName }}</td>
              <td class="align-middle text-muted small" style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ cr.description ?? '—' }}
              </td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(cr)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(cr)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <template v-if="activeModal === 'create' || activeModal === 'edit'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">{{ activeModal === 'create' ? 'Новый Change Request' : 'Редактировать CR' }}</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div v-if="activeModal === 'create'" class="mb-3">
                  <label class="form-label">Проект <span class="text-danger">*</span></label>
                  <select v-model="form.projectId" class="form-select">
                    <option value="">— Выберите проект —</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="Название CR" />
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

    <Teleport to="body">
      <template v-if="activeModal === 'delete'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить CR</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить CR <strong>{{ target?.name }}</strong>? Действие нельзя отменить.</p>
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
import type { PmChangeRequest, PmProject } from '~/composables/useProjectManagement'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'delete' | null

const { fetchChangeRequests, createChangeRequest, updateChangeRequest, deleteChangeRequest, fetchProjects } = useProjectManagement()

const changeRequests = ref<PmChangeRequest[]>([])
const projects = ref<PmProject[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const filterProjectId = ref('')
const activeModal = ref<ModalType>(null)
const target = ref<PmChangeRequest | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const form = reactive({ projectId: '', name: '', description: '' })

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    changeRequests.value = (await fetchChangeRequests({
      projectId: filterProjectId.value || undefined,
    })).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  form.projectId = filterProjectId.value
  form.name = ''
  form.description = ''
  formError.value = null
  target.value = null
  activeModal.value = 'create'
}

const openEdit = (item: PmChangeRequest) => {
  form.projectId = item.projectId
  form.name = item.name
  form.description = item.description ?? ''
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openDelete = (item: PmChangeRequest) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitForm = async () => {
  if (activeModal.value === 'create' && !form.projectId) { formError.value = 'Выберите проект'; return }
  if (!form.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    const desc = form.description.trim() || null
    if (activeModal.value === 'create') {
      await createChangeRequest({ projectId: form.projectId, name: form.name.trim(), description: desc })
    } else {
      await updateChangeRequest(target.value!.id, { name: form.name.trim(), description: desc })
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
    await deleteChangeRequest(target.value!.id)
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
  projects.value = (await fetchProjects().catch(() => ({ items: [] }))).items
  await load()
})
</script>
