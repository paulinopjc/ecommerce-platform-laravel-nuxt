import { USER_ROLE, MANAGER_AND_ABOVE } from '~/constants/enums'
import type { UserRole } from '~/constants/enums'

export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore()
  if (!auth.token) {
    return navigateTo('/login')
  }
  if (!MANAGER_AND_ABOVE.includes(auth.user?.role as UserRole)) {
    return navigateTo('/')
  }
})
