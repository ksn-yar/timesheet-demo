<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title mb-4 text-center">
              <i class="bi bi-arrow-clockwise me-2"></i>Обновление токена
            </h2>

            <div v-if="successMessage" class="alert alert-success">
              <i class="bi bi-check-circle me-2"></i>{{ successMessage }}
            </div>

            <div v-if="errorMessage" class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-info text-white" :disabled="loading" @click="handleRefresh">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-else class="bi bi-arrow-clockwise me-2"></i>
                Обновить токен
              </button>

              <NuxtLink to="/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>На главную
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { refreshToken, setTokens, clearTokens } = useAuth()
const router = useRouter()

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

async function handleRefresh() {
  loading.value = true
  errorMessage.value = null
  successMessage.value = null

  try {
    const response = await fetch('/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refreshToken.value }),
    })

    const result = await response.json()

    if (!response.ok) {
      // Refresh token истёк или невалиден — разлогиниваем
      if (response.status === 401) {
        clearTokens()
        router.push('/login')
        return
      }
      errorMessage.value = result.error ?? `Ошибка ${response.status}`
      return
    }

    setTokens(result.token, result.refresh_token)
    successMessage.value = 'Токен успешно обновлён'
  } catch {
    errorMessage.value = 'Не удалось подключиться к серверу'
  } finally {
    loading.value = false
  }
}
</script>
