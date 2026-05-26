export const useProducts = (params?: Ref<Record<string, any>>) => {
  const config = useRuntimeConfig()

  return useFetch('/api/v1/products', {
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    query: params,
    watch: params ? [params] : undefined,
  })
}
