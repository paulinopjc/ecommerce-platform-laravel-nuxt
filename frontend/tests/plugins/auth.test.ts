import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'

const loadFromStorageMock = vi.fn()

mockNuxtImport('useAuthStore', () => () => ({
  loadFromStorage: loadFromStorageMock,
}))

describe('auth.client plugin', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    loadFromStorageMock.mockClear()
  })

  it('calls loadFromStorage when the plugin initialises', async () => {
    const { default: plugin } = await import('~/plugins/auth.client')
    // Nuxt plugins receive a nuxtApp argument; provide is the only method used
    plugin({ provide: vi.fn() } as any)
    expect(loadFromStorageMock).toHaveBeenCalledOnce()
  })
})
