<template>
  <form @submit.prevent="emit('submit', form)" class="space-y-4">
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
      <textarea v-model="form.description" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm mb-1">Base price (₱)</label>
        <input v-model.number="priceInPesos" type="number" step="0.01" min="0" required class="w-full border rounded px-3 py-2 text-sm min-h-[44px]" />
      </div>
      <div>
        <label class="block text-sm mb-1">Category</label>
        <select v-model="form.category_id" class="w-full border rounded px-3 py-2 text-sm min-h-[44px]">
          <option value="">None</option>
          <option v-for="cat in categories?.data" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4" />
      <label for="is_active" class="text-sm">Active</label>
      <input v-model="form.is_featured" type="checkbox" id="is_featured" class="w-4 h-4 ml-4" />
      <label for="is_featured" class="text-sm">Featured</label>
    </div>
    <div v-if="error" class="text-red-600 text-sm bg-red-50 rounded px-3 py-2">{{ error }}</div>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-blue-700 min-h-[44px]">
      Save Product
    </button>
  </form>
</template>

<script setup lang="ts">
const props = defineProps<{ initial?: any }>()
const emit = defineEmits<{ (e: 'submit', form: any): void }>()

const config = useRuntimeConfig()
const error = ref('')

const form = reactive({
  name: props.initial?.name ?? '',
  slug: props.initial?.slug ?? '',
  description: props.initial?.description ?? '',
  base_price_cents: props.initial?.base_price_cents ?? 0,
  category_id: props.initial?.category_id ?? '',
  is_active: props.initial?.is_active ?? true,
  is_featured: props.initial?.is_featured ?? false,
})

const priceInPesos = computed({
  get: () => form.base_price_cents / 100,
  set: (v: number) => { form.base_price_cents = Math.round(v * 100) },
})

const { data: categories } = useFetch('/api/v1/categories', {
  baseURL: config.public.apiUrl.replace('/api/v1', ''),
})

// Auto-generate slug from name on create
watch(() => form.name, (name) => {
  if (!props.initial) {
    form.slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
  }
})
</script>