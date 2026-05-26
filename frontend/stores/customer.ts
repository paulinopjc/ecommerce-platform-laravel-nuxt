import { defineStore } from 'pinia'

interface Customer {
  id: number
  name: string
  email: string
}

export const useCustomerStore = defineStore('customer', () => {
  const customer = ref<Customer | null>(null)
  const token    = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function loadFromStorage() {
    if (import.meta.client) {
      token.value = localStorage.getItem('ecom-customer-token')
      const stored = localStorage.getItem('ecom-customer')
      if (stored) {
        try { customer.value = JSON.parse(stored) } catch { /* ignore */ }
      }
    }
  }

  function handleOAuthCallback(result: { customer: Customer; token: string }) {
    customer.value = result.customer
    token.value    = result.token
    localStorage.setItem('ecom-customer-token', result.token)
    localStorage.setItem('ecom-customer', JSON.stringify(result.customer))
  }

  function logout() {
    customer.value = null
    token.value    = null
    localStorage.removeItem('ecom-customer-token')
    localStorage.removeItem('ecom-customer')
  }

  return { customer, token, isAuthenticated, loadFromStorage, handleOAuthCallback, logout }
})
