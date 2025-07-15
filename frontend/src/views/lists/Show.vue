<template>
  <div class="min-h-screen bg-gray-50">
    {{ console.log('List Show template rendering') }}
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- List Detail Page -->
    <div v-else-if="list">
      <!-- Cover Image -->
      <div v-if="list.featured_image_url" class="relative h-64 sm:h-80 lg:h-96 bg-gray-900">
        <img 
          :src="list.featured_image_url" 
          :alt="list.name"
          class="w-full h-full object-cover opacity-90"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-8">
          <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white">{{ list.name }}</h1>
            <p v-if="list.description" class="mt-3 text-lg text-white/90 max-w-3xl">
              {{ list.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Header Section -->
      <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h1 v-if="!list.featured_image_url" class="text-3xl font-bold text-gray-900">{{ list.name }}</h1>
              <p v-if="list.description && !list.featured_image_url" class="mt-2 text-lg text-gray-600">
                {{ list.description }}
              </p>
              
              <!-- List Meta -->
              <div class="mt-4 flex items-center space-x-6 text-sm text-gray-500">
                <router-link 
                  v-if="list.user"
                  :to="`/@${list.user.custom_url || list.user.username}`"
                  class="flex items-center hover:text-gray-700"
                >
                  <img 
                    :src="list.user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(list.user.name)}&size=32`"
                    :alt="list.user.name"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span>{{ list.user.name }}</span>
                </router-link>
                
                <span>•</span>
                <span>{{ list.items_count }} {{ list.items_count === 1 ? 'item' : 'items' }}</span>
                <span>•</span>
                <span>{{ list.view_count }} views</span>
                <span v-if="list.updated_at">•</span>
                <span v-if="list.updated_at">Updated {{ formatDate(list.updated_at) }}</span>
              </div>

              <!-- Tags -->
              <div v-if="list.tags && list.tags.length > 0" class="mt-4 flex flex-wrap gap-2">
                <span 
                  v-for="tag in list.tags" 
                  :key="tag.id"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                  :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                >
                  {{ tag.name }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="isOwnList" class="flex items-center space-x-3">
              <router-link
                :to="{ name: 'ListEdit', params: { id: list.id } }"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Edit List
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- List Items -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div v-if="list.items && list.items.length > 0" class="space-y-6">
          <div 
            v-for="(item, index) in list.items" 
            :key="item.id"
            class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
          >
            <div class="p-6">
              <div class="flex items-start space-x-4">
                <!-- Item Number -->
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                  <span class="text-indigo-600 font-semibold">{{ index + 1 }}</span>
                </div>

                <!-- Item Content -->
                <div class="flex-1 min-w-0">
                  <!-- Directory Entry Type (Place) -->
                  <div v-if="item.type === 'directory_entry' && (item.directory_entry || item.place)">
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ item.directory_entry?.title || item.place?.title }}
                    </h3>
                    <p v-if="item.directory_entry?.description || item.place?.description" class="mt-1 text-gray-600">
                      {{ item.directory_entry?.description || item.place?.description }}
                    </p>
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                      <span v-if="item.directory_entry?.location || item.place?.location">
                        📍 {{ item.directory_entry?.location?.city || item.place?.location?.city }}, {{ item.directory_entry?.location?.state || item.place?.location?.state }}
                      </span>
                      <span v-if="item.directory_entry?.category || item.place?.category">
                        🏷️ {{ item.directory_entry?.category?.name || item.place?.category?.name }}
                      </span>
                    </div>
                  </div>

                  <!-- Text Type -->
                  <div v-else-if="item.type === 'text'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600" v-html="item.content"></p>
                  </div>

                  <!-- Location Type -->
                  <div v-else-if="item.type === 'location'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.address">📍 {{ item.data.address }}</span>
                    </div>
                  </div>

                  <!-- Event Type -->
                  <div v-else-if="item.type === 'event'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.start_date">
                        📅 {{ formatDate(item.data.start_date) }}
                      </span>
                      <span v-if="item.data.location" class="ml-4">
                        📍 {{ item.data.location }}
                      </span>
                    </div>
                  </div>

                  <!-- Item Notes -->
                  <div v-if="item.notes" class="mt-3 p-3 bg-gray-50 rounded-md">
                    <p class="text-sm text-gray-700">{{ item.notes }}</p>
                  </div>

                  <!-- Affiliate Link -->
                  <div v-if="item.affiliate_url" class="mt-3">
                    <a 
                      :href="item.affiliate_url" 
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500"
                    >
                      <span>Visit Website</span>
                      <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                      </svg>
                    </a>
                  </div>
                </div>

                <!-- Item Image -->
                <div v-if="item.item_image_cloudflare_id || item.image" class="flex-shrink-0">
                  <img 
                    :src="getItemImageUrl(item)"
                    :alt="item.title || 'Item image'"
                    class="w-24 h-24 rounded-lg object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No items in this list</h3>
          <p class="mt-1 text-sm text-gray-500">This list doesn't have any items yet.</p>
        </div>

        <!-- Back to Lists -->
        <div class="mt-8 text-center">
          <router-link
            v-if="list.user"
            :to="`/@${list.user.custom_url || list.user.username}`"
            class="text-indigo-600 hover:text-indigo-500"
          >
            ← Back to {{ list.user.name }}'s profile
          </router-link>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="flex flex-col items-center justify-center h-screen">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ errorTitle }}</h1>
      <p class="text-gray-600 mb-8">{{ errorMessage }}</p>
      <div class="flex space-x-4">
        <router-link to="/" class="text-indigo-600 hover:underline">Go to Home</router-link>
        <router-link v-if="!authStore.isAuthenticated" to="/login" class="text-indigo-600 hover:underline">Sign In</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const list = ref(null)
const errorTitle = ref('List Not Found')
const errorMessage = ref("The list you're looking for doesn't exist or is private.")

const currentUser = computed(() => authStore.user)
const isOwnList = computed(() => currentUser.value?.id === list.value?.user?.id)

async function fetchList() {
  loading.value = true
  
  try {
    const { username, slug } = route.params
    console.log('Fetching list:', username, slug)
    const response = await axios.get(`/api/users/${username}/${slug}`)
    console.log('List fetched successfully:', response.data)
    list.value = response.data
  } catch (error) {
    console.error('Failed to fetch list:', error)
    
    // If it's a 403 (forbidden) and user is not authenticated, redirect to login
    if (error.response?.status === 403 && !authStore.isAuthenticated) {
      // Save the current route to redirect back after login
      router.push({ 
        name: 'Login', 
        query: { redirect: route.fullPath } 
      })
      return
    }
    
    // Set appropriate error messages based on status
    if (error.response?.status === 403) {
      errorTitle.value = 'Access Denied'
      errorMessage.value = 'You do not have permission to view this list.'
    } else if (error.response?.status === 404) {
      errorTitle.value = 'List Not Found'
      errorMessage.value = "The list you're looking for doesn't exist."
    }
    
    list.value = null
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}

function getItemImageUrl(item) {
  if (item.item_image_cloudflare_id) {
    return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.item_image_cloudflare_id}/public`
  }
  return item.image
}

onMounted(() => {
  console.log('List Show component mounted')
  console.log('Route params:', route.params)
  console.log('Route path:', route.path)
  console.log('Route name:', route.name)
  fetchList()
})
</script>