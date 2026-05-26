<template>
  <div>
    <!-- Hero -->
    <section class="bg-blue-600 text-white py-16 px-4 text-center">
      <h1 class="text-3xl font-bold mb-2">Welcome to ShopPH</h1>
      <p class="text-blue-100 mb-6">Quality products delivered to your door</p>
      <NuxtLink to="/products" class="inline-block bg-white text-blue-600 font-medium px-6 py-3 rounded-lg hover:bg-blue-50">
        Shop Now
      </NuxtLink>
    </section>

    <!-- Featured products -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h2 class="text-xl font-semibold mb-6">Featured Products</h2>
      <div v-if="pending" class="text-gray-500">Loading...</div>
      <div v-else-if="!products?.data?.length" class="text-gray-500">No featured products yet.</div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
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
    </section>
  </div>
</template>

<script setup lang="ts">
useSeoMeta({ title: 'Home' })

const { data: products, pending } = useProducts(ref({ is_featured: true, per_page: 8 }))
</script>
