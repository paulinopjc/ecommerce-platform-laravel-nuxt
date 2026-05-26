export default defineNuxtRouteMiddleware((to) => {
  const customer = useCustomerStore()
  if (!customer.token) {
    return navigateTo('/login?redirect=' + encodeURIComponent(to.path))
  }
})
