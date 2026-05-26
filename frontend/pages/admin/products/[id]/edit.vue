<template>
  <div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">Edit Product</h1>
    <ProductForm v-if="product" :initial="product" @submit="handleSubmit" />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })

const route = useRoute()
const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()

const { data: product } = useFetch(`/api/v1/products/${route.params.id}`, {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
})

useSeoMeta({ title: computed(() => `Edit ${product.value?.name ?? 'Product'} — Admin`) })

const handleSubmit = async (form: any) => {
  await $fetch(`/api/v1/products/${route.params.id}`, {
    method: 'PATCH',
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    headers: { Authorization: `Bearer ${auth.token}` },
    body: form,
  })
  router.push(useAdminPath('/products'))
}
</script>