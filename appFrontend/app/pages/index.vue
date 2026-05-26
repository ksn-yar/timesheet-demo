<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="card-title mb-4">
              <i class="bi bi-house-door me-2"></i>Главная
            </h1>

            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
              </div>
            </div>

            <div v-else-if="error" class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
            </div>

            <div v-else>
              <pre class="bg-light p-3 rounded">{{ data }}</pre>
            </div>

            <div class="mt-4">
              <NuxtLink to="/logout" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Выйти
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { authFetch } = useAuth()

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<unknown>(null)

onMounted(async () => {
  try {
    const response = await authFetch('/api/')

    if (!response.ok) return // 401 — authFetch уже сделал redirect на /login

    data.value = await response.json()
  } catch {
    error.value = 'Не удалось подключиться к серверу'
  } finally {
    loading.value = false
  }
})
</script>
