<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h4 mb-0"><i class="bi bi-building me-2"></i>Клиенты</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать клиента
      </button>
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
              <th>Описание</th>
              <th class="text-end" style="width: 120px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clients.length === 0">
              <td colspan="3" class="text-center text-muted py-4">Клиенты не найдены</td>
            </tr>
            <tr v-for="client in clients" :key="client.id">
              <td class="align-middle fw-medium">{{ client.name }}</td>
              <td class="align-middle text-muted">{{ client.description ?? '—' }}</td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(client)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(client)">
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
                <h5 class="modal-title">{{ activeModal === 'create' ? 'Новый клиент' : 'Редактировать клиента' }}</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="Название клиента" />
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить клиента</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить клиента <strong>{{ target?.name }}</strong>? Действие нельзя отменить.</p>
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
import type { PmClient } from '~/composables/useProjectManagement'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'delete' | null

const { fetchClients, createClient, updateClient, deleteClient } = useProjectManagement()

const clients = ref<PmClient[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const target = ref<PmClient | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)
const form = reactive({ name: '', description: '' })

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    clients.value = (await fetchClients()).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  form.name = ''
  form.description = ''
  formError.value = null
  target.value = null
  activeModal.value = 'create'
}

const openEdit = (item: PmClient) => {
  form.name = item.name
  form.description = item.description ?? ''
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openDelete = (item: PmClient) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitForm = async () => {
  if (!form.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    const payload = { name: form.name.trim(), description: form.description.trim() || null }
    activeModal.value === 'create' ? await createClient(payload) : await updateClient(target.value!.id, payload)
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
    await deleteClient(target.value!.id)
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
onMounted(load)
</script>
