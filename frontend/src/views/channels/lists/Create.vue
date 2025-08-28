<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-16 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Create Channel List</h1>
          
          <!-- Form Actions -->
          <div class="flex items-center space-x-4">
            <router-link
              :to="`/channels/${props.channelId}/edit`"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </router-link>
            <button
              @click="createList"
              :disabled="saving || !isValid"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? 'Creating...' : 'Create List' }}
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Form -->
        <div v-else class="space-y-8">
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium mb-4">List Information</h2>
            
            <div class="space-y-6">
              <!-- List Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                  List Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="e.g., Best Coffee Shops"
                  @input="generateSlug"
                >
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
              </div>

              <!-- Slug -->
              <div>
                <label for="slug" class="block text-sm font-medium text-gray-700">
                  List URL
                </label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                    /{{ channel.slug }}/
                  </span>
                  <input
                    id="slug"
                    v-model="form.slug"
                    type="text"
                    class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    @blur="checkSlugAvailability"
                  >
                </div>
                <p v-if="slugError" class="mt-1 text-sm text-red-600">{{ slugError }}</p>
                <p v-else-if="slugChecking" class="mt-1 text-sm text-gray-500">Checking availability...</p>
                <p v-else-if="slugAvailable === true" class="mt-1 text-sm text-green-600">This URL is available</p>
                <p class="mt-1 text-sm text-gray-500">URL: /{{ channel.slug }}/{{ form.slug || 'your-list-slug' }}</p>
              </div>

              <!-- Description -->
              <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                  Description
                </label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="A brief description of your list"
                />
                <p class="mt-1 text-sm text-gray-500">{{ form.description.length }}/500 characters</p>
              </div>

              <!-- Category -->
              <div>
                <label for="category" class="block text-sm font-medium text-gray-700">
                  Category <span class="text-red-500">*</span>
                </label>
                <select
                  id="category"
                  v-model="form.category_id"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                >
                  <option value="">Select a category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <!-- Visibility -->
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Visibility
                </label>
                <div class="mt-2 space-y-2">
                  <label class="flex items-center">
                    <input
                      v-model="form.visibility"
                      type="radio"
                      value="public"
                      class="mr-2"
                    >
                    <span class="text-sm text-gray-700">
                      <strong>Public</strong> - Anyone can view this list
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="form.visibility"
                      type="radio"
                      value="private"
                      class="mr-2"
                    >
                    <span class="text-sm text-gray-700">
                      <strong>Private</strong> - Only you and people you share with can view this list
                    </span>
                  </label>
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Status
                </label>
                <div class="mt-2 space-y-2">
                  <label class="flex items-center">
                    <input
                      v-model="form.is_draft"
                      type="radio"
                      :value="true"
                      class="mr-2"
                    >
                    <span class="text-sm text-gray-700">
                      <strong>Draft</strong> - Save as draft (not published)
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="form.is_draft"
                      type="radio"
                      :value="false"
                      class="mr-2"
                    >
                    <span class="text-sm text-gray-700">
                      <strong>Published</strong> - Publish immediately
                    </span>
                  </label>
                </div>
                <p class="mt-1 text-sm text-gray-500">Draft lists are only visible to you until published</p>
              </div>
            </div>
          </div>

          <!-- Error message -->
          <div v-if="error" class="rounded-md bg-red-50 p-4">
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

// Simple debounce implementation
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

const props = defineProps({
  channelId: {
    type: String,
    required: true
  }
})

const router = useRouter()
const route = useRoute()

// Data
const channel = ref({})
const categories = ref([])
const form = reactive({
  name: '',
  slug: '',
  description: '',
  category_id: '',
  visibility: 'private',
  is_draft: true,
  channel_id: null
})
const errors = reactive({
  name: '',
  slug: ''
})
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const slugChecking = ref(false)
const slugAvailable = ref(null)
const slugError = ref(null)

// Computed
const isValid = computed(() => {
  return form.name.trim() && 
         form.slug.trim() && 
         form.category_id && 
         !errors.name && 
         !errors.slug && 
         slugAvailable !== false
})

// Methods
const fetchChannel = async () => {
  try {
    console.log('Fetching channel with ID:', props.channelId)
    const response = await axios.get(`/api/channels/${props.channelId}`)
    console.log('Channel data:', response.data)
    channel.value = response.data
    form.channel_id = response.data.id
    console.log('Set form.channel_id to:', form.channel_id)
  } catch (err) {
    console.error('Error fetching channel:', err)
    error.value = 'Failed to load channel information'
    router.push('/mychannels')
  }
}

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/list-categories')
    categories.value = response.data
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

const generateSlug = () => {
  if (!form.name) {
    form.slug = ''
    return
  }
  
  // Generate slug from name
  form.slug = form.name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .substring(0, 50)
}

const checkSlugAvailability = debounce(async () => {
  if (!form.slug) {
    slugAvailable.value = null
    slugError.value = null
    return
  }
  
  slugChecking.value = true
  slugError.value = null
  
  try {
    // Check if slug exists for this channel
    const response = await axios.get(`/api/channels/${channel.value.slug}/lists`)
    const existingList = response.data.data?.find(list => list.slug === form.slug)
    
    slugAvailable.value = !existingList
    if (existingList) {
      slugError.value = 'This URL is already taken'
    }
  } catch (err) {
    console.error('Error checking slug:', err)
    slugError.value = 'Failed to check availability'
  } finally {
    slugChecking.value = false
  }
}, 500)

const createList = async () => {
  if (!isValid.value) return
  
  saving.value = true
  error.value = null
  
  try {
    const payload = {
      name: form.name,
      slug: form.slug,
      description: form.description || '',
      category_id: form.category_id,
      visibility: form.visibility,
      is_draft: form.is_draft,
      channel_id: form.channel_id
    }
    
    console.log('Creating list with payload:', payload)
    const response = await axios.post('/api/lists', payload)
    console.log('List creation response:', response.data)
    
    // Redirect to the new list
    if (response.data.list && response.data.list.slug) {
      console.log('Redirecting to:', `/${channel.value.slug}/${response.data.list.slug}`)
      router.push(`/${channel.value.slug}/${response.data.list.slug}`)
    } else {
      // Fallback to channel edit page if no slug
      console.log('No slug in response, redirecting to channel edit')
      router.push(`/channels/${channel.value.id}/edit?tab=lists`)
    }
  } catch (err) {
    console.error('Error creating list:', err)
    
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors
      if (validationErrors.name) {
        errors.name = validationErrors.name[0]
      }
      if (validationErrors.slug) {
        errors.slug = validationErrors.slug[0]
      }
      error.value = 'Please fix the errors below'
    } else {
      error.value = err.response?.data?.message || 'Failed to create list. Please try again.'
    }
  } finally {
    saving.value = false
  }
}

// Watch slug changes
watch(() => form.slug, () => {
  if (form.slug) {
    checkSlugAvailability()
  }
})

// Watch name for validation
watch(() => form.name, (newVal) => {
  if (!newVal.trim()) {
    errors.name = 'Name is required'
  } else if (newVal.length > 100) {
    errors.name = 'Name must be less than 100 characters'
  } else {
    errors.name = ''
  }
})

// Lifecycle
onMounted(async () => {
  loading.value = true
  
  await Promise.all([
    fetchChannel(),
    fetchCategories()
  ])
  
  loading.value = false
})
</script>