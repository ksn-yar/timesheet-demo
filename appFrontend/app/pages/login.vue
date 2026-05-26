<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title mb-4 text-center">
              <i class="bi bi-person-lock me-2"></i>Вход
            </h2>

            <div v-if="errorMessage" class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
            </div>

            <form @submit.prevent="handleLogin">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    placeholder="user@example.com"
                    required
                    :disabled="loading"
                  />
                </div>
              </div>

              <div class="mb-4">
                <label for="password" class="form-label">Пароль</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                    :disabled="loading"
                  />
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-else class="bi bi-box-arrow-in-right me-2"></i>
                Войти
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { setTokens } = useAuth()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const errorMessage = ref<string | null>(null)

async function handleLogin() {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: form.email, password: form.password }),
    })

    const result = await response.json()

    if (!response.ok) {
      errorMessage.value = result.error ?? `Ошибка ${response.status}`
      return
    }

    setTokens(result.token, result.refresh_token)
    router.push('/')
  } catch {
    errorMessage.value = 'Не удалось подключиться к серверу'
  } finally {
    loading.value = false
  }
}
</script>
