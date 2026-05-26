import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initializes with null token and user', () => {
    const auth = useAuthStore()
    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(auth.isAuthenticated).toBe(false)
  })

  it('loadFromStorage reads token and user from localStorage', () => {
    localStorage.setItem('ecom-token', 'abc123')
    localStorage.setItem('ecom-user', JSON.stringify({
      id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin',
    }))

    const auth = useAuthStore()
    auth.loadFromStorage()

    expect(auth.token).toBe('abc123')
    expect(auth.user?.name).toBe('Admin User')
    expect(auth.isAuthenticated).toBe(true)
  })

  it('loadFromStorage does nothing when localStorage is empty', () => {
    const auth = useAuthStore()
    auth.loadFromStorage()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
  })

  it('isAdmin is true only for admin role', () => {
    localStorage.setItem('ecom-token', 'tok')
    localStorage.setItem('ecom-user', JSON.stringify({ id: 1, name: 'A', email: 'a@a.com', role: 'admin' }))

    const auth = useAuthStore()
    auth.loadFromStorage()

    expect(auth.isAdmin).toBe(true)
    expect(auth.isManager).toBe(true)
  })

  it('isManager is true for manager role but isAdmin is false', () => {
    localStorage.setItem('ecom-token', 'tok')
    localStorage.setItem('ecom-user', JSON.stringify({ id: 2, name: 'M', email: 'm@m.com', role: 'manager' }))

    const auth = useAuthStore()
    auth.loadFromStorage()

    expect(auth.isAdmin).toBe(false)
    expect(auth.isManager).toBe(true)
  })

  it('isAdmin and isManager are false for customer role', () => {
    localStorage.setItem('ecom-token', 'tok')
    localStorage.setItem('ecom-user', JSON.stringify({ id: 3, name: 'C', email: 'c@c.com', role: 'customer' }))

    const auth = useAuthStore()
    auth.loadFromStorage()

    expect(auth.isAdmin).toBe(false)
    expect(auth.isManager).toBe(false)
  })

  it('handleOAuthCallback sets token and user and persists to localStorage', () => {
    const auth = useAuthStore()
    auth.handleOAuthCallback({
      token: 'new-token',
      user: { id: 4, name: 'New', email: 'new@new.com', role: 'customer' },
    })

    expect(auth.token).toBe('new-token')
    expect(auth.user?.email).toBe('new@new.com')
    expect(localStorage.getItem('ecom-token')).toBe('new-token')
  })

  it('logout clears store and localStorage', () => {
    localStorage.setItem('ecom-token', 'tok')
    localStorage.setItem('ecom-user', JSON.stringify({ id: 1, name: 'A', email: 'a@a.com', role: 'admin' }))

    const auth = useAuthStore()
    auth.loadFromStorage()
    auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(localStorage.getItem('ecom-token')).toBeNull()
    expect(localStorage.getItem('ecom-user')).toBeNull()
  })
})
