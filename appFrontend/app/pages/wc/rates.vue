<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-currency-exchange me-2"></i>Тарифные ставки</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать ставку
      </button>
    </div>

    <div class="row g-2 mb-4">
      <div class="col-auto">
        <select v-model="filterRoleId" class="form-select form-select-sm" style="min-width: 180px;" @change="load">
          <option value="">Все роли</option>
          <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </div>
      <div class="col-auto">
        <select v-model="filterWorkId" class="form-select form-select-sm" style="min-width: 180px;" @change="load">
          <option value="">Все виды работ</option>
          <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
        </select>
      </div>
      <div v-if="filterRoleId || filterWorkId" class="col-auto">
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
              <th>Сумма</th>
              <th>Валюта</th>
              <th>Дата начала</th>
              <th>Роль</th>
              <th>Вид работ</th>
              <th class="text-end" style="width: 120px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="rates.length === 0">
              <td colspan="6" class="text-center text-muted py-4">Ставки не найдены</td>
            </tr>
            <tr v-for="rate in rates" :key="rate.id">
              <td class="align-middle fw-medium">{{ rate.amount }}</td>
              <td class="align-middle">
                <span class="badge bg-secondary">{{ rate.currency }}</span>
              </td>
              <td class="align-middle text-muted">{{ rate.effectiveFrom }}</td>
              <td class="align-middle">{{ rate.roleName ?? '—' }}</td>
              <td class="align-middle">{{ rate.workName ?? '—' }}</td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(rate)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(rate)">
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
                <h5 class="modal-title">{{ activeModal === 'create' ? 'Новая ставка' : 'Редактировать ставку' }}</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="row g-3 mb-3">
                  <div class="col-8">
                    <label class="form-label">Сумма <span class="text-danger">*</span></label>
                    <input v-model="form.amount" type="text" class="form-control" placeholder="Например: 1500.00" />
                  </div>
                  <div class="col-4">
                    <label class="form-label">Валюта <span class="text-danger">*</span></label>
                    <input v-model="form.currency" type="text" class="form-control" placeholder="RUB" maxlength="3" style="text-transform: uppercase;" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Дата начала действия <span class="text-danger">*</span></label>
                  <input v-model="form.effectiveFrom" type="date" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Роль исполнителя</label>
                  <select v-model="form.roleId" class="form-select">
                    <option value="">— Не выбрана —</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Вид работ</label>
                  <select v-model="form.workId" class="form-select">
                    <option value="">— Не выбран —</option>
                    <option v-for="w in works" :key="w.id" :value="w.id">{{ w.name }}</option>
                  </select>
                </div>
                <div class="form-text text-muted">Необходимо выбрать хотя бы одно из двух: роль или вид работ.</div>
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить ставку</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">
                  Удалить ставку <strong>{{ target?.amount }} {{ target?.currency }}</strong>
                  от <strong>{{ target?.effectiveFrom }}</strong>?
                </p>
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
import type { WcRate, WcRole, WcWork } from '~/composables/useWorkCatalog'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'delete' | null

const { fetchRates, createRate, updateRate, deleteRate, fetchRoles, fetchWorks } = useWorkCatalog()

const rates = ref<WcRate[]>([])
const roles = ref<WcRole[]>([])
const works = ref<WcWork[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const filterRoleId = ref('')
const filterWorkId = ref('')
const activeModal = ref<ModalType>(null)
const target = ref<WcRate | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const form = reactive({ amount: '', currency: 'RUB', effectiveFrom: '', roleId: '', workId: '' })

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    rates.value = (await fetchRates({
      roleId: filterRoleId.value || undefined,
      workId: filterWorkId.value || undefined,
    })).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const clearFilters = () => { filterRoleId.value = ''; filterWorkId.value = ''; load() }

const openCreate = () => {
  form.amount = ''
  form.currency = 'RUB'
  form.effectiveFrom = ''
  form.roleId = ''
  form.workId = ''
  formError.value = null
  target.value = null
  activeModal.value = 'create'
}

const openEdit = (item: WcRate) => {
  form.amount = item.amount
  form.currency = item.currency
  form.effectiveFrom = item.effectiveFrom
  form.roleId = item.roleId ?? ''
  form.workId = item.workId ?? ''
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openDelete = (item: WcRate) => {
  formError.value = null
  target.value = item
  activeModal.value = 'delete'
}

const closeModal = () => { activeModal.value = null; target.value = null }

const submitForm = async () => {
  if (!form.amount.trim()) { formError.value = 'Сумма обязательна'; return }
  if (!form.currency.trim() || form.currency.trim().length !== 3) { formError.value = 'Валюта должна содержать ровно 3 символа'; return }
  if (!form.effectiveFrom) { formError.value = 'Дата начала действия обязательна'; return }
  if (!form.roleId && !form.workId) { formError.value = 'Необходимо выбрать роль или вид работ'; return }

  submitting.value = true
  formError.value = null
  try {
    const payload = {
      amount: form.amount.trim(),
      currency: form.currency.trim().toUpperCase(),
      effectiveFrom: form.effectiveFrom,
      roleId: form.roleId || null,
      workId: form.workId || null,
    }
    activeModal.value === 'create'
      ? await createRate(payload)
      : await updateRate(target.value!.id, payload)
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
    await deleteRate(target.value!.id)
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
  const [r, w] = await Promise.all([
    fetchRoles().catch(() => ({ items: [] as WcRole[] })),
    fetchWorks().catch(() => ({ items: [] as WcWork[] })),
  ])
  roles.value = r.items
  works.value = w.items
  await load()
})
</script>
