import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

describe('useCustomerStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initializes with null token and customer', () => {
    const store = useCustomerStore()
    expect(store.token).toBeNull()
    expect(store.customer).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('loadFromStorage reads from ecom-customer-token and ecom-customer', () => {
    localStorage.setItem('ecom-customer-token', 'cust-abc')
    localStorage.setItem('ecom-customer', JSON.stringify({ id: 1, name: 'Jane', email: 'jane@example.com' }))

    const store = useCustomerStore()
    store.loadFromStorage()

    expect(store.token).toBe('cust-abc')
    expect(store.customer?.name).toBe('Jane')
    expect(store.isAuthenticated).toBe(true)
  })

  it('loadFromStorage does nothing when localStorage is empty', () => {
    const store = useCustomerStore()
    store.loadFromStorage()

    expect(store.token).toBeNull()
    expect(store.customer).toBeNull()
  })

  it('handleOAuthCallback sets token and customer and persists to localStorage', () => {
    const store = useCustomerStore()
    store.handleOAuthCallback({
      token: 'new-cust-token',
      customer: { id: 2, name: 'John', email: 'john@example.com' },
    })

    expect(store.token).toBe('new-cust-token')
    expect(store.customer?.email).toBe('john@example.com')
    expect(localStorage.getItem('ecom-customer-token')).toBe('new-cust-token')
  })

  it('logout clears store and localStorage', () => {
    localStorage.setItem('ecom-customer-token', 'cust-tok')
    localStorage.setItem('ecom-customer', JSON.stringify({ id: 1, name: 'Jane', email: 'jane@example.com' }))

    const store = useCustomerStore()
    store.loadFromStorage()
    store.logout()

    expect(store.token).toBeNull()
    expect(store.customer).toBeNull()
    expect(localStorage.getItem('ecom-customer-token')).toBeNull()
    expect(localStorage.getItem('ecom-customer')).toBeNull()
  })

  it('does not collide with admin auth store keys', () => {
    setActivePinia(createPinia())

    const auth     = useAuthStore()
    const customer = useCustomerStore()

    auth.handleOAuthCallback({
      token: 'admin-tok',
      user: { id: 1, name: 'Admin', email: 'admin@example.com', role: 'admin' },
    })
    customer.handleOAuthCallback({
      token: 'cust-tok',
      customer: { id: 2, name: 'Customer', email: 'cust@example.com' },
    })

    expect(localStorage.getItem('ecom-token')).toBe('admin-tok')
    expect(localStorage.getItem('ecom-customer-token')).toBe('cust-tok')
    expect(auth.token).toBe('admin-tok')
    expect(customer.token).toBe('cust-tok')
  })
})
