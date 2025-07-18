<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-16 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Edit Channel</h1>
          
          <!-- Form Actions (moved to top) -->
          <div v-if="!loading" class="flex items-center space-x-4">
            <span v-if="channelUpdated" class="text-sm text-green-600 mr-4">
              Channel updated successfully!
            </span>
            <router-link
              :to="`/@${channel.slug}`"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </router-link>
            <button
              @click="saveChannel"
              :disabled="saving || activeTab === 'lists'"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
            <a
              :href="`/@${channel.slug}`"
              target="_blank"
              class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200"
              title="View Channel"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Tabbed Interface -->
        <div v-else>
          <!-- Tab Navigation -->
          <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                type="button"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="space-y-8">
            <!-- Basic Information Tab -->
            <div v-show="activeTab === 'basic'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium mb-4">Basic Information</h2>
                
                <div class="space-y-6">
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
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
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
                </div>
              </div>
            </div>

            <!-- Images Tab -->
            <div v-show="activeTab === 'images'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium mb-4">Channel Images</h2>
                
                <div class="space-y-8">
                  <!-- Avatar Image -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Avatar Image
                    </label>
                    <div class="flex items-start space-x-4">
                      <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                        <img 
                          v-if="avatarCloudflareId || channel.avatar_url" 
                          :src="avatarCloudflareId ? `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${avatarCloudflareId}/public` : channel.avatar_url" 
                          alt="Avatar"
                          class="h-full w-full object-cover"
                        >
                        <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <div class="flex-1">
                        <CloudflareDragDropUploader
                          :max-files="1"
                          context="avatar"
                          :entity-type="'App\\Models\\Channel'"
                          :entity-id="channel.id"
                          :metadata="{
                            context: 'avatar'
                          }"
                          @upload-success="handleAvatarUpload"
                          :compact="true"
                          :show-preview="false"
                        >
                          <template #trigger>
                            <button
                              type="button"
                              class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                              Change Avatar
                            </button>
                          </template>
                        </CloudflareDragDropUploader>
                        <button
                          v-if="avatarCloudflareId || channel.avatar_cloudflare_id"
                          type="button"
                          @click="removeAvatar"
                          class="ml-2 px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Banner Image -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Banner Image
                    </label>
                    <div class="h-32 w-full rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden mb-2">
                      <img 
                        v-if="bannerCloudflareId || channel.banner_url" 
                        :src="bannerCloudflareId ? `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${bannerCloudflareId}/public` : channel.banner_url" 
                        alt="Banner"
                        class="h-full w-full object-cover"
                      >
                      <svg v-else class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="flex items-center space-x-2">
                      <CloudflareDragDropUploader
                        :max-files="1"
                        context="banner"
                        :entity-type="'App\\Models\\Channel'"
                        :entity-id="channel.id"
                        :metadata="{
                          context: 'banner'
                        }"
                        @upload-success="handleBannerUpload"
                        :compact="true"
                        :show-preview="false"
                      >
                        <template #trigger>
                          <button
                            type="button"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                          >
                            Change Banner
                          </button>
                        </template>
                      </CloudflareDragDropUploader>
                      <button
                        v-if="bannerCloudflareId || channel.banner_cloudflare_id"
                        type="button"
                        @click="removeBanner"
                        class="px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lists Tab -->
            <div v-show="activeTab === 'lists'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                  <h2 class="text-lg font-medium">Channel Lists</h2>
                  <router-link
                    :to="`/channels/${channel.id}/lists/create`"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create List
                  </router-link>
                </div>
                
                <!-- Lists Grid -->
                <div v-if="listsLoading" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>
                
                <div v-else-if="channelLists.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div
                    v-for="list in channelLists"
                    :key="list.id"
                    class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <h3 class="font-medium text-gray-900">{{ list.name }}</h3>
                        <p v-if="list.description" class="text-sm text-gray-600 mt-1">{{ list.description }}</p>
                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                          <span>{{ list.items_count || 0 }} items</span>
                          <span v-if="list.category">{{ list.category.name }}</span>
                          <span v-if="list.status !== 'on_hold'">{{ list.visibility === 'public' ? 'Public' : 'Private' }}</span>
                          <span v-if="list.status === 'on_hold'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                            On Hold
                          </span>
                          <span v-if="list.is_draft" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            Draft
                          </span>
                        </div>
                      </div>
                      <div class="flex items-center space-x-2 ml-4">
                        <router-link
                          :to="`/@${channel.slug}/${list.slug}`"
                          class="text-gray-400 hover:text-gray-600"
                          title="View List"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </router-link>
                        <router-link
                          :to="`/lists/${list.id}/edit`"
                          class="text-gray-400 hover:text-gray-600"
                          title="Edit List"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-900">No lists created yet</p>
                  <router-link 
                    :to="`/channels/${channel.id}/lists/create`"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                  >
                    Create Your First List
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Owner Tab -->
            <div v-show="activeTab === 'owner'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium mb-4">Owner Settings</h2>
                
                <div class="space-y-6">
                  <!-- Analytics Summary -->
                  <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Channel Analytics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total Lists</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ channel.lists_count || 0 }}</p>
                      </div>
                      <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Followers</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ channel.followers_count || 0 }}</p>
                      </div>
                      <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Total Views</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ channel.views_count || 0 }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Danger Zone -->
                  <div class="border-t pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Danger Zone</h3>
                    <div class="space-y-4">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-medium text-gray-900">Delete Channel</p>
                          <p class="text-sm text-gray-500">Permanently delete this channel and all its data</p>
                        </div>
                        <button
                          @click="deleteChannel"
                          class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                        >
                          Delete Channel
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Error message -->
          <div v-if="error" class="mt-4 rounded-md bg-red-50 p-4">
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
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'

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
  id: {
    type: String,
    required: true
  }
})

const router = useRouter()
const route = useRoute()

// Tabs configuration
const tabs = [
  { id: 'basic', name: 'Basic Information' },
  { id: 'images', name: 'Images' },
  { id: 'lists', name: 'Lists' },
  { id: 'owner', name: 'Owner' }
]

// Data
const activeTab = ref('basic')
const channel = ref({})
const form = reactive({
  name: '',
  slug: '',
  description: '',
  is_public: true
})
const avatarCloudflareId = ref(null)
const bannerCloudflareId = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const slugChecking = ref(false)
const slugAvailable = ref(null)
const slugError = ref(null)
const channelUpdated = ref(false)
const channelLists = ref([])
const listsLoading = ref(false)

// Methods
const fetchChannel = async () => {
  loading.value = true
  
  try {
    const response = await axios.get(`/api/channels/${props.id}`)
    channel.value = response.data
    
    // Populate form
    form.name = response.data.name
    form.slug = response.data.slug
    form.description = response.data.description || ''
    form.is_public = response.data.is_public
    
    // Populate cloudflare IDs
    avatarCloudflareId.value = response.data.avatar_cloudflare_id
    bannerCloudflareId.value = response.data.banner_cloudflare_id
    
    // Fetch lists if on lists tab
    if (activeTab.value === 'lists') {
      await fetchChannelLists()
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

const fetchChannelLists = async () => {
  listsLoading.value = true
  
  try {
    const response = await axios.get(`/api/channels/${channel.value.slug}/lists`)
    channelLists.value = response.data.data || []
  } catch (err) {
    console.error('Error fetching channel lists:', err)
  } finally {
    listsLoading.value = false
  }
}

const checkSlugAvailability = debounce(async () => {
  if (!form.slug || form.slug === channel.value.slug) {
    slugAvailable.value = true
    slugError.value = null
    return
  }
  
  slugChecking.value = true
  slugError.value = null
  
  try {
    const response = await axios.post('/api/channels/check-slug', {
      slug: form.slug,
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

const handleAvatarUpload = (uploadedImage) => {
  console.log('Avatar uploaded:', uploadedImage)
  avatarCloudflareId.value = uploadedImage.cloudflare_id || uploadedImage.id
  error.value = null
}

const handleBannerUpload = (uploadedImage) => {
  console.log('Banner uploaded:', uploadedImage)
  bannerCloudflareId.value = uploadedImage.cloudflare_id || uploadedImage.id
  error.value = null
}

const removeAvatar = async () => {
  avatarCloudflareId.value = null
  channel.value.avatar_cloudflare_id = null
}

const removeBanner = async () => {
  bannerCloudflareId.value = null
  channel.value.banner_cloudflare_id = null
}

const saveChannel = async () => {
  if (activeTab.value === 'lists') return // Don't save on lists tab
  
  await updateChannel()
}

const updateChannel = async () => {
  saving.value = true
  error.value = null
  channelUpdated.value = false
  
  try {
    const payload = {
      name: form.name,
      slug: form.slug,
      description: form.description || '',
      is_public: form.is_public,
      avatar_cloudflare_id: avatarCloudflareId.value || channel.value.avatar_cloudflare_id || null,
      banner_cloudflare_id: bannerCloudflareId.value || channel.value.banner_cloudflare_id || null
    }
    
    await axios.put(`/api/channels/${channel.value.slug}`, payload)
    
    channelUpdated.value = true
    
    // Update the channel slug in case it changed
    channel.value.slug = form.slug
    
    // Hide success message after 3 seconds
    setTimeout(() => {
      channelUpdated.value = false
    }, 3000)
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
    await axios.delete(`/api/channels/${channel.value.slug}`)
    router.push('/mychannels')
  } catch (err) {
    console.error('Error deleting channel:', err)
    error.value = err.response?.data?.message || 'Failed to delete channel.'
  }
}

// Watch slug changes
watch(() => form.slug, () => {
  if (form.slug !== channel.value.slug) {
    checkSlugAvailability()
  } else {
    slugAvailable.value = true
    slugError.value = null
  }
})

// Watch tab changes to fetch lists when needed
watch(activeTab, (newTab) => {
  if (newTab === 'lists' && channelLists.value.length === 0 && channel.value.id) {
    fetchChannelLists()
  }
})

// Lifecycle
onMounted(() => {
  console.log('🔥 Channel Edit component loaded with tabs!')
  
  // Check for tab query parameter
  const tab = route.query.tab
  if (tab && tabs.find(t => t.id === tab)) {
    activeTab.value = tab
  }
  
  fetchChannel()
})
</script>