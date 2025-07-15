<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">
            Welcome to {{ appName }}
          </h1>
          <p class="mt-5 max-w-xl mx-auto text-xl text-gray-500">
            Discover amazing places, create lists, and share your favorites with the community.
          </p>
          <div class="mt-8 flex justify-center space-x-4">
            <router-link 
              to="/directory" 
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              Browse Directory
            </router-link>
            <router-link 
              to="/location/california" 
              class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Explore Locations
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-white mt-12">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
          <div class="bg-gray-50 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Total Places</dt>
              <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.totalEntries || 0 }}</dd>
            </div>
          </div>
          <div class="bg-gray-50 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Regions</dt>
              <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.totalRegions || 0 }}</dd>
            </div>
          </div>
          <div class="bg-gray-50 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">User Lists</dt>
              <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.totalLists || 0 }}</dd>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Regions -->
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">Popular Locations</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <router-link
          v-for="region in featuredRegions"
          :key="region.id"
          :to="`/local/${region.slug}`"
          class="block bg-white rounded-lg shadow hover:shadow-lg transition-shadow"
        >
          <div class="h-48 bg-gray-200 rounded-t-lg overflow-hidden">
            <img 
              v-if="region.cover_image_url" 
              :src="region.cover_image_url" 
              :alt="region.name"
              class="w-full h-full object-cover"
            >
          </div>
          <div class="p-4">
            <h3 class="text-lg font-semibold">{{ region.name }}</h3>
            <p class="text-gray-600 text-sm mt-1">{{ region.entries_count }} places</p>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'
const stats = ref({})
const featuredRegions = ref([])

onMounted(async () => {
  // Fetch stats
  try {
    const [statsResponse, regionsResponse] = await Promise.all([
      axios.get('/api/stats'),
      axios.get('/api/regions?level=1&limit=6')
    ])
    
    stats.value = statsResponse.data
    featuredRegions.value = regionsResponse.data.data
  } catch (error) {
    console.error('Failed to load homepage data:', error)
  }
})
</script>