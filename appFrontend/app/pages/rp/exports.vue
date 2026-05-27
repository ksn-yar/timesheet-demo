<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="bi bi-file-earmark-arrow-down me-2"></i>Выгрузки отчётов</h1>
      <NuxtLink to="/rp/reports" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>К отчётам
      </NuxtLink>
    </div>

    <!-- Фильтр по формату -->
    <div class="mb-3 d-flex gap-2 align-items-center">
      <label class="form-label mb-0 small">Формат:</label>
      <select v-model="filterFormat" class="form-select form-select-sm" style="width:120px;" @change="load">
        <option value="">Все</option>
        <option value="csv">CSV</option>
        <option value="xlsx">XLSX</option>
        <option value="pdf">PDF</option>
      </select>
      <button v-if="filterFormat" class="btn btn-sm btn-outline-secondary" @click="filterFormat = ''; load()">
        <i class="bi bi-x-lg"></i>
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
              <th style="width:80px;">Формат</th>
              <th style="width:170px;">Дата генерации</th>
              <th>Отчёты</th>
              <th class="text-end" style="width:110px;">Скачать</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="exports.length === 0">
              <td colspan="4" class="text-center text-muted py-4">Выгрузок не найдено</td>
            </tr>
            <tr v-for="e in exports" :key="e.id">
              <td class="align-middle">
                <span :class="formatBadgeClass(e.format)">{{ e.format.toUpperCase() }}</span>
              </td>
              <td class="align-middle text-muted small text-nowrap">{{ formatDatetime(e.generatedAt) }}</td>
              <td class="align-middle text-muted small">{{ e.reportIds.length }} отчёт(ов)</td>
              <td class="align-middle text-end">
                <button
                  class="btn btn-sm btn-outline-success"
                  :disabled="downloading === e.id"
                  title="Скачать файл"
                  @click="download(e)"
                >
                  <span v-if="downloading === e.id" class="spinner-border spinner-border-sm" role="status"></span>
                  <i v-else class="bi bi-download"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="total > 0" class="card-footer text-muted small d-flex align-items-center justify-content-between">
        <span>Показано {{ exports.length }} из {{ total }}</span>
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
  </div>
</template>

<script setup lang="ts">
import type { RpExport } from '~/composables/useReporting'

definePageMeta({ middleware: 'auth' })

const { fetchExports, downloadExport } = useReporting()

const exports = ref<RpExport[]>([])
const total = ref(0)
const page = ref(1)
const perPage = 20
const totalPages = computed(() => Math.ceil(total.value / perPage))
const loading = ref(true)
const loadError = ref<string | null>(null)
const filterFormat = ref('')
const downloading = ref<string | null>(null)

const formatDatetime = (iso: string) => {
  try {
    return new Date(iso).toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const formatBadgeClass = (fmt: string) => {
  const map: Record<string, string> = { csv: 'badge bg-info text-dark', xlsx: 'badge bg-success', pdf: 'badge bg-danger' }
  return map[fmt] ?? 'badge bg-secondary'
}

const load = async () => {
  loading.value = true
  loadError.value = null
  try {
    const result = await fetchExports({
      format: filterFormat.value || undefined,
      page: page.value,
      perPage,
    })
    exports.value = result.items
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

const download = async (e: RpExport) => {
  downloading.value = e.id
  try {
    const filename = `export_${e.id.slice(0, 8)}.${e.format}`
    await downloadExport(e.id, filename)
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Не удалось скачать файл')
  } finally {
    downloading.value = null
  }
}

onMounted(load)
</script>
