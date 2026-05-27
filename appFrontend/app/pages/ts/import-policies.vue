<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h4 mb-0"><i class="bi bi-cloud-download me-2"></i>Политики импорта</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать политику
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
              <th>Система-источник</th>
              <th style="width:130px;">Ред. импорт.</th>
              <th style="width:100px;">Статус</th>
              <th class="text-end" style="width:150px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="policies.length === 0">
              <td colspan="5" class="text-center text-muted py-4">Политики не настроены</td>
            </tr>
            <tr v-for="p in policies" :key="p.id">
              <td class="align-middle fw-medium">{{ p.name }}</td>
              <td class="align-middle text-muted font-monospace small">{{ p.sourceSystem }}</td>
              <td class="align-middle">
                <span :class="p.allowEdit ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ p.allowEdit ? 'Разрешено' : 'Запрещено' }}
                </span>
              </td>
              <td class="align-middle">
                <span :class="p.isActive ? 'badge bg-success' : 'badge bg-light text-secondary border'">
                  {{ p.isActive ? 'Активна' : 'Неактивна' }}
                </span>
              </td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-primary me-1" title="Запустить импорт" @click="openRunImport(p)">
                  <i class="bi bi-play-fill"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(p)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button
                  :class="p.isActive ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-outline-success'"
                  :title="p.isActive ? 'Деактивировать' : 'Активировать'"
                  @click="toggleActive(p)"
                >
                  <i :class="p.isActive ? 'bi bi-pause-fill' : 'bi bi-check-lg'"></i>
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
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Новая политика импорта</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" placeholder="Например: Jira Import" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Система-источник <span class="text-danger">*</span></label>
                  <input v-model="form.sourceSystem" type="text" class="form-control" placeholder="Например: jira" />
                </div>
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input id="createAllowEdit" v-model="form.allowEdit" class="form-check-input" type="checkbox" role="switch" />
                    <label class="form-check-label" for="createAllowEdit">Разрешить редактирование импортированных тикетов</label>
                  </div>
                </div>
                <hr />
                <h6 class="text-muted mb-3">Правила маппинга полей</h6>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Employee mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.empSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField (напр. user_email)" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.empMatchBy" class="form-select form-select-sm">
                      <option value="email">email</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Task mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.taskSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField (напр. task_code)" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.taskMatchBy" class="form-select form-select-sm">
                      <option value="name">name</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Work mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.workSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField (напр. work_type)" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.workMatchBy" class="form-select form-select-sm">
                      <option value="name">name</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small">Поле даты <span class="text-danger">*</span></label>
                    <input v-model="form.dateField" type="text" class="form-control form-control-sm" placeholder="напр. worked_date" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Поле часов <span class="text-danger">*</span></label>
                    <input v-model="form.hoursField" type="text" class="form-control form-control-sm" placeholder="напр. duration_hours" />
                  </div>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small">Поле комментария</label>
                    <input v-model="form.commentField" type="text" class="form-control form-control-sm" placeholder="напр. notes (пусто = не маппится)" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Поле внешнего ID <span class="text-danger">*</span></label>
                    <input v-model="form.externalIdField" type="text" class="form-control form-control-sm" placeholder="напр. entry_id" />
                  </div>
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
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Редактировать политику</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Название</label>
                  <input :value="target?.name" type="text" class="form-control" disabled />
                  <div class="form-text text-muted">Название нельзя изменить.</div>
                </div>
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input id="editAllowEdit" v-model="form.allowEdit" class="form-check-input" type="checkbox" role="switch" />
                    <label class="form-check-label" for="editAllowEdit">Разрешить редактирование импортированных тикетов</label>
                  </div>
                </div>
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input id="editIsActive" v-model="form.isActive" class="form-check-input" type="checkbox" role="switch" />
                    <label class="form-check-label" for="editIsActive">Активна</label>
                  </div>
                </div>
                <hr />
                <h6 class="text-muted mb-3">Правила маппинга полей</h6>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Employee mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.empSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.empMatchBy" class="form-select form-select-sm">
                      <option value="email">email</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Task mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.taskSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.taskMatchBy" class="form-select form-select-sm">
                      <option value="name">name</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-12"><span class="fw-semibold small">Work mapping</span></div>
                  <div class="col-md-6">
                    <input v-model="form.workSourceField" type="text" class="form-control form-control-sm" placeholder="sourceField" />
                  </div>
                  <div class="col-md-6">
                    <select v-model="form.workMatchBy" class="form-select form-select-sm">
                      <option value="name">name</option>
                      <option value="externalId">externalId</option>
                    </select>
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small">Поле даты <span class="text-danger">*</span></label>
                    <input v-model="form.dateField" type="text" class="form-control form-control-sm" placeholder="напр. worked_date" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Поле часов <span class="text-danger">*</span></label>
                    <input v-model="form.hoursField" type="text" class="form-control form-control-sm" placeholder="напр. duration_hours" />
                  </div>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small">Поле комментария</label>
                    <input v-model="form.commentField" type="text" class="form-control form-control-sm" placeholder="напр. notes (пусто = не маппится)" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Поле внешнего ID <span class="text-danger">*</span></label>
                    <input v-model="form.externalIdField" type="text" class="form-control form-control-sm" placeholder="напр. entry_id" />
                  </div>
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

    <!-- Модал запуска импорта -->
    <Teleport to="body">
      <template v-if="activeModal === 'run'">
        <div class="modal show d-block" tabindex="-1" @click.self="!submitting && closeModal()">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-play-fill me-2"></i>Запустить импорт</h5>
                <button class="btn-close" :disabled="submitting" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div v-if="importSummary" class="alert alert-success py-2">
                  <div class="fw-semibold mb-1">Импорт завершён</div>
                  <div>Импортировано: <strong>{{ importSummary.imported }}</strong></div>
                  <div>Дубликатов пропущено: <strong>{{ importSummary.duplicates }}</strong></div>
                  <div>Ошибок: <strong>{{ importSummary.errors }}</strong></div>
                </div>
                <p class="text-muted small mb-3">
                  Политика: <strong>{{ target?.name }}</strong>
                  <br />Источник: <code>{{ target?.sourceSystem }}</code>
                </p>
                <div class="mb-3">
                  <label class="form-label">Дата с <span class="text-danger">*</span></label>
                  <input v-model="runForm.dateFrom" type="date" class="form-control" :disabled="!!importSummary" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Дата по <span class="text-danger">*</span></label>
                  <input v-model="runForm.dateTo" type="date" class="form-control" :disabled="!!importSummary" />
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" :disabled="submitting" @click="closeModal">
                  {{ importSummary ? 'Закрыть' : 'Отмена' }}
                </button>
                <button v-if="!importSummary" class="btn btn-primary" :disabled="submitting" @click="submitRunImport">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  <i v-else class="bi bi-play-fill me-1"></i>
                  Запустить
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
import type { TsImportPolicy, TsImportSummary } from '~/composables/useTimesheet'

definePageMeta({ middleware: 'auth' })

const { isAdmin } = useAuth()
const { fetchImportPolicies, createImportPolicy, updateImportPolicy, runImport } = useTimesheet()

if (!isAdmin()) {
  await navigateTo('/')
}

type ModalType = 'create' | 'edit' | 'run' | null

const policies = ref<TsImportPolicy[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const target = ref<TsImportPolicy | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)
const importSummary = ref<TsImportSummary | null>(null)
const runForm = reactive({ dateFrom: '', dateTo: '' })

const form = reactive({
  name: '',
  sourceSystem: '',
  allowEdit: false,
  isActive: false,
  empSourceField: '',
  empMatchBy: 'email',
  taskSourceField: '',
  taskMatchBy: 'externalId',
  workSourceField: '',
  workMatchBy: 'name',
  dateField: '',
  hoursField: '',
  commentField: '',
  externalIdField: '',
})

const resetForm = () => {
  form.name = ''
  form.sourceSystem = ''
  form.allowEdit = false
  form.isActive = false
  form.empSourceField = ''
  form.empMatchBy = 'email'
  form.taskSourceField = ''
  form.taskMatchBy = 'externalId'
  form.workSourceField = ''
  form.workMatchBy = 'name'
  form.dateField = ''
  form.hoursField = ''
  form.commentField = ''
  form.externalIdField = ''
}

const fillFormFromPolicy = (p: TsImportPolicy) => {
  const r = p.mappingRules
  form.name = p.name
  form.sourceSystem = p.sourceSystem
  form.allowEdit = p.allowEdit
  form.isActive = p.isActive
  form.empSourceField = r.employeeMapping?.sourceField ?? ''
  form.empMatchBy = r.employeeMapping?.matchBy ?? 'email'
  form.taskSourceField = r.taskMapping?.sourceField ?? ''
  form.taskMatchBy = r.taskMapping?.matchBy ?? 'externalId'
  form.workSourceField = r.workMapping?.sourceField ?? ''
  form.workMatchBy = r.workMapping?.matchBy ?? 'name'
  form.dateField = r.dateField ?? ''
  form.hoursField = r.hoursField ?? ''
  form.commentField = r.commentField ?? ''
  form.externalIdField = r.externalIdField ?? ''
}

const buildMappingRules = () => ({
  employeeMapping: { sourceField: form.empSourceField, matchBy: form.empMatchBy },
  taskMapping: { sourceField: form.taskSourceField, matchBy: form.taskMatchBy },
  workMapping: { sourceField: form.workSourceField, matchBy: form.workMatchBy },
  dateField: form.dateField,
  hoursField: form.hoursField,
  commentField: form.commentField.trim() || null,
  externalIdField: form.externalIdField,
})

const validateMapping = (): string | null => {
  if (!form.empSourceField.trim()) return 'Укажите sourceField для Employee mapping'
  if (!form.taskSourceField.trim()) return 'Укажите sourceField для Task mapping'
  if (!form.workSourceField.trim()) return 'Укажите sourceField для Work mapping'
  if (!form.dateField.trim()) return 'Укажите поле даты (dateField)'
  if (!form.hoursField.trim()) return 'Укажите поле часов (hoursField)'
  if (!form.externalIdField.trim()) return 'Укажите поле внешнего ID (externalIdField)'
  return null
}

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    policies.value = (await fetchImportPolicies()).items
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetForm()
  formError.value = null
  activeModal.value = 'create'
}

const openEdit = (item: TsImportPolicy) => {
  fillFormFromPolicy(item)
  formError.value = null
  target.value = item
  activeModal.value = 'edit'
}

const openRunImport = (item: TsImportPolicy) => {
  importSummary.value = null
  formError.value = null
  runForm.dateFrom = ''
  runForm.dateTo = ''
  target.value = item
  activeModal.value = 'run'
}

const closeModal = () => { activeModal.value = null; target.value = null; importSummary.value = null }

const submitCreate = async () => {
  if (!form.name.trim()) { formError.value = 'Название обязательно'; return }
  if (!form.sourceSystem.trim()) { formError.value = 'Система-источник обязательна'; return }
  const err = validateMapping()
  if (err) { formError.value = err; return }
  submitting.value = true
  formError.value = null
  try {
    await createImportPolicy({
      name: form.name.trim(),
      sourceSystem: form.sourceSystem.trim(),
      mappingRules: buildMappingRules(),
      allowEdit: form.allowEdit,
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
  const err = validateMapping()
  if (err) { formError.value = err; return }
  submitting.value = true
  formError.value = null
  try {
    await updateImportPolicy(target.value!.id, {
      mappingRules: buildMappingRules(),
      allowEdit: form.allowEdit,
      isActive: form.isActive,
    })
    closeModal()
    await load()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const toggleActive = async (item: TsImportPolicy) => {
  try {
    await updateImportPolicy(item.id, { isActive: !item.isActive })
    await load()
  } catch (e) {
    alert(e instanceof Error ? e.message : 'Произошла ошибка')
  }
}

const submitRunImport = async () => {
  if (!runForm.dateFrom) { formError.value = 'Укажите дату начала'; return }
  if (!runForm.dateTo) { formError.value = 'Укажите дату окончания'; return }
  submitting.value = true
  formError.value = null
  try {
    importSummary.value = await runImport(target.value!.id, { dateFrom: runForm.dateFrom, dateTo: runForm.dateTo })
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка при импорте'
  } finally {
    submitting.value = false
  }
}

watch(activeModal, val => { if (import.meta.client) document.body.style.overflow = val ? 'hidden' : '' })
onUnmounted(() => { if (import.meta.client) document.body.style.overflow = '' })
onMounted(load)
</script>
