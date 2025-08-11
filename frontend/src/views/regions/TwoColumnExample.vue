<template>
  <div class="h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Region Explorer</h1>
        <div class="flex items-center gap-4">
          <!-- Region Selector -->
          <select 
            v-model="selectedRegionId" 
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select a region...</option>
            <option 
              v-for="region in sampleRegions" 
              :key="region.id" 
              :value="region.id"
            >
              {{ region.name }} ({{ region.type }})
            </option>
          </select>
          
          <!-- View Toggle -->
          <div class="flex items-center gap-2">
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                viewMode === 'list' 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              ]"
            >
              List Only
            </button>
            <button
              @click="viewMode = 'two-column'"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                viewMode === 'two-column' 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              ]"
            >
              Two Column
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-hidden" style="height: calc(100vh - 80px);">
      <!-- Two Column View -->
      <RegionTwoColumnView
        v-if="viewMode === 'two-column' && selectedRegionId"
        :key="selectedRegionId"
        :region-id="selectedRegionId"
        @place-selected="handlePlaceSelected"
        @region-loaded="handleRegionLoaded"
      />
      
      <!-- Fallback List View -->
      <div v-else-if="viewMode === 'list'" class="h-full overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
          <h2 class="text-xl font-semibold mb-6">Traditional List View</h2>
          <p class="text-gray-600 mb-8">This demonstrates the traditional list-only view for comparison.</p>
          
          <div v-if="selectedRegion" class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium mb-2">{{ selectedRegion.name }}</h3>
            <p class="text-sm text-gray-600">{{ selectedRegion.description || 'No description available.' }}</p>
          </div>
          
          <!-- Sample places would be loaded here -->
          <div class="space-y-4">
            <div v-for="n in 5" :key="n" class="bg-white rounded-lg shadow p-4">
              <div class="animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Welcome State -->
      <div v-else class="h-full flex items-center justify-center bg-gray-50">
        <div class="text-center max-w-md mx-auto px-6">
          <div class="mx-auto h-24 w-24 text-gray-400 mb-6">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
          </div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-4">Region Two-Column View Demo</h2>
          <p class="text-gray-600 mb-8">
            Select a region from the dropdown above to see the two-column layout in action. 
            This component provides an immersive way to browse places with an integrated map view.
          </p>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
            <h3 class="font-medium text-blue-900 mb-2">Features Demonstrated:</h3>
            <ul class="text-sm text-blue-800 space-y-1">
              <li>• Responsive two-column layout (40% list, 60% map)</li>
              <li>• Real-time search and category filtering</li>
              <li>• Interactive Mapbox map with clustering</li>
              <li>• Mobile-responsive with collapsible sections</li>
              <li>• Place selection synchronization</li>
              <li>• Infinite scroll for large datasets</li>
              <li>• Accessibility features and keyboard navigation</li>
            </ul>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Selected Place Info Panel (Fixed Position) -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="selectedPlace"
        class="fixed bottom-4 left-4 right-4 md:left-auto md:w-80 bg-white rounded-lg shadow-xl border border-gray-200 p-4 z-50"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ selectedPlace.name }}</h3>
            <p v-if="selectedPlace.category" class="text-sm text-gray-600">{{ selectedPlace.category.name }}</p>
          </div>
          <button
            @click="selectedPlace = null"
            class="ml-2 p-1 rounded-full hover:bg-gray-100 transition-colors"
            aria-label="Close"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <p v-if="selectedPlace.description" class="text-sm text-gray-700 mb-3 line-clamp-2">
          {{ selectedPlace.description }}
        </p>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center text-sm text-gray-500">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ selectedPlace.address || 'Address not available' }}
          </div>
          
          <button class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
            View Details
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import RegionTwoColumnView from '@/components/regions/RegionTwoColumnView.vue'

// State
const selectedRegionId = ref('')
const selectedRegion = ref(null)
const selectedPlace = ref(null)
const viewMode = ref('two-column') // 'two-column' | 'list'

// Sample data for demonstration
const sampleRegions = [
  {
    id: 1,
    name: 'San Francisco',
    type: 'city',
    description: 'A vibrant city known for its iconic landmarks, diverse culture, and innovative spirit.',
    place_count: 1248,
    population: 873000
  },
  {
    id: 2,
    name: 'Mission District',
    type: 'neighborhood',
    description: 'A historic neighborhood famous for its street art, Latino culture, and culinary scene.',
    place_count: 324,
    population: 45000
  },
  {
    id: 3,
    name: 'Yosemite National Park',
    type: 'national_park',
    description: 'Iconic national park featuring waterfalls, giant sequoias, and granite cliffs.',
    place_count: 89,
    park_info: {
      hours: '24 hours',
      entrance_fee: '$35 per vehicle',
      activities: ['Hiking', 'Rock Climbing', 'Photography', 'Camping']
    }
  },
  {
    id: 4,
    name: 'Point Reyes State Park',
    type: 'state_park',
    description: 'Coastal park with lighthouse, hiking trails, and wildlife viewing opportunities.',
    place_count: 45,
    park_info: {
      hours: '8 AM - 8 PM',
      entrance_fee: '$10 per vehicle',
      activities: ['Hiking', 'Bird Watching', 'Beach Activities']
    }
  },
  {
    id: 5,
    name: 'California',
    type: 'state',
    description: 'The Golden State, home to diverse landscapes from beaches to mountains.',
    place_count: 15420,
    population: 39500000
  }
]

// Computed
const currentRegion = computed(() => {
  if (!selectedRegionId.value) return null
  return sampleRegions.find(r => r.id === parseInt(selectedRegionId.value))
})

// Event handlers
const handlePlaceSelected = (place) => {
  selectedPlace.value = place
  console.log('Place selected:', place)
}

const handleRegionLoaded = (region) => {
  selectedRegion.value = region
  console.log('Region loaded:', region)
}

// Watchers
watch(selectedRegionId, () => {
  selectedPlace.value = null // Clear selected place when region changes
})
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Transitions */
.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>