<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Browse by Region</h1>
        <p class="mt-2 text-lg text-gray-600">Explore places organized by states and cities</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- States Grid -->
      <div v-else class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">United States</h2>
        </div>
        
        <div class="p-6">
          <!-- States List -->
          <div v-if="states.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="state in states" :key="state.id" class="group">
              <router-link 
                :to="`/regions/${state.slug}`"
                class="block p-4 rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                      {{ state.display_name || state.full_name || state.name }}
                    </h3>
                    <p class="text-sm text-gray-500">
                      {{ state.children_count || 0 }} {{ state.children_count === 1 ? 'city' : 'cities' }}
                    </p>
                    <p v-if="state.entries_count > 0" class="text-xs text-gray-400 mt-1">
                      {{ state.entries_count }} {{ state.entries_count === 1 ? 'place' : 'places' }}
                    </p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </router-link>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No regions available</h3>
            <p class="mt-1 text-sm text-gray-500">Check back later for new regions.</p>
          </div>
        </div>
      </div>

      <!-- Popular Cities -->
      <div v-if="popularCities.length > 0" class="mt-8 bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">Popular Cities</h2>
        </div>
        
        <div class="p-6">
          <div class="flex flex-wrap gap-3">
            <router-link
              v-for="city in popularCities"
              :key="city.id"
              :to="`/regions/${city.parent.slug}/${city.slug}`"
              class="inline-flex items-center px-4 py-2 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
            >
              {{ city.display_name || city.full_name || city.name }}, {{ city.parent.display_name || city.parent.full_name || city.parent.name }}
              <span v-if="city.entries_count > 0" class="ml-2 text-xs bg-blue-100 px-2 py-0.5 rounded-full">
                {{ city.entries_count }}
              </span>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const error = ref(null)
const states = ref([])
const popularCities = ref([])

const fetchRegions = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch states (level 1 regions)
    const response = await axios.get('/api/regions', {
      params: {
        level: 1,
        type: 'state',
        with_counts: true,
        sort_by: 'name',
        sort_order: 'asc'
      }
    })
    
    states.value = response.data.data || []
    
    // Fetch popular cities
    const citiesResponse = await axios.get('/api/regions', {
      params: {
        level: 2,
        type: 'city',
        with_counts: true,
        has_entries: true,
        limit: 12,
        sort_by: 'entries_count',
        sort_order: 'desc'
      }
    })
    
    popularCities.value = citiesResponse.data.data || []
    
  } catch (err) {
    console.error('Error fetching regions:', err)
    error.value = 'Failed to load regions. Please try again later.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRegions()
})
</script>