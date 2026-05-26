<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-56 bg-gray-900 text-white flex-shrink-0 hidden md:flex flex-col">
      <div class="p-4 border-b border-gray-700">
        <NuxtLink to="/" class="text-lg font-bold text-white">ShopPH</NuxtLink>
        <p class="text-xs text-gray-400 mt-0.5">Admin Panel</p>
      </div>
      <nav class="flex-1 p-4 space-y-1 text-sm">
        <NuxtLink :to="useAdminPath()" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</NuxtLink>
        <NuxtLink :to="useAdminPath('/products')" class="block px-3 py-2 rounded hover:bg-gray-700">Products</NuxtLink>
        <NuxtLink :to="useAdminPath('/orders')" class="block px-3 py-2 rounded hover:bg-gray-700">Orders</NuxtLink>
        <NuxtLink :to="useAdminPath('/users')" class="block px-3 py-2 rounded hover:bg-gray-700">Users</NuxtLink>
        <NuxtLink :to="useAdminPath('/categories')" class="block px-3 py-2 rounded hover:bg-gray-700">Categories</NuxtLink>
        <NuxtLink :to="useAdminPath('/coupons')" class="block px-3 py-2 rounded hover:bg-gray-700">Coupons</NuxtLink>
      </nav>
      <div class="p-4 border-t border-gray-700">
        <p class="text-xs text-white font-medium truncate">{{ auth.user?.name }}</p>
        <p class="text-xs text-gray-400 truncate mb-3">{{ auth.user?.email }}</p>
        <div class="flex flex-col gap-1">
          <NuxtLink
            to="/admin/profile"
            class="block text-xs text-gray-400 hover:text-white px-2 py-1.5 rounded hover:bg-gray-700"
          >
            Edit details
          </NuxtLink>
          <button
            class="text-left text-xs text-gray-400 hover:text-white px-2 py-1.5 rounded hover:bg-gray-700"
            @click="handleLogout"
          >
            Sign out
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile top bar -->
    <div class="md:hidden fixed top-0 left-0 right-0 bg-gray-900 text-white flex items-center justify-between px-4 h-14 z-50">
      <span class="font-bold">Admin Panel</span>
      <button @click="menuOpen = !menuOpen" class="p-2 min-h-[44px]">☰</button>
    </div>
    <div v-if="menuOpen" class="md:hidden fixed top-14 left-0 right-0 bg-gray-800 text-white text-sm z-40 p-4 space-y-2">
      <NuxtLink :to="useAdminPath()" class="block py-2" @click="menuOpen = false">Dashboard</NuxtLink>
      <NuxtLink :to="useAdminPath('/products')" class="block py-2" @click="menuOpen = false">Products</NuxtLink>
      <NuxtLink :to="useAdminPath('/orders')" class="block py-2" @click="menuOpen = false">Orders</NuxtLink>
      <NuxtLink :to="useAdminPath('/users')" class="block py-2" @click="menuOpen = false">Users</NuxtLink>
      <NuxtLink :to="useAdminPath('/categories')" class="block py-2" @click="menuOpen = false">Categories</NuxtLink>
      <NuxtLink :to="useAdminPath('/coupons')" class="block py-2" @click="menuOpen = false">Coupons</NuxtLink>
      <div class="border-t border-gray-700 pt-2 mt-2">
        <p class="text-xs text-gray-400 mb-2">{{ auth.user?.name }}</p>
        <NuxtLink to="/admin/profile" class="block py-2 text-gray-400" @click="menuOpen = false">Edit details</NuxtLink>
        <button class="block py-2 text-gray-400 text-left" @click="handleLogout">Sign out</button>
      </div>
    </div>

    <!-- Content -->
    <main class="flex-1 p-6 md:p-8 bg-gray-50 mt-14 md:mt-0 overflow-auto">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const menuOpen = ref(false)

function handleLogout() {
  auth.logout()
  navigateTo('/admin/login')
}
</script>