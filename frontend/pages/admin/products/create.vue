<template>
  <div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">New Product</h1>
    <ProductForm @submit="handleSubmit" />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'New Product — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()

const handleSubmit = async (form: any) => {
  await $fetch('/api/v1/products', {
    method: 'POST',
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    headers: { Authorization: `Bearer ${auth.token}` },
    body: form,
  })
  router.push(useAdminPath('/products'))
}
</script>