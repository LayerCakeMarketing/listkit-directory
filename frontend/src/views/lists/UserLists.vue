<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- User Lists Page -->
    <div v-else-if="user">
      <!-- Header Section -->
      <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div class="flex items-center space-x-4">
            <img
              :src="user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&size=64`"
              alt="Avatar"
              class="w-16 h-16 rounded-full"
            />
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ user.page_title || user.name }}'s Lists
              </h1>
              <p class="text-gray-600">
                {{ totalLists }} {{ totalLists === 1 ? 'list' : 'lists' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Lists Grid -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div v-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-white rounded-lg shadow hover:shadow-md transition-shadow"
          >
            <router-link
              :to="`/up/@${user.custom_url || user.username}/${list.slug}`"
              class="block p-6"
            >
              <!-- List Image -->
              <div v-if="list.featured_image_url || list.featured_image" class="h-48 rounded-lg overflow-hidden mb-4">
                <img 
                  :src="list.featured_image_url || list.featured_image" 
                  :alt="list.name" 
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- List Info -->
              <h3 class="font-semibold text-lg text-gray-900 mb-2">{{ list.name }}</h3>

              <!-- List Stats -->
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">
                  {{ list.items_count }} {{ list.items_count === 1 ? 'item' : 'items' }}
                </span>
                <span class="text-gray-500">
                  {{ list.view_count }} views
                </span>
              </div>

              <!-- Pinned Badge -->
              <div v-if="list.is_pinned" class="mt-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                  </svg>
                  Pinned
                </span>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No lists yet</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ isOwnProfile ? "You haven't created any lists yet." : "This user hasn't created any public lists yet." }}
          </p>
          <div v-if="isOwnProfile" class="mt-6">
            <router-link
              to="/lists"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              Create your first list
            </router-link>
          </div>
        </div>

        <!-- Back to Profile -->
        <div class="mt-8 text-center">
          <router-link
            :to="`/up/@${user.custom_url || user.username}`"
            class="text-indigo-600 hover:text-indigo-500"
          >
            ← Back to {{ user.name }}'s profile
          </router-link>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="flex flex-col items-center justify-center h-screen">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">User Not Found</h1>
      <p class="text-gray-600 mb-8">The user you're looking for doesn't exist.</p>
      <router-link to="/" class="text-indigo-600 hover:underline">Go to Home</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const route = useRoute()
const authStore = useAuthStore()

const loading = ref(true)
const user = ref(null)
const lists = ref([])
const totalLists = ref(0)

const currentUser = computed(() => authStore.user)
const isOwnProfile = computed(() => currentUser.value?.id === user.value?.id)

async function fetchUserLists() {
  loading.value = true
  
  try {
    const username = route.params.username
    const response = await axios.get(`/api/users/${username}/lists`)
    
    user.value = response.data.user
    lists.value = response.data.lists
    totalLists.value = response.data.total
  } catch (error) {
    console.error('Failed to fetch user lists:', error)
    user.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchUserLists()
})
</script>