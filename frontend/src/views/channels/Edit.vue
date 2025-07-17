<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Form -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Channel</h1>
          
          <form @submit.prevent="updateChannel" class="space-y-6">
            <!-- Channel Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">
                Channel Name
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              >
            </div>

            <!-- Slug -->
            <div>
              <label for="slug" class="block text-sm font-medium text-gray-700">
                Channel URL
              </label>
              <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                  @
                </span>
                <input
                  id="slug"
                  v-model="form.slug"
                  type="text"
                  required
                  class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  @blur="checkSlugAvailability"
                >
              </div>
              <p v-if="slugError" class="mt-1 text-sm text-red-600">{{ slugError }}</p>
              <p v-else-if="slugChecking" class="mt-1 text-sm text-gray-500">Checking availability...</p>
              <p v-else-if="slugAvailable === true" class="mt-1 text-sm text-green-600">This URL is available</p>
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
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              />
            </div>

            <!-- Avatar Image -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Avatar Image
              </label>
              <div class="mt-2 flex items-center space-x-4">
                <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                  <img 
                    v-if="avatarPreview || channel.avatar_url" 
                    :src="avatarPreview || channel.avatar_url" 
                    alt="Avatar"
                    class="h-full w-full object-cover"
                  >
                  <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="space-x-2">
                  <input
                    type="file"
                    ref="avatarInput"
                    @change="handleAvatarChange"
                    accept="image/*"
                    class="hidden"
                  >
                  <button
                    type="button"
                    @click="$refs.avatarInput.click()"
                    class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Change Avatar
                  </button>
                  <button
                    v-if="channel.avatar_url"
                    type="button"
                    @click="removeAvatar"
                    class="px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>

            <!-- Banner Image -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Banner Image
              </label>
              <div class="mt-2">
                <div class="h-32 w-full rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden">
                  <img 
                    v-if="bannerPreview || channel.banner_url" 
                    :src="bannerPreview || channel.banner_url" 
                    alt="Banner"
                    class="h-full w-full object-cover"
                  >
                  <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="mt-2 space-x-2">
                  <input
                    type="file"
                    ref="bannerInput"
                    @change="handleBannerChange"
                    accept="image/*"
                    class="hidden"
                  >
                  <button
                    type="button"
                    @click="$refs.bannerInput.click()"
                    class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Change Banner
                  </button>
                  <button
                    v-if="channel.banner_url"
                    type="button"
                    @click="removeBanner"
                    class="px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>

            <!-- Visibility -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Visibility
              </label>
              <div class="mt-2 space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.is_public"
                    type="radio"
                    :value="true"
                    class="mr-2"
                  >
                  <span class="text-sm text-gray-700">
                    <strong>Public</strong> - Anyone can view this channel and its lists
                  </span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.is_public"
                    type="radio"
                    :value="false"
                    class="mr-2"
                  >
                  <span class="text-sm text-gray-700">
                    <strong>Private</strong> - Only you and followers can view this channel
                  </span>
                </label>
              </div>
            </div>

            <!-- Error message -->
            <div v-if="error" class="rounded-md bg-red-50 p-4">
              <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-between">
              <button
                type="button"
                @click="deleteChannel"
                class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
              >
                Delete Channel
              </button>
              
              <div class="flex items-center space-x-3">
                <router-link
                  :to="`/@${channel.slug}`"
                  class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  Cancel
                </router-link>
                <button
                  type="submit"
                  :disabled="saving || !slugAvailable"
                  class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash-es'

const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

const router = useRouter()
const route = useRoute()

// Data
const channel = ref({})
const form = ref({
  name: '',
  slug: '',
  description: '',
  is_public: true
})
const avatarFile = ref(null)
const bannerFile = ref(null)
const avatarPreview = ref(null)
const bannerPreview = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const slugChecking = ref(false)
const slugAvailable = ref(null)
const slugError = ref(null)

// Methods
const fetchChannel = async () => {
  loading.value = true
  
  try {
    const response = await axios.get(`/api/channels/${props.id}`)
    channel.value = response.data
    
    // Populate form
    form.value = {
      name: response.data.name,
      slug: response.data.slug,
      description: response.data.description || '',
      is_public: response.data.is_public
    }
  } catch (err) {
    console.error('Error fetching channel:', err)
    if (err.response?.status === 404) {
      router.push('/mychannels')
    }
  } finally {
    loading.value = false
  }
}

const checkSlugAvailability = debounce(async () => {
  if (!form.value.slug || form.value.slug === channel.value.slug) {
    slugAvailable.value = true
    slugError.value = null
    return
  }
  
  slugChecking.value = true
  slugError.value = null
  
  try {
    const response = await axios.post('/api/channels/check-slug', {
      slug: form.value.slug,
      exclude_id: channel.value.id
    })
    
    slugAvailable.value = response.data.available
    if (!response.data.available) {
      slugError.value = 'This URL is already taken'
    }
  } catch (err) {
    console.error('Error checking slug:', err)
    slugError.value = 'Failed to check availability'
  } finally {
    slugChecking.value = false
  }
}, 500)

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Check file size (max 5MB)
    const maxSize = 5 * 1024 * 1024 // 5MB in bytes
    if (file.size > maxSize) {
      error.value = 'Avatar image must be less than 5MB'
      event.target.value = '' // Reset input
      return
    }
    
    // Check file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
    if (!validTypes.includes(file.type)) {
      error.value = 'Avatar must be a valid image file (JPEG, PNG, GIF, or WebP)'
      event.target.value = '' // Reset input
      return
    }
    
    avatarFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const handleBannerChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Check file size (max 5MB)
    const maxSize = 5 * 1024 * 1024 // 5MB in bytes
    if (file.size > maxSize) {
      error.value = 'Banner image must be less than 5MB'
      event.target.value = '' // Reset input
      return
    }
    
    // Check file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
    if (!validTypes.includes(file.type)) {
      error.value = 'Banner must be a valid image file (JPEG, PNG, GIF, or WebP)'
      event.target.value = '' // Reset input
      return
    }
    
    bannerFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      bannerPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removeAvatar = () => {
  channel.value.avatar_url = null
  avatarPreview.value = null
  avatarFile.value = null
}

const removeBanner = () => {
  channel.value.banner_url = null
  bannerPreview.value = null
  bannerFile.value = null
}

const updateChannel = async () => {
  saving.value = true
  error.value = null
  
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('slug', form.value.slug)
    formData.append('description', form.value.description || '')
    formData.append('is_public', form.value.is_public ? '1' : '0')
    formData.append('_method', 'PUT') // Laravel method spoofing
    
    if (avatarFile.value) {
      formData.append('avatar_image', avatarFile.value)
    }
    
    if (bannerFile.value) {
      formData.append('banner_image', bannerFile.value)
    }
    
    await axios.post(`/api/channels/${channel.value.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Redirect to the channel (with new slug if changed)
    router.push(`/@${form.value.slug}`)
  } catch (err) {
    console.error('Error updating channel:', err)
    error.value = err.response?.data?.message || 'Failed to update channel. Please try again.'
  } finally {
    saving.value = false
  }
}

const deleteChannel = async () => {
  if (!confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
    return
  }
  
  try {
    await axios.delete(`/api/channels/${channel.value.id}`)
    router.push('/mychannels')
  } catch (err) {
    console.error('Error deleting channel:', err)
    error.value = err.response?.data?.message || 'Failed to delete channel.'
  }
}

// Watch slug changes
watch(() => form.value.slug, () => {
  if (form.value.slug !== channel.value.slug) {
    checkSlugAvailability()
  } else {
    slugAvailable.value = true
    slugError.value = null
  }
})

// Lifecycle
onMounted(() => {
  fetchChannel()
})
</script>