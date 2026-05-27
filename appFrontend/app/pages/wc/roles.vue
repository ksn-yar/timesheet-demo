<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h4 mb-0"><i class="bi bi-person-badge me-2"></i>Роли исполнителей</h1>
      <button v-if="admin" class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать
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
              <th v-if="admin" class="text-end" style="width: 80px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="roles.length === 0">
              <td :colspan="admin ? 3 : 2" class="text-center text-muted py-4">Роли не найдены</td>
            </tr>
            <tr v-for="r in roles" :key="r.id">
              <td class="align-middle fw-medium">{{ r.name }}</td>
              <td class="align-middle text-muted">{{ r.description ?? '—' }}</td>
              <td v-if="admin" class="align-middle text-end">
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(r)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <template v-if="activeModal === 'create'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Новая роль</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="Например: Developer, QA, PM" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Описание</label>
                  <textarea v-model="form.description" class="form-control" rows="3" placeholder="Описание (необязательно)"></textarea>
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

    <Teleport to="body">
      <template v-if="activeModal === 'delete'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить роль</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить роль <strong>{{ target?.name }}</strong>?</p>
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
import type { WcRole } from '~/composables/useWorkCatalog'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'delete' | null

const { fetchRoles, createRole, deleteRole } = useWorkCatalog()
const { isAdmin } = useAuth()

const admin = isAdmin()
const roles = ref<WcRole[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const target = ref<WcRole | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)
const form = reactive({ name: '', description: '' })

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    roles.value = (await fetchRoles()).items
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
  activeModal.value = 'create'
}

const openDelete = (item: WcRole) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitCreate = async () => {
  if (!form.name.trim()) { formError.value = 'Название обязательно'; return }
  submitting.value = true
  formError.value = null
  try {
    await createRole({ name: form.name.trim(), description: form.description.trim() || null })
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
    await deleteRole(target.value!.id)
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
