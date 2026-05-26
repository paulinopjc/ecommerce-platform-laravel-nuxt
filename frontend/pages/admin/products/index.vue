<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">Products</h1>
      <NuxtLink :to="useAdminPath('/products/create')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 min-h-[44px] flex items-center">
        + New Product
      </NuxtLink>
    </div>

    <div class="bg-white rounded-lg border overflow-hidden">
      <div v-if="pending" class="p-6 text-gray-500 text-sm">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Name</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Price</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="product in products?.data" :key="product.id">
            <td class="px-4 py-3">
              <p class="font-medium">{{ product.name }}</p>
              <p class="text-xs text-gray-400">{{ product.slug }}</p>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell">₱{{ (product.base_price_cents / 100).toFixed(2) }}</td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 rounded text-xs">
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <NuxtLink :to="useAdminPath(`/products/${product.id}/edit`)" class="text-blue-600 hover:underline text-xs mr-3">Edit</NuxtLink>
              <button @click="deleteProduct(product.id)" class="text-red-500 hover:text-red-700 text-xs">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'Products — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()

const { data: products, pending, refresh } = useFetch('/api/v1/products', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
})

const deleteProduct = async (id: number) => {
  if (!confirm('Delete this product?')) return
  await $fetch(`/api/v1/products/${id}`, {
    method: 'DELETE',
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    headers: { Authorization: `Bearer ${auth.token}` },
  })
  refresh()
}
</script>