<template>
  <div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
      <NuxtLink :to="useAdminPath('/orders')" class="text-blue-600 hover:underline text-sm">← Orders</NuxtLink>
      <h1 class="text-xl font-semibold">{{ order?.order_number }}</h1>
    </div>

    <div v-if="pending" class="text-gray-500 text-sm">Loading...</div>
    <div v-else-if="order" class="space-y-6">

      <!-- Status + actions -->
      <div class="bg-white rounded-lg border p-5">
        <div class="flex flex-wrap items-center gap-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Current status</p>
            <span class="px-2 py-1 rounded text-sm bg-gray-100 font-medium">{{ order.status }}</span>
          </div>
          <div class="flex items-center gap-2">
            <select v-model="newStatus" class="border rounded px-3 py-2 text-sm min-h-[44px]">
              <option value="">Change status...</option>
              <option v-for="s in appConfig?.order_statuses" :key="s" :value="s">{{ s }}</option>
            </select>
            <button @click="updateStatus" :disabled="!newStatus || updating" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 disabled:opacity-50 min-h-[44px]">
              Update
            </button>
          </div>
          <button
            v-if="order.payment_method === 'cod' && order.status === 'pending_payment'"
            @click="markPaid"
            :disabled="updating"
            class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 disabled:opacity-50 min-h-[44px]"
          >
            Mark as Paid (COD)
          </button>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white rounded-lg border p-5">
        <h2 class="font-medium mb-3">Items</h2>
        <div class="space-y-2">
          <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
            <span>{{ item.product_name }} — {{ item.variant_name }} x{{ item.quantity }}</span>
            <span>₱{{ (item.unit_price_cents * item.quantity / 100).toFixed(2) }}</span>
          </div>
        </div>
        <div class="border-t mt-3 pt-3 flex justify-between font-medium text-sm">
          <span>Total</span>
          <span>₱{{ (order.total_cents / 100).toFixed(2) }}</span>
        </div>
      </div>

      <!-- Status history -->
      <div class="bg-white rounded-lg border p-5">
        <h2 class="font-medium mb-3">Status History</h2>
        <div class="space-y-2 text-sm">
          <div v-for="entry in order.status_history" :key="entry.id" class="flex items-center gap-2 text-gray-600">
            <span>{{ entry.from_status }}</span>
            <span>→</span>
            <span class="font-medium text-gray-800">{{ entry.to_status }}</span>
            <span v-if="entry.note" class="text-gray-400">· {{ entry.note }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })

const route = useRoute()
const config = useRuntimeConfig()
const auth = useAuthStore()
const { data: appConfig } = useConfig()

const newStatus = ref('')
const updating = ref(false)

const { data: order, pending, refresh } = useFetch(`/api/v1/orders/${route.params.id}`, {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
})

useSeoMeta({ title: computed(() => `${order.value?.order_number ?? 'Order'} — Admin`) })

const updateStatus = async () => {
  if (!newStatus.value) return
  updating.value = true
  try {
    await $fetch(`/api/v1/orders/${route.params.id}/status`, {
      method: 'PATCH',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: { status: newStatus.value },
    })
    newStatus.value = ''
    refresh()
  } finally {
    updating.value = false
  }
}

const markPaid = async () => {
  updating.value = true
  try {
    await $fetch(`/api/v1/orders/${route.params.id}/mark-paid`, {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
    })
    refresh()
  } finally {
    updating.value = false
  }
}
</script>