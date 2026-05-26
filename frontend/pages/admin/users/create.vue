<template>
  <div class="max-w-lg">
    <h1 class="text-xl font-semibold mb-6">New User</h1>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Name</label>
        <input v-model="form.name" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Email</label>
        <input v-model="form.email" type="email" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input v-model="form.password" type="password" required minlength="8" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Role</label>
        <select v-model="form.role" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]">
          <option value="">Select role...</option>
          <option v-for="r in appConfig?.roles" :key="r" :value="r">{{ r }}</option>
        </select>
      </div>
      <div v-if="error" class="text-red-600 text-sm bg-red-50 rounded px-3 py-2">{{ error }}</div>
      <button type="submit" :disabled="loading" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 min-h-[44px]">
        {{ loading ? 'Creating...' : 'Create User' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'New User — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()
const { data: appConfig } = useConfig()

const form = reactive({ name: '', email: '', password: '', role: '' })
const error = ref('')
const loading = ref(false)

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  try {
    await $fetch('/api/v1/users', {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: form,
    })
    router.push(useAdminPath('/users'))
  } catch (e: any) {
    error.value = e.data?.message ?? 'Failed to create user.'
  } finally {
    loading.value = false
  }
}
</script>