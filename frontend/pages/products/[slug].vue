<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div v-if="pending" class="text-gray-500">Loading...</div>
    <div v-else-if="product" class="grid md:grid-cols-2 gap-8">
      <!-- Image -->
      <div>
        <img
          v-if="product.images?.[0]"
          :src="product.images[0].url"
          :alt="product.name"
          class="w-full rounded-lg"
        />
        <div v-else class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">No image</div>
      </div>

      <!-- Info -->
      <div>
        <h1 class="text-2xl font-semibold mb-2">{{ product.name }}</h1>
        <p class="text-2xl text-blue-600 font-bold mb-4">
          &#8369;{{ ((selectedVariant ? selectedVariant.price_cents : product.base_price_cents) / 100).toFixed(2) }}
        </p>
        <p v-if="product.description" class="text-gray-600 mb-4 text-sm">{{ product.description }}</p>

        <!-- Variant selector -->
        <div v-if="product.variants?.length" class="mb-4">
          <p class="text-sm font-medium mb-2">Select variant:</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="variant in product.variants"
              :key="variant.id"
              @click="selectedVariantId = variant.id"
              :disabled="variant.stock_quantity - variant.reserved_quantity <= 0"
              :class="[
                'px-3 py-1.5 border rounded text-sm min-h-[44px]',
                selectedVariantId === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'hover:border-gray-400',
                variant.stock_quantity - variant.reserved_quantity <= 0 ? 'opacity-40 cursor-not-allowed' : '',
              ]"
            >
              {{ variant.name }}
            </button>
          </div>
        </div>

        <!-- Quantity -->
        <div class="flex items-center gap-3 mb-4">
          <label class="text-sm font-medium">Qty:</label>
          <input v-model.number="qty" type="number" min="1" max="99" class="border rounded px-2 py-1 w-16 text-sm" />
        </div>

        <!-- Stock status -->
        <p v-if="selectedVariant" class="text-sm mb-4" :class="availableStock > 0 ? 'text-green-600' : 'text-red-500'">
          {{ availableStock > 0 ? `${availableStock} in stock` : 'Out of stock' }}
        </p>

        <button
          @click="handleAddToCart"
          :disabled="!selectedVariant || availableStock <= 0 || adding"
          class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed min-h-[44px]"
        >
          {{ adding ? 'Adding...' : 'Add to Cart' }}
        </button>

        <p v-if="addedMessage" class="text-green-600 text-sm mt-2 text-center">{{ addedMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const auth = useAuthStore()
const { addItem } = useCart()

const { data: product, pending } = useFetch(`/api/v1/products/${route.params.slug}`, {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
})

useSeoMeta({ title: computed(() => (product.value as any)?.name ?? 'Product') })

const selectedVariantId = ref<number | null>(null)
const qty = ref(1)
const adding = ref(false)
const addedMessage = ref('')

const selectedVariant = computed(() =>
  (product.value as any)?.variants?.find((v: any) => v.id === selectedVariantId.value)
)
const availableStock = computed(() =>
  selectedVariant.value
    ? selectedVariant.value.stock_quantity - selectedVariant.value.reserved_quantity
    : 0
)

watch(product, (p: any) => {
  if (p?.variants?.length) selectedVariantId.value = p.variants[0].id
}, { immediate: true })

const handleAddToCart = async () => {
  if (!auth.token) return navigateTo('/login?redirect=' + route.fullPath)
  if (!selectedVariantId.value) return
  adding.value = true
  try {
    await addItem(selectedVariantId.value, qty.value)
    addedMessage.value = 'Added to cart!'
    setTimeout(() => { addedMessage.value = '' }, 2000)
  } finally {
    adding.value = false
  }
}
</script>
