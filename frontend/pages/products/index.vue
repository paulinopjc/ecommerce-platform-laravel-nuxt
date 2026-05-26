<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold mb-6">Products</h1>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Search products..."
        class="border rounded-lg px-3 py-2 text-sm flex-1 min-h-[44px]"
      />
      <select v-model="selectedCategory" class="border rounded-lg px-3 py-2 text-sm min-h-[44px]">
        <option value="">All categories</option>
        <option v-for="cat in categories?.data" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
      <select v-model="sortBy" class="border rounded-lg px-3 py-2 text-sm min-h-[44px]">
        <option value="created_at">Newest</option>
        <option value="base_price_cents">Price</option>
        <option value="name">Name</option>
      </select>
    </div>

    <div v-if="pending" class="text-gray-500">Loading...</div>
    <div v-else-if="!products?.data?.length" class="text-gray-500">No products found.</div>
    <div v-else>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <NuxtLink
          v-for="product in products.data"
          :key="product.id"
          :to="`/products/${product.slug}`"
          class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
        >
          <img
            v-if="product.images?.[0]"
            :src="product.images[0].url"
            :alt="product.name"
            class="w-full aspect-square object-cover"
          />
          <div v-else class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-sm">No image</div>
          <div class="p-3">
            <p class="font-medium text-sm truncate">{{ product.name }}</p>
            <p class="text-blue-600 text-sm mt-1">&#8369;{{ (product.base_price_cents / 100).toFixed(2) }}</p>
          </div>
        </NuxtLink>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center gap-2 mt-8">
        <button
          v-for="page in products.meta?.last_page"
          :key="page"
          @click="currentPage = page"
          :class="['px-3 py-1 rounded border text-sm min-h-[44px]', currentPage === page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50']"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
useSeoMeta({ title: 'Products' })

const config = useRuntimeConfig()
const search = ref('')
const selectedCategory = ref('')
const sortBy = ref('created_at')
const currentPage = ref(1)

const params = computed(() => ({
  search: search.value || undefined,
  category_id: selectedCategory.value || undefined,
  sortBy: sortBy.value,
  sortOrder: 'desc',
  page: currentPage.value,
}))

const { data: products, pending } = useProducts(params)

const { data: categories } = useFetch('/api/v1/categories', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
})

// Reset to page 1 when filters change
watch([search, selectedCategory, sortBy], () => { currentPage.value = 1 })
</script>
