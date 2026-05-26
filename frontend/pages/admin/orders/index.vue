<template>
  <div>
    <h1 class="text-xl font-semibold mb-6">Orders</h1>

    <div class="mb-4">
      <select v-model="statusFilter" class="border rounded px-3 py-2 text-sm min-h-[44px]">
        <option value="">All statuses</option>
        <option v-for="s in appConfig?.order_statuses" :key="s" :value="s">{{ s }}</option>
      </select>
    </div>

    <div class="bg-white rounded-lg border overflow-hidden">
      <div v-if="pending" class="p-6 text-gray-500 text-sm">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Order</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Customer</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Total</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="order in orders?.data" :key="order.id">
            <td class="px-4 py-3 font-medium">{{ order.order_number }}</td>
            <td class="px-4 py-3 hidden sm:table-cell text-gray-600">{{ order.user?.name }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ order.status }}</span>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">₱{{ (order.total_cents / 100).toFixed(2) }}</td>
            <td class="px-4 py-3 text-right">
              <NuxtLink :to="useAdminPath(`/orders/${order.id}`)" class="text-blue-600 hover:underline text-xs">View</NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'Orders — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const statusFilter = ref('')

const { data: appConfig } = useConfig()

const params = computed(() => ({ status: statusFilter.value || undefined }))

const { data: orders, pending } = useFetch('/api/v1/orders', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
  query: params,
  watch: [params],
})
</script>