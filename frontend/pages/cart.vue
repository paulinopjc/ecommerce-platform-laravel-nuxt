<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold mb-6">Your Cart</h1>

    <div v-if="!cart || !cart.items?.length" class="text-gray-500">
      Your cart is empty. <NuxtLink to="/products" class="text-blue-600 hover:underline">Continue shopping</NuxtLink>
    </div>

    <div v-else>
      <div class="space-y-4 mb-6">
        <div v-for="item in cart.items" :key="item.id" class="flex items-center gap-4 border rounded-lg p-4">
          <div class="flex-1">
            <p class="font-medium text-sm">{{ item.product_name }}</p>
            <p class="text-gray-500 text-xs">{{ item.variant_name }}</p>
          </div>
          <p class="text-sm font-medium">&#8369;{{ (item.unit_price_cents * item.quantity / 100).toFixed(2) }}</p>
          <span class="text-xs text-gray-400">x{{ item.quantity }}</span>
          <button @click="removeItem(item.id)" class="text-red-500 hover:text-red-700 text-sm min-h-[44px] px-2">Remove</button>
        </div>
      </div>

      <!-- Totals -->
      <div class="border-t pt-4 space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Subtotal</span>
          <span>&#8369;{{ (cart.subtotal_cents / 100).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between font-semibold text-base">
          <span>Total</span>
          <span>&#8369;{{ (cart.subtotal_cents / 100).toFixed(2) }}</span>
        </div>
      </div>

      <NuxtLink
        to="/checkout"
        class="block w-full text-center bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 mt-6 min-h-[44px] flex items-center justify-center"
      >
        Proceed to Checkout
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: 'Cart' })

const { cart, fetchCart, removeItem } = useCart()

onMounted(() => fetchCart())
</script>
