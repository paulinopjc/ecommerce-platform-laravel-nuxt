<template>
  <div class="min-h-screen flex flex-col">
    <header class="bg-white border-b sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <NuxtLink to="/" class="text-xl font-bold text-blue-600">ShopPH</NuxtLink>

          <!-- Desktop nav -->
          <nav class="hidden md:flex items-center gap-6 text-sm">
            <NuxtLink to="/products" class="text-gray-600 hover:text-gray-900">Products</NuxtLink>
            <NuxtLink v-if="auth.isAdmin" to="/admin" class="text-gray-600 hover:text-gray-900">Admin</NuxtLink>
            <NuxtLink to="/cart" class="relative text-gray-600 hover:text-gray-900">
              Cart
              <span v-if="itemCount > 0" class="absolute -top-2 -right-3 bg-blue-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                {{ itemCount }}
              </span>
            </NuxtLink>
            <template v-if="auth.user">
              <span class="text-gray-600">{{ auth.user.name }}</span>
              <button @click="auth.logout()" class="text-gray-600 hover:text-gray-900">Logout</button>
            </template>
            <NuxtLink v-else to="/login" class="text-gray-600 hover:text-gray-900">Login</NuxtLink>
          </nav>

          <!-- Mobile hamburger -->
          <button @click="menuOpen = !menuOpen" class="md:hidden p-2 min-h-[44px] min-w-[44px]">
            <span class="sr-only">Menu</span>
            <div class="space-y-1">
              <span class="block w-6 h-0.5 bg-gray-600"></span>
              <span class="block w-6 h-0.5 bg-gray-600"></span>
              <span class="block w-6 h-0.5 bg-gray-600"></span>
            </div>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-if="menuOpen" class="md:hidden border-t px-4 py-3 space-y-2 text-sm">
        <NuxtLink to="/products" class="block py-2 text-gray-600" @click="menuOpen = false">Products</NuxtLink>
        <NuxtLink v-if="auth.isAdmin" to="/admin" class="block py-2 text-gray-600" @click="menuOpen = false">Admin</NuxtLink>
        <NuxtLink to="/cart" class="block py-2 text-gray-600" @click="menuOpen = false">Cart ({{ itemCount }})</NuxtLink>
        <template v-if="auth.user">
          <span class="block py-2 text-gray-600">{{ auth.user.name }}</span>
          <button @click="auth.logout(); menuOpen = false" class="block py-2 text-gray-600">Logout</button>
        </template>
        <NuxtLink v-else to="/login" class="block py-2 text-gray-600" @click="menuOpen = false">Login</NuxtLink>
      </div>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="border-t py-6 text-center text-sm text-gray-500">
      &copy; {{ new Date().getFullYear() }} ShopPH
    </footer>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const { itemCount, fetchCart } = useCart()
const menuOpen = ref(false)

onMounted(() => {
  if (auth.token) fetchCart()
})
</script>
