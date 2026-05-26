<template>
  <div class="min-h-screen flex items-center justify-center">
    <p v-if="error" class="text-red-600">{{ error }}</p>
    <p v-else class="text-gray-500">Signing in...</p>
  </div>
</template>

<script setup lang="ts">
import { MANAGER_AND_ABOVE } from '~/constants/enums'
import type { UserRole } from '~/constants/enums'

const router        = useRouter()
const auth          = useAuthStore()
const customerStore = useCustomerStore()
const error         = ref<string | null>(null)

onMounted(() => {
  const hash     = window.location.hash.substring(1)
  const params   = new URLSearchParams(hash)
  const token    = params.get('token')
  const userStr  = params.get('user')
  const redirect = params.get('redirect') || '/'
  const type     = params.get('type') // 'customer' | 'user'

  window.history.replaceState(null, '', '/auth/callback')

  if (!token || !userStr) {
    error.value = 'Sign-in failed.'
    return
  }

  try {
    const principal = JSON.parse(userStr)

    if (type === 'user') {
      // Backoffice flow — verify role before storing
      if (!MANAGER_AND_ABOVE.includes(principal.role as UserRole)) {
        router.replace('/admin/login?error=' + encodeURIComponent('Access denied. Admin accounts only.'))
        return
      }
      auth.handleOAuthCallback({ user: principal, token })
    } else {
      // Customer (storefront) flow
      customerStore.handleOAuthCallback({ customer: principal, token })
    }

    router.replace(redirect)
  } catch {
    error.value = 'Sign-in failed.'
  }
})
</script>