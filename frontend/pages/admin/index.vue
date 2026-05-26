<template>
  <div>
    <h1 class="text-xl font-semibold mb-6">Dashboard</h1>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border p-5">
        <p class="text-sm text-gray-500">Total Orders</p>
        <p class="text-2xl font-bold mt-1">—</p>
      </div>
      <div class="bg-white rounded-lg border p-5">
        <p class="text-sm text-gray-500">Products</p>
        <p class="text-2xl font-bold mt-1">—</p>
      </div>
      <div class="bg-white rounded-lg border p-5">
        <p class="text-sm text-gray-500">Pending Payment</p>
        <p class="text-2xl font-bold mt-1">—</p>
      </div>
      <div class="bg-white rounded-lg border p-5">
        <p class="text-sm text-gray-500">Revenue</p>
        <p class="text-2xl font-bold mt-1">—</p>
      </div>
    </div>
    <p class="text-sm text-gray-400 mt-6">Connect these stats to the API in a future iteration.</p>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'Dashboard — Admin' })
</script>
