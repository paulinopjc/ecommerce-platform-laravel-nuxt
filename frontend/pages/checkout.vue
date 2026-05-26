<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold mb-6">Checkout</h1>

    <form @submit.prevent="placeOrder" class="space-y-6">
      <!-- Shipping address -->
      <div class="border rounded-lg p-5">
        <h2 class="font-medium mb-4">Shipping Address</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-sm mb-1">Full name</label>
            <input v-model="form.shipping_name" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm mb-1">Address line 1</label>
            <input v-model="form.shipping_line1" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
          <div>
            <label class="block text-sm mb-1">City</label>
            <input v-model="form.shipping_city" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
          <div>
            <label class="block text-sm mb-1">Province</label>
            <input v-model="form.shipping_province" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
          <div>
            <label class="block text-sm mb-1">Postal code</label>
            <input v-model="form.shipping_postal_code" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
          <div>
            <label class="block text-sm mb-1">Phone</label>
            <input v-model="form.phone" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
          </div>
        </div>
      </div>

      <!-- Payment method -->
      <div class="border rounded-lg p-5">
        <h2 class="font-medium mb-4">Payment Method</h2>
        <div class="space-y-3">
          <label class="flex items-center gap-3 cursor-pointer min-h-[44px]">
            <input type="radio" v-model="form.payment_method" value="cod" class="w-4 h-4" />
            <span class="text-sm">Cash on Delivery</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer min-h-[44px]">
            <input type="radio" v-model="form.payment_method" value="xendit" class="w-4 h-4" />
            <span class="text-sm">Pay Online (Xendit)</span>
          </label>
        </div>
      </div>

      <div v-if="error" class="text-red-600 text-sm bg-red-50 rounded px-3 py-2">{{ error }}</div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 min-h-[44px]"
      >
        {{ loading ? 'Placing order...' : 'Place Order' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: 'Checkout' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()
const error = ref('')
const loading = ref(false)

const form = reactive({
  shipping_name: '',
  shipping_line1: '',
  shipping_city: '',
  shipping_province: '',
  shipping_postal_code: '',
  phone: '',
  payment_method: 'cod',
})

const placeOrder = async () => {
  loading.value = true
  error.value = ''
  try {
    const order: any = await $fetch('/api/v1/checkout', {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: form,
    })

    if (order.xendit_invoice_url) {
      window.location.href = order.xendit_invoice_url
    } else {
      router.push(`/order-confirmation/${order.id}`)
    }
  } catch (e: any) {
    error.value = e.data?.message ?? 'Something went wrong.'
  } finally {
    loading.value = false
  }
}
</script>
