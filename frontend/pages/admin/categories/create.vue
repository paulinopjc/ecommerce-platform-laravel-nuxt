<template>
  <div class="max-w-lg">
    <h1 class="text-xl font-semibold mb-6">New Category</h1>
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Name</label>
        <input v-model="form.name" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Slug</label>
        <input v-model="form.slug" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Description</label>
        <textarea v-model="form.description" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1">Position</label>
        <input v-model.number="form.position" type="number" min="0" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div class="flex items-center gap-3">
        <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4" />
        <label for="is_active" class="text-sm">Active</label>
      </div>
      <div v-if="error" class="text-red-600 text-sm bg-red-50 rounded px-3 py-2">{{ error }}</div>
      <button type="submit" :disabled="loading" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 min-h-[44px]">
        {{ loading ? 'Saving...' : 'Create Category' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'admin', layout: 'admin' })
useSeoMeta({ title: 'New Category — Admin' })

const config = useRuntimeConfig()
const auth = useAuthStore()
const router = useRouter()

const form = reactive({ name: '', slug: '', description: '', position: 0, is_active: true })
const error = ref('')
const loading = ref(false)

watch(() => form.name, (name) => {
  form.slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
})

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  try {
    await $fetch('/api/v1/categories', {
      method: 'POST',
      baseURL: config.public.apiUrl.replace('/api/v1', ''),
      headers: { Authorization: `Bearer ${auth.token}` },
      body: form,
    })
    router.push(useAdminPath('/categories'))
  } catch (e: any) {
    error.value = e.data?.message ?? 'Failed to create category.'
  } finally {
    loading.value = false
  }
}
</script>