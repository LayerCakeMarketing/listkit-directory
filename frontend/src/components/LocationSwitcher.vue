<template>
  <div class="relative">
    <!-- Location Display -->
    <button
      @click="showModal = true"
      class="flex items-center space-x-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
    >
      <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <span class="text-gray-700 font-medium">{{ currentLocationName }}</span>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <!-- Location Modal -->
    <Modal :show="showModal" @close="showModal = false" max-width="2xl">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Your Location</h3>
        
        <!-- Search -->
        <div class="mb-6">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search for a city or neighborhood..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="handleSearch"
            />
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>

        <!-- Current Location -->
        <div v-if="detectedLocation" class="mb-6 p-4 bg-blue-50 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-600 font-medium">Detected Location</p>
              <p class="text-gray-900">{{ detectedLocation.full_name }}</p>
            </div>
            <button
              v-if="!isCurrentLocation(detectedLocation.id)"
              @click="selectLocation(detectedLocation)"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
            >
              Use This Location
            </button>
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 mb-2">Search Results</h4>
          <div class="space-y-2">
            <button
              v-for="region in searchResults"
              :key="region.id"
              @click="selectLocation(region)"
              class="w-full text-left p-3 rounded-lg hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(region.id) }"
            >
              <div class="font-medium text-gray-900">{{ region.name }}</div>
              <div class="text-sm text-gray-500">{{ region.full_name }}</div>
            </button>
          </div>
        </div>

        <!-- State Selection -->
        <div v-else-if="!selectedState">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Select a State</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button
              v-for="state in states"
              :key="state.id"
              @click="selectState(state)"
              class="p-3 text-left rounded-lg border hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(state.id) }"
            >
              {{ state.name }}
            </button>
          </div>
        </div>

        <!-- City Selection -->
        <div v-else-if="!selectedCity && cities.length > 0">
          <div class="flex items-center mb-3">
            <button @click="selectedState = null" class="text-blue-600 hover:text-blue-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <h4 class="text-sm font-medium text-gray-700 ml-2">Cities in {{ selectedState.name }}</h4>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button
              @click="selectLocation(selectedState)"
              class="p-3 text-left rounded-lg border hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(selectedState.id) }"
            >
              <div class="font-medium">All of {{ selectedState.name }}</div>
              <div class="text-sm text-gray-500">Entire state</div>
            </button>
            <button
              v-for="city in cities"
              :key="city.id"
              @click="selectCity(city)"
              class="p-3 text-left rounded-lg border hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(city.id) }"
            >
              {{ city.name }}
            </button>
          </div>
        </div>

        <!-- Neighborhood Selection -->
        <div v-else-if="neighborhoods.length > 0">
          <div class="flex items-center mb-3">
            <button @click="selectedCity = null" class="text-blue-600 hover:text-blue-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <h4 class="text-sm font-medium text-gray-700 ml-2">Neighborhoods in {{ selectedCity.name }}</h4>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button
              @click="selectLocation(selectedCity)"
              class="p-3 text-left rounded-lg border hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(selectedCity.id) }"
            >
              <div class="font-medium">All of {{ selectedCity.name }}</div>
              <div class="text-sm text-gray-500">Entire city</div>
            </button>
            <button
              v-for="neighborhood in neighborhoods"
              :key="neighborhood.id"
              @click="selectLocation(neighborhood)"
              class="p-3 text-left rounded-lg border hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': isCurrentLocation(neighborhood.id) }"
            >
              {{ neighborhood.name }}
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-between">
          <button
            @click="clearLocation"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
          >
            Use Auto-Detection
          </button>
          <button
            @click="showModal = false"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { debounce } from 'lodash'
import axios from 'axios'
import Modal from '@/components/Modal.vue'

const props = defineProps({
  currentLocation: Object,
  locationBreadcrumb: String
})

const emit = defineEmits(['location-changed'])

// State
const showModal = ref(false)
const loading = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const states = ref([])
const cities = ref([])
const neighborhoods = ref([])
const selectedState = ref(null)
const selectedCity = ref(null)
const detectedLocation = ref(null)

// Computed
const currentLocationName = computed(() => {
  return props.locationBreadcrumb || 'Select Location'
})

// Methods
const isCurrentLocation = (regionId) => {
  return props.currentLocation?.id === regionId
}

const fetchStates = async () => {
  try {
    const response = await axios.get('/api/regions', {
      params: { level: 1 }
    })
    states.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching states:', error)
  }
}

const selectState = async (state) => {
  selectedState.value = state
  loading.value = true
  
  try {
    const response = await axios.get(`/api/regions/${state.id}/children`)
    cities.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching cities:', error)
  } finally {
    loading.value = false
  }
}

const selectCity = async (city) => {
  selectedCity.value = city
  loading.value = true
  
  try {
    const response = await axios.get(`/api/regions/${city.id}/children`)
    neighborhoods.value = response.data.data || []
    
    // If no neighborhoods, select the city
    if (neighborhoods.value.length === 0) {
      selectLocation(city)
    }
  } catch (error) {
    console.error('Error fetching neighborhoods:', error)
  } finally {
    loading.value = false
  }
}

const selectLocation = async (region) => {
  try {
    const response = await axios.post('/api/places/set-location', {
      region_id: region.id
    })
    
    if (response.data.success) {
      emit('location-changed', response.data.location)
      showModal.value = false
      
      // Refresh the current page
      window.location.reload()
    }
  } catch (error) {
    console.error('Error setting location:', error)
  }
}

const clearLocation = async () => {
  try {
    const response = await axios.post('/api/places/clear-location')
    
    if (response.data.success) {
      emit('location-changed', response.data.location)
      showModal.value = false
      window.location.reload()
    }
  } catch (error) {
    console.error('Error clearing location:', error)
  }
}

const handleSearch = debounce(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  
  try {
    const response = await axios.get('/api/regions', {
      params: {
        q: searchQuery.value,
        limit: 10
      }
    })
    searchResults.value = response.data.data || []
  } catch (error) {
    console.error('Error searching regions:', error)
  }
}, 300)

// Initialize
onMounted(() => {
  fetchStates()
  
  // Get detected location if available
  if (props.currentLocation && props.currentLocation.detected_via === 'auto') {
    detectedLocation.value = props.currentLocation
  }
})
</script>