<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title mb-4 text-center">
              <i class="bi bi-box-arrow-right me-2"></i>Выход
            </h2>

            <p class="text-muted text-center mb-4">Вы уверены, что хотите выйти из системы?</p>

            <div v-if="errorMessage" class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-danger" :disabled="loading" @click="handleLogout">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-else class="bi bi-box-arrow-right me-2"></i>
                Выйти
              </button>

              <NuxtLink to="/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Отмена
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { authFetch, refreshToken, clearTokens } = useAuth()
const router = useRouter()

const loading = ref(false)
const errorMessage = ref<string | null>(null)

async function handleLogout() {
  loading.value = true
  errorMessage.value = null

  try {
    await authFetch('/api/auth/logout', {
      method: 'POST',
      body: JSON.stringify({ refresh_token: refreshToken.value }),
    })
  } catch {
    // Даже при ошибке сети очищаем токены и редиректим
  } finally {
    clearTokens()
    router.push('/login')
  }
}
</script>
