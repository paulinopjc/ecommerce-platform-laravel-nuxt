interface AppConfig {
  roles: string[]
  order_statuses: string[]
  order_sources: string[]
  payment_methods: string[]
  payment_statuses: string[]
  coupon_types: string[]
}

export const useConfig = () => {
  const config = useRuntimeConfig()
  return useFetch<AppConfig>('/api/v1/config', {
    baseURL: config.public.apiBase,
    lazy: true,
  })
}