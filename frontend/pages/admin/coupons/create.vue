<template>
  <div class="max-w-lg">
    <h1 class="text-xl font-semibold mb-6">New Coupon</h1>
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Code</label>
        <input v-model="form.code" required placeholder="SUMMER20" class="w-full border rounded px-3 py-2 text-sm min-h-[44px] uppercase" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Type</label>
          <select v-model="form.type" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]">
            <option value="">Select...</option>
            <option v-for="t in appConfig?.coupon_types" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1">
            Discount {{ form.type === 'percentage' ? '(%)' : form.type === 'fixed' ? '(₱)' : '' }}
          </label>
          <input v-model.number="discountInput" type="number" min="1" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Max uses (optional)</label>
          <input v-model.number="form.max_uses" type="number" min="1" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
        </div>
        <div>
          <label class="block text-sm mb-1">Expires at (optional)</label>
          <input v-model="form.expires_at" type="date" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
        </div>
      </div>
      <div>
        <label class="block text-sm mb-1">Min order amount (₱, optional)</label>
        <input v-model.number="minOrderInput" type="number" min="0" step="0.01" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div class="flex items-center gap-3">
        <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4" checked />
        <label for="is_active" class="text-sm">Active</label>
      </div>
      <div v-if="error" class="text-red-600 text-sm bg-red-50 rounded px-3 py-2">{{ error }}</div>
      <button type="submit" :disabled="loading" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 min-h-[44px]">
        {{ loading ? 'Saving...' : 'Create Coupon' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'New Coupon — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()
const { data: appConfig } = useConfig()

const form = reactive({
  code: '',
  type: '',
  discount_value: 0,
  max_uses: null as number | null,
  expires_at: '',
  min_order_cents: null as number | null,
  is_active: true,
})
const error = ref('')
const loading = ref(false)

// percentage: user enters 20 → stored as 20; fixed: user enters 100 → stored as 10000 (cents)
const discountInput = computed({
  get: () => form.type === 'fixed' ? form.discount_value / 100 : form.discount_value,
  set: (v: number) => { form.discount_value = form.type === 'fixed' ? Math.round(v * 100) : v },
})
const minOrderInput = computed({
  get: () => form.min_order_cents ? form.min_order_cents / 100 : null,
  set: (v: number | null) => { form.min_order_cents = v ? Math.round(v * 100) : null },
})

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  try {
    await $fetch('/api/v1/coupons', {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: { ...form, code: form.code.toUpperCase() },
    })
    router.push(useAdminPath('/coupons'))
  } catch (e: any) {
    error.value = e.data?.message ?? 'Failed to create coupon.'
  } finally {
    loading.value = false
  }
}
</script>