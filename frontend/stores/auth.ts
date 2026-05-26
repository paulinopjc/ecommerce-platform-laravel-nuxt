import { defineStore } from 'pinia'
import { USER_ROLE, MANAGER_AND_ABOVE, type UserRole } from '~/constants/enums'

interface User {
  id: number
  name: string
  email: string
  role: UserRole
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === USER_ROLE.ADMIN)
  const isManager = computed(() =>
    MANAGER_AND_ABOVE.includes(user.value?.role as UserRole)
  )

  function loadFromStorage() {
    if (import.meta.client) {
      token.value = localStorage.getItem('ecom-token')
      const stored = localStorage.getItem('ecom-user')
      if (stored) {
        try { user.value = JSON.parse(stored) } catch { /* ignore */ }
      }
    }
  }

  function handleOAuthCallback(result: { user: User; token: string }) {
    user.value = result.user
    token.value = result.token
    localStorage.setItem('ecom-token', result.token)
    localStorage.setItem('ecom-user', JSON.stringify(result.user))
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('ecom-token')
    localStorage.removeItem('ecom-user')
  }

  return {
    user, token, isAuthenticated, isAdmin, isManager,
    loadFromStorage, handleOAuthCallback, logout,
  }
})