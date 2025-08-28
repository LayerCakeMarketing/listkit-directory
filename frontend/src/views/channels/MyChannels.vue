<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-900">My Channels</h1>
        <router-link
          to="/channels/create"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
        >
          <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Channel
        </router-link>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Channels grid -->
      <div v-else-if="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="channel in channels"
          :key="channel.id"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden"
        >
          <!-- Banner -->
          <div class="h-32 bg-gradient-to-r from-blue-600 to-purple-600 relative">
            <img 
              v-if="channel.banner_url" 
              :src="channel.banner_url" 
              :alt="`${channel.name} banner`"
              class="absolute inset-0 w-full h-full object-cover"
            >
          </div>
          
          <!-- Content -->
          <div class="p-4">
            <div class="flex items-start space-x-3">
              <!-- Avatar -->
              <img
                :src="channel.avatar_url || defaultAvatar(channel.name)"
                :alt="channel.name"
                class="h-12 w-12 rounded-full"
              >
              
              <!-- Info -->
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                  <router-link :to="`/${channel.slug}`" class="hover:text-blue-600">
                    {{ channel.name }}
                  </router-link>
                </h3>
                <p class="text-sm text-gray-500">@{{ channel.slug }}</p>
              </div>
            </div>
            
            <p v-if="channel.description" class="mt-3 text-sm text-gray-600 line-clamp-2">
              {{ channel.description }}
            </p>
            
            <!-- Stats -->
            <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
              <div class="flex items-center space-x-4">
                <span>{{ channel.lists_count }} lists</span>
                <span>{{ channel.followers_count }} followers</span>
              </div>
              <span v-if="!channel.is_public" class="text-xs bg-gray-100 px-2 py-1 rounded">
                Private
              </span>
            </div>
            
            <!-- Actions -->
            <div class="mt-4 flex items-center space-x-2">
              <router-link
                :to="`/${channel.slug}`"
                class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                View
              </router-link>
              <router-link
                :to="`/channels/${channel.id}/edit`"
                class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Edit
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No channels yet</h3>
        <p class="mt-1 text-sm text-gray-500">Create your first channel to start organizing your lists.</p>
        <div class="mt-6">
          <router-link
            to="/channels/create"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
          >
            <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Channel
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Data
const channels = ref([])
const loading = ref(true)
const error = ref(null)

// Methods
const fetchChannels = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/my-channels')
    channels.value = response.data
  } catch (err) {
    console.error('Error fetching channels:', err)
    error.value = 'Failed to load channels'
  } finally {
    loading.value = false
  }
}

const defaultAvatar = (name) => {
  const initials = name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
  
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
    <rect width="100" height="100" fill="#6366f1"/>
    <text x="50" y="50" font-family="Arial, sans-serif" font-size="40" fill="white" text-anchor="middle" dominant-baseline="central">${initials}</text>
  </svg>`
  
  return `data:image/svg+xml;base64,${btoa(svg)}`
}

// Lifecycle
onMounted(() => {
  fetchChannels()
})
</script>