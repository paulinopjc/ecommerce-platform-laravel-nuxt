<template>
  <div class="min-h-screen flex items-center justify-center">
    <p v-if="error" class="text-red-600">{{ error }}</p>
    <p v-else class="text-gray-500">Signing in...</p>
  </div>
</template>

<script setup lang="ts">
const router = useRouter()
const auth = useAuthStore()
const error = ref<string | null>(null)

onMounted(() => {
  const hash = window.location.hash.substring(1)
  const params = new URLSearchParams(hash)
  const token = params.get('token')
  const userStr = params.get('user')
  const redirect = params.get('redirect') || '/'

  window.history.replaceState(null, '', '/auth/callback')

  if (!token || !userStr) {
    error.value = 'Sign-in failed.'
    return
  }

  try {
    const user = JSON.parse(userStr)
    auth.handleOAuthCallback({ user, token })
    router.replace(redirect)
  } catch {
    error.value = 'Sign-in failed.'
  }
})
</script>