<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Discover Channels</h1>
        <p class="mt-2 text-lg text-gray-600">
          Explore curated collections from creators around the world
        </p>
      </div>

      <!-- Search and filters -->
      <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search channels..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @input="debouncedSearch"
          >
        </div>
        <select
          v-model="sortBy"
          @change="fetchChannels"
          class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="created_at">Newest</option>
          <option value="followers">Most Followed</option>
          <option value="lists">Most Lists</option>
          <option value="name">Alphabetical</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="loading && channels.length === 0" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Channels grid -->
      <div v-else-if="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <router-link
          v-for="channel in channels"
          :key="channel.id"
          :to="`/@${channel.slug}`"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group"
        >
          <!-- Banner -->
          <div class="h-32 bg-gradient-to-r from-blue-600 to-purple-600 relative">
            <img 
              v-if="channel.banner_url" 
              :src="channel.banner_url" 
              :alt="`${channel.name} banner`"
              class="absolute inset-0 w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-opacity"></div>
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
                <h3 class="text-lg font-semibold text-gray-900 truncate group-hover:text-blue-600">
                  {{ channel.name }}
                </h3>
                <p class="text-sm text-gray-500">@{{ channel.slug }}</p>
              </div>
            </div>
            
            <p v-if="channel.description" class="mt-3 text-sm text-gray-600 line-clamp-2">
              {{ channel.description }}
            </p>
            
            <!-- Stats -->
            <div class="mt-4 flex items-center text-sm text-gray-500">
              <span class="mr-4">{{ channel.lists_count }} lists</span>
              <span>{{ channel.followers_count }} followers</span>
            </div>
            
            <!-- Creator -->
            <div class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-500">
              by {{ channel.user.name }}
            </div>
          </div>
        </router-link>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No channels found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try a different search term' : 'Be the first to create a channel!' }}
        </p>
        <div v-if="authStore.isAuthenticated" class="mt-6">
          <router-link
            to="/channels/create"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
          >
            Create Channel
          </router-link>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                page === currentPage
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <span v-else class="px-2 text-gray-500">...</span>
          </template>
          
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash-es'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Data
const channels = ref([])
const loading = ref(false)
const searchQuery = ref('')
const sortBy = ref('created_at')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 12

// Computed
const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
})

// Methods
const fetchChannels = async (page = 1) => {
  loading.value = true
  
  try {
    const response = await axios.get('/api/channels', {
      params: {
        page,
        per_page: perPage,
        search: searchQuery.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'name' ? 'asc' : 'desc'
      }
    })
    
    channels.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
  } catch (error) {
    console.error('Error fetching channels:', error)
  } finally {
    loading.value = false
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    fetchChannels(page)
  }
}

const debouncedSearch = debounce(() => {
  currentPage.value = 1
  fetchChannels()
}, 300)

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