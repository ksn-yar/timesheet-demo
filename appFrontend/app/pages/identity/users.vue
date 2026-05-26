<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h4 mb-0"><i class="bi bi-people me-2"></i>Пользователи</h1>
      <button class="btn btn-primary btn-sm" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Создать пользователя
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
              <th>Имя</th>
              <th>Email</th>
              <th>Роль</th>
              <th>Группа</th>
              <th>Должность</th>
              <th>Статус</th>
              <th class="text-end" style="width: 160px;">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="users.length === 0">
              <td colspan="7" class="text-center text-muted py-4">Пользователи не найдены</td>
            </tr>
            <tr v-for="user in users" :key="user.id">
              <td class="align-middle fw-medium">{{ user.name }}</td>
              <td class="align-middle text-muted small">{{ user.email }}</td>
              <td class="align-middle">
                <span :class="roleBadgeClass(user.systemRole)" class="badge">
                  {{ user.systemRoleLabel }}
                </span>
              </td>
              <td class="align-middle">{{ user.groupName ?? '—' }}</td>
              <td class="align-middle">{{ user.roleName ?? '—' }}</td>
              <td class="align-middle">
                <span :class="user.isActive ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ user.isActive ? 'Активен' : 'Неактивен' }}
                </span>
              </td>
              <td class="align-middle text-end">
                <button class="btn btn-sm btn-outline-secondary me-1" title="Редактировать" @click="openEdit(user)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary me-1" title="Сменить группу" @click="openChangeGroup(user)">
                  <i class="bi bi-diagram-3"></i>
                </button>
                <button
                  v-if="user.isActive"
                  class="btn btn-sm btn-outline-warning me-1"
                  title="Деактивировать"
                  @click="openDeactivate(user)"
                >
                  <i class="bi bi-person-dash"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Удалить" @click="openDelete(user)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модал создания пользователя -->
    <Teleport to="body">
      <template v-if="activeModal === 'create'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Новый пользователь</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Имя <span class="text-danger">*</span></label>
                  <input v-model="createForm.name" type="text" class="form-control" placeholder="Иван Иванов" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input v-model="createForm.email" type="email" class="form-control" placeholder="user@example.com" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Пароль <span class="text-danger">*</span></label>
                  <input v-model="createForm.password" type="password" class="form-control" placeholder="••••••••" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Системная роль <span class="text-danger">*</span></label>
                  <select v-model="createForm.systemRole" class="form-select">
                    <option value="employee">Сотрудник</option>
                    <option value="manager">Менеджер</option>
                    <option value="admin">Администратор</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Группа</label>
                  <select v-model="createForm.groupId" class="form-select">
                    <option :value="null">— Без группы —</option>
                    <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Должность</label>
                  <select v-model="createForm.roleId" class="form-select">
                    <option :value="null">— Без должности —</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
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

    <!-- Модал редактирования пользователя -->
    <Teleport to="body">
      <template v-if="activeModal === 'edit'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Редактировать пользователя</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <div class="mb-3">
                  <label class="form-label">Имя <span class="text-danger">*</span></label>
                  <input v-model="editForm.name" type="text" class="form-control" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Системная роль <span class="text-danger">*</span></label>
                  <select v-model="editForm.systemRole" class="form-select">
                    <option value="employee">Сотрудник</option>
                    <option value="manager">Менеджер</option>
                    <option value="admin">Администратор</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Должность</label>
                  <select v-model="editForm.roleId" class="form-select">
                    <option :value="null">— Без должности —</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
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

    <!-- Модал смены группы -->
    <Teleport to="body">
      <template v-if="activeModal === 'group'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Сменить группу</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="text-muted small mb-3">Пользователь: <strong>{{ targetUser?.name }}</strong></p>
                <label class="form-label">Группа</label>
                <select v-model="groupForm.groupId" class="form-select">
                  <option :value="null">— Без группы —</option>
                  <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-primary" :disabled="submitting" @click="submitChangeGroup">
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

    <!-- Модал деактивации -->
    <Teleport to="body">
      <template v-if="activeModal === 'deactivate'">
        <div class="modal show d-block" tabindex="-1" @click.self="closeModal">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="bi bi-person-dash me-2"></i>Деактивация</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Деактивировать пользователя <strong>{{ targetUser?.name }}</strong>?</p>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">Отмена</button>
                <button class="btn btn-warning" :disabled="submitting" @click="submitDeactivate">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                  Деактивировать
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Удалить</h5>
                <button class="btn-close" @click="closeModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
                <p class="mb-0">Удалить пользователя <strong>{{ targetUser?.name }}</strong>? Действие нельзя отменить.</p>
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
import type { User } from '~/composables/useIdentityUsers'
import type { Group } from '~/composables/useIdentityGroups'

definePageMeta({ middleware: 'auth' })

type ModalType = 'create' | 'edit' | 'group' | 'deactivate' | 'delete' | null

interface Role {
  id: string
  name: string
}

const { authFetch } = useAuth()
const { fetchUsers, createUser, updateUser, deleteUser, deactivateUser, changeUserGroup } = useIdentityUsers()
const { fetchGroups } = useIdentityGroups()

const users = ref<User[]>([])
const groups = ref<Group[]>([])
const roles = ref<Role[]>([])
const loading = ref(true)
const loadError = ref<string | null>(null)
const activeModal = ref<ModalType>(null)
const targetUser = ref<User | null>(null)
const submitting = ref(false)
const formError = ref<string | null>(null)

const createForm = reactive({
  name: '',
  email: '',
  password: '',
  systemRole: 'employee',
  groupId: null as string | null,
  roleId: null as string | null,
})

const editForm = reactive({
  name: '',
  systemRole: 'employee',
  roleId: null as string | null,
})

const groupForm = reactive({
  groupId: null as string | null,
})

const roleBadgeClass = (role: string) => ({
  badge: true,
  'bg-danger': role === 'admin',
  'bg-primary': role === 'manager',
  'bg-secondary': role === 'employee',
})

const loadData = async () => {
  loading.value = true
  loadError.value = null
  try {
    const [usersResult, groupsResult, rolesRes] = await Promise.all([
      fetchUsers(),
      fetchGroups(),
      authFetch('/api/work-catalog/roles?perPage=100'),
    ])
    users.value = usersResult.items
    groups.value = groupsResult.items
    if (rolesRes.ok) {
      const rolesData = await rolesRes.json() as { items: Role[] }
      roles.value = rolesData.items
    }
  } catch (e) {
    loadError.value = e instanceof Error ? e.message : 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  createForm.name = ''
  createForm.email = ''
  createForm.password = ''
  createForm.systemRole = 'employee'
  createForm.groupId = null
  createForm.roleId = null
  formError.value = null
  activeModal.value = 'create'
}

const openEdit = (user: User) => {
  editForm.name = user.name
  editForm.systemRole = user.systemRole
  editForm.roleId = user.roleId
  formError.value = null
  targetUser.value = user
  activeModal.value = 'edit'
}

const openChangeGroup = (user: User) => {
  groupForm.groupId = user.groupId
  formError.value = null
  targetUser.value = user
  activeModal.value = 'group'
}

const openDeactivate = (user: User) => {
  formError.value = null
  targetUser.value = user
  activeModal.value = 'deactivate'
}

const openDelete = (user: User) => {
  formError.value = null
  targetUser.value = user
  activeModal.value = 'delete'
}

const closeModal = () => {
  activeModal.value = null
  targetUser.value = null
}

const submitCreate = async () => {
  if (!createForm.name.trim() || !createForm.email.trim() || !createForm.password) {
    formError.value = 'Имя, email и пароль обязательны'
    return
  }
  submitting.value = true
  formError.value = null
  try {
    await createUser({
      name: createForm.name.trim(),
      email: createForm.email.trim(),
      password: createForm.password,
      systemRole: createForm.systemRole,
      groupId: createForm.groupId,
      roleId: createForm.roleId,
    })
    closeModal()
    await loadData()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitEdit = async () => {
  if (!editForm.name.trim()) {
    formError.value = 'Имя обязательно'
    return
  }
  if (!targetUser.value) return
  submitting.value = true
  formError.value = null
  try {
    await updateUser(targetUser.value.id, {
      name: editForm.name.trim(),
      systemRole: editForm.systemRole,
      roleId: editForm.roleId,
    })
    closeModal()
    await loadData()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitChangeGroup = async () => {
  if (!targetUser.value) return
  submitting.value = true
  formError.value = null
  try {
    await changeUserGroup(targetUser.value.id, groupForm.groupId)
    closeModal()
    await loadData()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitDeactivate = async () => {
  if (!targetUser.value) return
  submitting.value = true
  formError.value = null
  try {
    await deactivateUser(targetUser.value.id)
    closeModal()
    await loadData()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

const submitDelete = async () => {
  if (!targetUser.value) return
  submitting.value = true
  formError.value = null
  try {
    await deleteUser(targetUser.value.id)
    closeModal()
    await loadData()
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Произошла ошибка'
  } finally {
    submitting.value = false
  }
}

watch(activeModal, (val) => {
  if (import.meta.client) {
    document.body.style.overflow = val ? 'hidden' : ''
  }
})

onUnmounted(() => {
  if (import.meta.client) document.body.style.overflow = ''
})

onMounted(loadData)
</script>
