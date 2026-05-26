export default defineNuxtPlugin(() => {
  useAuthStore().loadFromStorage()
  useCustomerStore().loadFromStorage()
})
