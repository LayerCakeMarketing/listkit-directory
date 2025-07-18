<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-700">{{ error }}</p>
      </div>
    </div>
    
    <!-- Channel content -->
    <template v-else-if="channel">
      <!-- Banner -->
      <div class="relative h-48 md:h-64 bg-gradient-to-r from-blue-600 to-purple-600">
        <img 
          v-if="channel.banner_url" 
          :src="channel.banner_url" 
          :alt="`${channel.name} banner`"
          class="absolute inset-0 w-full h-full object-cover"
        >
        <div class="absolute inset-0 bg-black bg-opacity-30"></div>
      </div>

    <!-- Channel Info -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="relative -mt-16 sm:-mt-20">
        <div class="bg-white rounded-lg shadow-lg p-6">
          <div class="sm:flex sm:items-start sm:justify-between">
            <div class="sm:flex sm:items-start sm:space-x-5">
              <!-- Avatar -->
              <div class="flex-shrink-0">
                <img
                  :src="channel.avatar_url || defaultAvatar"
                  :alt="channel.name"
                  class="h-24 w-24 rounded-full ring-4 ring-white"
                >
              </div>
              
              <!-- Channel details -->
              <div class="mt-4 sm:mt-0">
                <h1 class="text-2xl font-bold text-gray-900">{{ channel.name }}</h1>
                <p class="text-sm text-gray-500">@{{ channel.slug }}</p>
                <p v-if="channel.description" class="mt-2 text-gray-700">{{ channel.description }}</p>
                
                <!-- Stats -->
                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500">
                  <span>
                    <strong class="text-gray-900">{{ channel.lists_count }}</strong> lists
                  </span>
                  <span>
                    <strong class="text-gray-900">{{ channel.followers_count }}</strong> followers
                  </span>
                </div>
                
                <!-- Owner -->
                <div class="mt-2 text-sm text-gray-500">
                  Created by 
                  <router-link 
                    :to="`/up/@${channel.user.custom_url || channel.user.username}`"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    {{ channel.user.name }}
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-5 sm:mt-0 sm:ml-4 flex flex-col sm:flex-row gap-2">
              <!-- Follow/Unfollow button -->
              <button
                v-if="authStore.isAuthenticated && !isOwner"
                @click="toggleFollow"
                :disabled="followLoading"
                :class="[
                  'inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium transition-colors',
                  isFollowing
                    ? 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                    : 'border-transparent text-white bg-blue-600 hover:bg-blue-700',
                  followLoading && 'opacity-50 cursor-not-allowed'
                ]"
              >
                {{ isFollowing ? 'Following' : 'Follow' }}
              </button>

              <!-- Edit button (for owner) -->
              <router-link
                v-if="isOwner"
                :to="`/channels/${channel.id}/edit`"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Edit Channel
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Channel Lists -->
      <div class="mt-8 pb-12">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Lists</h2>
        
        <!-- Loading -->
        <div v-if="listsLoading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Lists grid -->
        <div v-else-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden relative"
          >
            <router-link :to="`/@${channel.slug}/${list.slug}`">
              <div v-if="list.featured_image_url" class="h-48 bg-gray-200">
                <img
                  :src="list.featured_image_url"
                  :alt="list.name"
                  class="w-full h-full object-cover"
                />
              </div>
              <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ list.name }}</h3>
                <p v-if="list.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                  {{ list.description }}
                </p>
                <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                  <div class="flex items-center space-x-4">
                    <span>{{ list.items_count }} items</span>
                    <span v-if="list.category">{{ list.category.name }}</span>
                  </div>
                </div>
              </div>
            </router-link>
            
            <!-- Quick actions -->
            <div class="absolute top-4 right-4 flex items-center space-x-2">
              <span 
                v-if="list.status === 'on_hold'"
                class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"
              >
                On Hold
              </span>
              <span 
                v-else
                :class="[
                  'px-2 py-1 text-xs rounded-full',
                  list.visibility === 'public' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                ]"
              >
                {{ list.visibility }}
              </span>
              
              <!-- Dropdown Menu -->
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button class="flex items-center rounded-full bg-gray-100 p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-100">
                    <span class="sr-only">Open options</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                      <path d="M10 3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10 8.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM11.5 15.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
                    </svg>
                  </button>
                </template>
                <template #content>
                  <div class="py-1">
                    <router-link
                      :to="`/@${channel.slug}/${list.slug}`"
                      class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View List
                    </router-link>
                    
                    <router-link
                      v-if="isOwner"
                      :to="`/mylists/${list.id}/edit`"
                      class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      Edit List
                    </router-link>
                    
                    <button
                      @click="shareList(list)"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                      </svg>
                      Share
                    </button>
                    
                    <button
                      v-if="isOwner"
                      @click="deleteList(list)"
                      class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                      <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Delete
                    </button>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No lists yet</h3>
          <p class="mt-1 text-sm text-gray-500">This channel hasn't created any lists yet.</p>
        </div>

        <!-- Load more -->
        <div v-if="hasMoreLists" class="mt-8 text-center">
          <button
            @click="loadMoreLists"
            :disabled="listsLoading"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Load More
          </button>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import Dropdown from '@/components/Dropdown.vue'

const props = defineProps({
  slug: {
    type: String,
    required: true
  }
})

const authStore = useAuthStore()
const router = useRouter()

// Data
const channel = ref(null)
const loading = ref(true)
const error = ref(null)
const lists = ref([])
const listsLoading = ref(false)
const listsPage = ref(1)
const hasMoreLists = ref(true)
const isFollowing = ref(false)
const followLoading = ref(false)

// Computed
const isOwner = computed(() => {
  return authStore.user?.id === channel.value?.user_id
})

const defaultAvatar = computed(() => {
  if (!channel.value) return ''
  
  // Generate default avatar with channel initials
  const initials = channel.value.name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
  
  // Create a simple SVG data URL
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
    <rect width="100" height="100" fill="#6366f1"/>
    <text x="50" y="50" font-family="Arial, sans-serif" font-size="40" fill="white" text-anchor="middle" dominant-baseline="central">${initials}</text>
  </svg>`
  
  return `data:image/svg+xml;base64,${btoa(svg)}`
})

// Methods
const fetchChannel = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get(`/api/@${props.slug}`)
    channel.value = response.data
    isFollowing.value = response.data.is_following || false
    
    // Fetch lists after channel is loaded
    await fetchLists()
  } catch (err) {
    console.error('Error fetching channel:', err)
    error.value = err.response?.data?.message || 'Failed to load channel'
  } finally {
    loading.value = false
  }
}

const fetchLists = async (page = 1) => {
  if (!channel.value) return
  
  listsLoading.value = true
  
  try {
    console.log('Fetching lists for channel:', channel.value.slug)
    const response = await axios.get(`/api/channels/${channel.value.slug}/lists`, {
      params: {
        page,
        per_page: 12
      }
    })
    
    console.log('Channel lists response:', response.data)
    
    if (page === 1) {
      lists.value = response.data.data || []
    } else {
      lists.value.push(...(response.data.data || []))
    }
    
    hasMoreLists.value = response.data.current_page < response.data.last_page
    listsPage.value = response.data.current_page
  } catch (error) {
    console.error('Error fetching lists:', error)
  } finally {
    listsLoading.value = false
  }
}

const loadMoreLists = () => {
  fetchLists(listsPage.value + 1)
}

const shareList = (list) => {
  // Copy link to clipboard
  const listUrl = `${window.location.origin}/@${channel.value.slug}/${list.slug}`
  navigator.clipboard.writeText(listUrl).then(() => {
    alert('List link copied to clipboard!')
  }).catch(err => {
    console.error('Failed to copy:', err)
    alert('Failed to copy link')
  })
}

const deleteList = async (list) => {
  if (!confirm(`Are you sure you want to delete "${list.name}"?`)) {
    return
  }
  
  try {
    await axios.delete(`/api/lists/${list.id}`)
    // Remove from lists array
    const index = lists.value.findIndex(l => l.id === list.id)
    if (index > -1) {
      lists.value.splice(index, 1)
    }
    // Update channel lists count
    if (channel.value.lists_count > 0) {
      channel.value.lists_count--
    }
  } catch (error) {
    console.error('Error deleting list:', error)
    alert('Failed to delete list')
  }
}

const toggleFollow = async () => {
  if (!authStore.isAuthenticated) {
    // Redirect to login
    router.push({ name: 'Login', query: { redirect: router.currentRoute.value.fullPath } })
    return
  }
  
  followLoading.value = true
  
  try {
    if (isFollowing.value) {
      await axios.delete(`/api/channels/${channel.value.slug}/follow`)
      isFollowing.value = false
      channel.value.followers_count--
    } else {
      await axios.post(`/api/channels/${channel.value.slug}/follow`)
      isFollowing.value = true
      channel.value.followers_count++
    }
  } catch (error) {
    console.error('Error toggling follow:', error)
    // Revert on error
    if (error.response?.data?.message) {
      alert(error.response.data.message)
    }
  } finally {
    followLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchChannel()
})
</script>