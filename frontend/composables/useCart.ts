export const useCart = () => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  const cart = useState<any>('cart', () => null)

  const fetchCart = async () => {
    if (!auth.token) return
    const data = await $fetch('/api/v1/cart', {
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
    })
    cart.value = data
  }

  const addItem = async (variantId: number, quantity: number) => {
    await $fetch('/api/v1/cart/items', {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: { variant_id: variantId, quantity },
    })
    await fetchCart()
  }

  const removeItem = async (itemId: number) => {
    await $fetch(`/api/v1/cart/items/${itemId}`, {
      method: 'DELETE',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
    })
    await fetchCart()
  }

  const itemCount = computed(() =>
    cart.value?.items?.reduce((sum: number, i: any) => sum + i.quantity, 0) ?? 0
  )

  return { cart, fetchCart, addItem, removeItem, itemCount }
}
