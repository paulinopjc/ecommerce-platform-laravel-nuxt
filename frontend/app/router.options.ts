import type { RouterConfig } from '@nuxt/schema'

export default <RouterConfig>{
  routes: (_routes) => {
    const prefix = process.env.NUXT_PUBLIC_ADMIN_PREFIX || 'admin'
    if (prefix === 'admin') return _routes
    return _routes.map(route => ({
      ...route,
      path: route.path?.startsWith('/admin')
        ? route.path.replace('/admin', `/${prefix}`)
        : route.path,
    }))
  },
}