export default defineNuxtConfig({
  devtools: { enabled: true },
  srcDir: '.',
  modules: ['@pinia/nuxt', '@nuxtjs/tailwindcss'],
  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      adminPrefix: process.env.NUXT_PUBLIC_ADMIN_PREFIX || 'admin',
    },
  },
  routeRules: {
    '/account/**': { ssr: false },
    '/admin/**': { ssr: false },
  },
  app: {
    head: {
      titleTemplate: '%s - E-Commerce Platform',
    },
  },
  compatibilityDate: '2025-01-01',
})