export const useAdminPath = (path = '') => {
  const config = useRuntimeConfig()
  return `/${config.public.adminPrefix}${path}`
}