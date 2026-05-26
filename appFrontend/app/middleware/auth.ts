export default defineNuxtRouteMiddleware(() => {
  const { accessToken } = useAuth()
  if (!accessToken.value) {
    return navigateTo('/login')
  }
})
