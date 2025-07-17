<template>
  <div class="min-h-screen bg-gray-50">
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
                    :to="`/@${channel.user.custom_url || channel.user.username}`"
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
          <router-link
            v-for="list in lists"
            :key="list.id"
            :to="`/@${channel.slug}/${list.slug}`"
            class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden"
          >
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
              <div class="mt-2 flex items-center text-sm text-gray-500">
                <span>{{ list.items_count }} items</span>
                <span v-if="list.category" class="ml-4">{{ list.category.name }}</span>
              </div>
            </div>
          </router-link>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  channel: {
    type: Object,
    required: true
  }
})

const authStore = useAuthStore()

// Data
const lists = ref([])
const listsLoading = ref(false)
const listsPage = ref(1)
const hasMoreLists = ref(true)
const isFollowing = ref(props.channel.is_following || false)
const followLoading = ref(false)

// Computed
const isOwner = computed(() => {
  return authStore.user?.id === props.channel.user_id
})

const defaultAvatar = computed(() => {
  // Generate default avatar with channel initials
  const initials = props.channel.name
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
const fetchLists = async (page = 1) => {
  listsLoading.value = true
  
  try {
    const response = await axios.get(`/api/channels/${props.channel.id}/lists`, {
      params: {
        page,
        per_page: 12
      }
    })
    
    if (page === 1) {
      lists.value = response.data.data
    } else {
      lists.value.push(...response.data.data)
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

const toggleFollow = async () => {
  if (!authStore.isAuthenticated) {
    // Redirect to login
    router.push({ name: 'Login', query: { redirect: route.fullPath } })
    return
  }
  
  followLoading.value = true
  
  try {
    if (isFollowing.value) {
      await axios.delete(`/api/channels/${props.channel.id}/follow`)
      isFollowing.value = false
      props.channel.followers_count--
    } else {
      await axios.post(`/api/channels/${props.channel.id}/follow`)
      isFollowing.value = true
      props.channel.followers_count++
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
  fetchLists()
})
</script>