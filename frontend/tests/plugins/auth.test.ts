import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'

const loadFromStorageMock         = vi.fn()
const customerLoadFromStorageMock = vi.fn()

mockNuxtImport('useAuthStore', () => () => ({
  loadFromStorage: loadFromStorageMock,
}))

mockNuxtImport('useCustomerStore', () => () => ({
  loadFromStorage: customerLoadFromStorageMock,
}))

describe('auth.client plugin', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    loadFromStorageMock.mockClear()
    customerLoadFromStorageMock.mockClear()
  })

  it('calls loadFromStorage on both auth store and customer store', async () => {
    const { default: plugin } = await import('~/plugins/auth.client')
    plugin({ provide: vi.fn() } as any)
    expect(loadFromStorageMock).toHaveBeenCalledOnce()
    expect(customerLoadFromStorageMock).toHaveBeenCalledOnce()
  })
})
