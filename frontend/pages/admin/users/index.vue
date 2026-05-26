<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">Users</h1>
      <NuxtLink :to="useAdminPath('/users/create')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 min-h-[44px] flex items-center">
        + New User
      </NuxtLink>
    </div>

    <div class="mb-4">
      <select v-model="roleFilter" class="border rounded px-3 py-2 text-sm min-h-[44px]">
        <option value="">All roles</option>
        <option v-for="r in appConfig?.roles" :key="r" :value="r">{{ r }}</option>
      </select>
    </div>

    <div class="bg-white rounded-lg border overflow-hidden">
      <div v-if="pending" class="p-6 text-gray-500 text-sm">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Name</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Email</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Role</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="user in users?.data" :key="user.id">
            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
            <td class="px-4 py-3 hidden sm:table-cell text-gray-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">{{ user.role }}</span>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 rounded text-xs">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="toggleActive(user)" class="text-gray-500 hover:text-gray-700 text-xs mr-3">
                {{ user.is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'Users — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const { data: appConfig } = useConfig()
const roleFilter = ref('')

const params = computed(() => ({ role: roleFilter.value || undefined }))

const { data: users, pending, refresh } = useFetch('/api/v1/users', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
  headers: { Authorization: `Bearer ${auth.token}` },
  query: params,
  watch: [params],
})

const toggleActive = async (user: any) => {
  await $fetch(`/api/v1/users/${user.id}`, {
    method: 'PATCH',
    baseURL: config.public.apiUrl.replace('/api/v1', ''),
    headers: { Authorization: `Bearer ${auth.token}` },
    body: { is_active: !user.is_active },
  })
  refresh()
}
</script>