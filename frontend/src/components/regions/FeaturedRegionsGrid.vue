<template>
  <div v-if="featuredRegions.length > 0" class="featured-regions-section">
    <div class="bg-white shadow-sm rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Featured Areas in {{ regionName }}</h2>
      </div>
      
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <router-link
            v-for="region in featuredRegions"
            :key="region.id"
            :to="region.url"
            class="group block"
          >
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all duration-200">
              <!-- Cover Image -->
              <div class="relative h-48">
                <img 
                  v-if="region.cover_image_url"
                  :src="region.cover_image_url" 
                  :alt="region.name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                  <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                
                <!-- Label Badge -->
                <div v-if="region.label" class="absolute top-3 left-3">
                  <span class="px-3 py-1 bg-purple-600 text-white text-xs font-medium rounded-full shadow-lg">
                    {{ region.label }}
                  </span>
                </div>
                
                <!-- Type Badge -->
                <div class="absolute bottom-3 right-3">
                  <span :class="getTypeBadgeClass(region.type)" class="px-2 py-1 text-xs font-medium rounded-full shadow">
                    {{ formatType(region.type) }}
                  </span>
                </div>
              </div>
              
              <!-- Content -->
              <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                  {{ region.name }}
                </h3>
                
                <p v-if="region.description" class="mt-2 text-sm text-gray-600 line-clamp-2">
                  {{ region.description }}
                </p>
                <p v-else-if="region.intro_text" class="mt-2 text-sm text-gray-600 line-clamp-2">
                  {{ region.intro_text }}
                </p>
                
                <div class="mt-3 flex items-center justify-between">
                  <div class="flex items-center space-x-3 text-sm text-gray-500">
                    <span v-if="region.cached_place_count > 0" class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      {{ region.cached_place_count }} places
                    </span>
                    <span v-if="region.parent" class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                      </svg>
                      {{ region.parent.name }}
                    </span>
                  </div>
                  
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  regionSlug: {
    type: String,
    required: true
  },
  regionName: {
    type: String,
    required: true
  }
})

// State
const featuredRegions = ref([])
const loading = ref(false)

// Methods
const fetchFeaturedRegions = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/regions/${props.regionSlug}/featured-regions`)
    featuredRegions.value = response.data.featured_regions || []
  } catch (error) {
    console.error('Failed to fetch featured regions:', error)
  } finally {
    loading.value = false
  }
}

const getTypeBadgeClass = (type) => {
  const classes = {
    'state': 'bg-green-100 text-green-800',
    'city': 'bg-blue-100 text-blue-800',
    'neighborhood': 'bg-orange-100 text-orange-800',
    'national_park': 'bg-purple-100 text-purple-800',
    'state_park': 'bg-teal-100 text-teal-800',
    'regional_park': 'bg-indigo-100 text-indigo-800',
    'local_park': 'bg-yellow-100 text-yellow-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const formatType = (type) => {
  const typeNames = {
    'state': 'State',
    'city': 'City',
    'neighborhood': 'Neighborhood',
    'national_park': 'National Park',
    'state_park': 'State Park',
    'regional_park': 'Regional Park',
    'local_park': 'Local Park'
  }
  return typeNames[type] || type
}

// Lifecycle
onMounted(() => {
  fetchFeaturedRegions()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>