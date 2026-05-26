<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">Coupons</h1>
      <NuxtLink :to="useAdminPath('/coupons/create')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 min-h-[44px] flex items-center">
        + New Coupon
      </NuxtLink>
    </div>

    <div class="bg-white rounded-lg border overflow-hidden">
      <div v-if="pending" class="p-6 text-gray-500 text-sm">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Code</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Type</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Discount</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Uses</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="coupon in coupons?.data" :key="coupon.id">
            <td class="px-4 py-3 font-mono font-medium">{{ coupon.code }}</td>
            <td class="px-4 py-3 text-gray-600">{{ coupon.type }}</td>
            <td class="px-4 py-3 hidden sm:table-cell">
              {{ coupon.type === 'percentage' ? coupon.discount_value + '%' : '₱' + (coupon.discount_value / 100).toFixed(2) }}
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-gray-500">
              {{ coupon.current_uses ?? 0 }}{{ coupon.max_uses ? ' / ' + coupon.max_uses : '' }}
            </td>
            <td class="px-4 py-3">
              <span :class="coupon.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 rounded text-xs">
                {{ coupon.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="deleteCoupon(coupon.id)" class="text-red-500 hover:text-red-700 text-xs">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'Coupons — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()

const { data: coupons, pending, refresh } = useFetch('/api/v1/coupons', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
})

const deleteCoupon = async (id: number) => {
  if (!confirm('Delete this coupon?')) return
  await $fetch(`/api/v1/coupons/${id}`, {
    method: 'DELETE',
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    headers: { Authorization: `Bearer ${auth.token}` },
  })
  refresh()
}
</script>