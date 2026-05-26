<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="bg-white rounded-lg border p-8 max-w-sm w-full">
      <h1 class="text-xl font-semibold mb-6">Sign In</h1>
      <div v-if="error" class="mb-4 text-sm text-red-600 bg-red-50 rounded px-3 py-2">
        {{ error }}
      </div>
      <a
        :href="googleAuthUrl"
        class="inline-flex items-center gap-3 w-full justify-center px-4 py-2.5 border rounded-md text-sm font-medium hover:bg-gray-50 min-h-[44px]"
      >
        <svg width="18" height="18" viewBox="0 0 48 48">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59a14.5 14.5 0 0 1 0-9.18l-7.98-6.19a24.01 24.01 0 0 0 0 21.56l7.98-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        Sign in with Google
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

const error = computed(() => (route.query.error as string) || null)
const googleAuthUrl = computed(() => {
  const redirect = (route.query.redirect as string) || '/'
  return `${config.public.apiUrl}/auth/google?mode=customer&redirect=${encodeURIComponent(redirect)}`
})
</script>