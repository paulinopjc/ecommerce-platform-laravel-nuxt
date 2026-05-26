<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
    <div class="text-green-500 text-5xl mb-4">&#10003;</div>
    <h1 class="text-2xl font-semibold mb-2">Order Placed!</h1>
    <p class="text-gray-600 mb-6">Order number: <strong>{{ (order as any)?.order_number }}</strong></p>

    <div v-if="(order as any)?.payment_method === 'cod'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-left mb-6">
      <p class="font-medium text-yellow-800 mb-1">Cash on Delivery</p>
      <p class="text-yellow-700">Please prepare the exact amount upon delivery. Your order will be processed shortly.</p>
    </div>

    <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-left mb-6">
      <p class="font-medium text-blue-800 mb-1">Online Payment</p>
      <p class="text-blue-700">You were redirected to the payment page. If not, <a :href="(order as any)?.payments?.[0]?.xendit_invoice_url" class="underline">click here to pay</a>.</p>
    </div>

    <NuxtLink to="/products" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700">
      Continue Shopping
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: 'Order Confirmed' })

const route = useRoute()
const config = useRuntimeConfig()
const auth = useAuthStore()

const { data: order } = useFetch(`/api/v1/orders/${route.params.id}`, {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
})
</script>
