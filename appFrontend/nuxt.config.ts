// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css',
  ],
  app: {
    head: {
      title: 'Timesheet',
    },
  },
  routeRules: {
    '/api/**': { proxy: 'http://corpo-ts-nginx/api/**' },
  },
})
