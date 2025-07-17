<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Create a Channel</h1>
          
          <form @submit.prevent="createChannel" class="space-y-6">
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
                placeholder="My Awesome Channel"
              >
              <p class="mt-1 text-sm text-gray-500">This will generate your channel URL: @{{ generatedSlug || 'channel-slug' }}</p>
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
                placeholder="Tell people what your channel is about..."
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
                    v-if="avatarPreview" 
                    :src="avatarPreview" 
                    alt="Avatar preview"
                    class="h-full w-full object-cover"
                  >
                  <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
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
                  Choose Avatar
                </button>
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
                    v-if="bannerPreview" 
                    :src="bannerPreview" 
                    alt="Banner preview"
                    class="h-full w-full object-cover"
                  >
                  <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
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
                  class="mt-2 px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  Choose Banner
                </button>
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
            <div class="flex items-center justify-end space-x-3">
              <router-link
                to="/profile/edit"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Cancel
              </router-link>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ loading ? 'Creating...' : 'Create Channel' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// Form data
const form = ref({
  name: '',
  description: '',
  is_public: true
})

const avatarFile = ref(null)
const bannerFile = ref(null)
const avatarPreview = ref(null)
const bannerPreview = ref(null)
const loading = ref(false)
const error = ref(null)

// Computed
const generatedSlug = computed(() => {
  if (!form.value.name) return ''
  return form.value.name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
})

// Methods
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

const createChannel = async () => {
  loading.value = true
  error.value = null
  
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('description', form.value.description || '')
    formData.append('is_public', form.value.is_public ? '1' : '0')
    
    if (avatarFile.value) {
      formData.append('avatar_image', avatarFile.value)
    }
    
    if (bannerFile.value) {
      formData.append('banner_image', bannerFile.value)
    }
    
    const response = await axios.post('/api/channels', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Redirect to the new channel
    router.push(`/@${response.data.channel.slug}`)
  } catch (err) {
    console.error('Error creating channel:', err)
    error.value = err.response?.data?.message || 'Failed to create channel. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>