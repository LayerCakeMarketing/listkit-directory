<template>
  <div class="h-screen flex flex-col bg-gray-100">
    <!-- Header -->
    <header class="flex-shrink-0 bg-white shadow-sm border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo/Title -->
          <div class="flex items-center">
            <router-link to="/" class="flex items-center space-x-2">
              <div class="text-xl font-bold text-blue-600">Listerino</div>
            </router-link>
            <div class="hidden sm:block ml-4 text-gray-400">|</div>
            <h1 class="hidden sm:block ml-4 text-lg font-semibold text-gray-900">
              Places Discovery
            </h1>
          </div>

          <!-- Header Actions -->
          <div class="flex items-center space-x-4">
            <!-- Search Bar (Desktop) -->
            <div class="hidden md:block relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                placeholder="Search places, categories, or locations..."
                @keydown.enter="handleSearch"
              />
            </div>

            <!-- Default Location Display -->
            <div v-if="userLocation && userLocation.name" class="hidden lg:flex items-center text-sm text-gray-600">
              <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="truncate max-w-[150px]">{{ userLocation.name }}</span>
            </div>

            <!-- Location Actions -->
            <div class="flex items-center space-x-2">
              <!-- Update Location Button -->
              <button
                @click="handleGetLocation"
                :disabled="geoLoading"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :title="userLocation ? 'Update to current location' : 'Get my location'"
              >
                <svg 
                  :class="[
                    'w-4 h-4',
                    geoLoading ? 'animate-spin' : '',
                    userLocation ? 'text-green-600' : 'text-gray-400'
                  ]" 
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path 
                    v-if="!geoLoading"
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
                  />
                  <circle v-else cx="12" cy="12" r="3"/>
                </svg>
                <span class="hidden sm:inline ml-2">
                  {{ geoLoading ? 'Locating...' : 'Current' }}
                </span>
              </button>

              <!-- Save as Default Button -->
              <button
                v-if="userLocation && !savingDefault"
                @click="saveAsDefaultLocation"
                class="flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                title="Save as default location"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="hidden sm:inline ml-2">Set Default</span>
              </button>
            </div>

            <!-- View Mode Toggle -->
            <div class="flex border border-gray-300 rounded-md">
              <button
                @click="viewMode = 'split'"
                :class="[
                  'p-2 rounded-l-md transition-colors',
                  viewMode === 'split' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-white text-gray-400 hover:text-gray-600'
                ]"
                title="Split view"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2z" />
                </svg>
              </button>
              <button
                @click="viewMode = 'map'"
                :class="[
                  'p-2 border-l transition-colors',
                  viewMode === 'map' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-white text-gray-400 hover:text-gray-600'
                ]"
                title="Map view"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
              </button>
              <button
                @click="viewMode = 'list'"
                :class="[
                  'p-2 rounded-r-md transition-colors',
                  viewMode === 'list' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-white text-gray-400 hover:text-gray-600'
                ]"
                title="List view"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile Search (below header on mobile) -->
        <div class="md:hidden pb-4">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
              placeholder="Search places..."
              @keydown.enter="handleSearch"
            />
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Filters Panel -->
      <FiltersPanel
        :is-open="showFilters"
        :filters="filters"
        :categories="categories"
        :regions="regions"
        :user-location="userLocation"
        @close="showFilters = false"
        @update-filter="updateFilter"
        @clear-all="clearAllFilters"
        @apply-filters="applyFilters"
        @use-location="handleGetLocation"
      />

      <!-- Places List (Split/List View) -->
      <div 
        v-if="viewMode === 'split' || viewMode === 'list'"
        :class="[
          'flex-shrink-0 bg-white',
          viewMode === 'split' ? 'w-[38%]' : 'flex-1'
        ]"
      >
        <PlacesList
          :places="filteredPlaces"
          :is-loading="isLoading"
          :error="error"
          :has-more="hasMore"
          :total="total"
          :selected-place="selectedPlace"
          :hovered-place="hoveredPlace"
          :view-mode="listViewMode"
          :show-filters="showFilters"
          :filters="filters"
          :categories="categories"
          :current-sort="currentSort"
          :sort-options="sortOptions"
          @update:view-mode="listViewMode = $event"
          @update:current-sort="currentSort = $event"
          @select-place="selectPlace"
          @hover-place="hoveredPlace = $event"
          @toggle-filters="showFilters = !showFilters"
          @update-filter="updateFilter"
          @clear-filters="clearAllFilters"
          @load-more="loadMore"
          @retry="refresh"
          @search="handleSearch"
          @save-place="handleSavePlace"
        />
      </div>

      <!-- Map View -->
      <div 
        v-if="viewMode === 'split' || viewMode === 'map'"
        class="flex-1 relative"
      >
        <PlacesMap
          v-if="!isInitialLoading"
          :places="filteredPlaces"
          :view-mode="viewMode"
          :user-location="userLocation"
          :selected-place="selectedPlace"
          :hovered-place="hoveredPlace"
          @update:view-mode="viewMode = $event"
          @place-click="selectPlace"
          @bounds-change="handleBoundsChange"
          @map-ready="handleMapReady"
          ref="mapComponent"
        />
      </div>
    </div>

    <!-- Mobile Bottom Sheet (for selected place details) -->
    <div
      v-if="selectedPlace && isMobile"
      class="fixed inset-x-0 bottom-0 bg-white shadow-xl rounded-t-xl z-50 max-h-96 overflow-y-auto transform transition-transform duration-300"
      :class="selectedPlace ? 'translate-y-0' : 'translate-y-full'"
    >
      <div class="p-4">
        <!-- Handle -->
        <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
        
        <!-- Place Details -->
        <PlaceCard
          :place="selectedPlace"
          :show-more-button="true"
          @click="goToPlaceDetails"
          @more="showPlaceActions"
        />
        
        <!-- Actions -->
        <div class="flex space-x-3 mt-4">
          <button
            @click="selectedPlace = null"
            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors"
          >
            Close
          </button>
          <button
            @click="goToPlaceDetails"
            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
          >
            View Details
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div
      v-if="isInitialLoading"
      class="fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50"
    >
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mx-auto mb-4"></div>
        <p class="text-lg font-medium text-gray-900 mb-2">Loading Places Discovery</p>
        <p class="text-sm text-gray-600">Finding amazing places near you...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useMapPlaces } from '@/composables/useMapPlaces'
import { useGeolocation } from '@/composables/useGeolocation'
import { useNotification } from '@/composables/useNotification'
import PlacesMap from '@/components/maps/PlacesMap.vue'
import PlacesList from '@/components/places/PlacesList.vue'
import PlaceCard from '@/components/places/PlaceCard.vue'
import FiltersPanel from '@/components/filters/FiltersPanel.vue'

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// Router and notifications
const router = useRouter()
const { showSuccess, showError } = useNotification()

// Composables
const {
  places,
  features,
  isLoading,
  error,
  total,
  filters,
  updateBounds,
  updateFilter,
  clearFilters
} = useMapPlaces()

const {
  location: userLocation,
  isLoading: geoLoading,
  getLocation,
  coordinates
} = useGeolocation({
  fallbackToIP: true,
  showNotifications: false
})

// Local state
const searchQuery = ref('')
const viewMode = ref('split') // 'split', 'map', 'list'
const listViewMode = ref('grid') // 'grid', 'list'
const showFilters = ref(false)
const isInitialLoading = ref(true)
const mapComponent = ref(null)
const selectedPlace = ref(null)
const hoveredPlace = ref(null)
const hasMore = ref(false)
const currentSort = ref('relevance')
const categories = ref([])
const regions = ref([])
const savingDefault = ref(false)

// Computed to make places list compatible
const filteredPlaces = computed(() => places.value)

// Sort options
const sortOptions = [
  { value: 'relevance', label: 'Relevance' },
  { value: 'distance', label: 'Distance' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'newest', label: 'Newest' },
  { value: 'name', label: 'Name A-Z' }
]

// Responsive state
const isMobile = ref(false)

// Computed
const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.category) count++
  if (filters.region) count++
  if (filters.rating > 0) count++
  if (filters.priceRange) count++
  if (filters.openNow) count++
  if (filters.features?.length > 0) count += filters.features.length
  if (filters.status?.length > 0) count += filters.status.length
  return count
})

// Methods
const handleGetLocation = async () => {
  try {
    const location = await getLocation()
    if (location) {
      userLocation.value = location
      showSuccess('Location Found', `Using your current location`)
    }
  } catch (err) {
    showError('Location Error', 'Unable to determine your location')
  }
}

const saveAsDefaultLocation = async () => {
  if (!userLocation.value || savingDefault.value) return
  
  savingDefault.value = true
  try {
    // Get the current map center if available
    const mapCenter = mapComponent.value?.map?.value?.getCenter()
    const mapZoom = mapComponent.value?.map?.value?.getZoom()
    
    const locationData = {
      latitude: mapCenter?.lat || userLocation.value.latitude,
      longitude: mapCenter?.lng || userLocation.value.longitude,
      zoom: mapZoom || userLocation.value.zoom || 12,
      location_name: userLocation.value.name || 'Current Location'
    }
    
    await axios.put('/api/user/map-settings', locationData)
    showSuccess('Default Location Saved', `Your default map location has been updated to ${locationData.location_name}`)
  } catch (err) {
    console.error('Error saving default location:', err)
    showError('Save Failed', 'Unable to save default location')
  } finally {
    savingDefault.value = false
  }
}

const handleSearch = () => {
  updateFilter('search', searchQuery.value)
}

const selectPlace = (place) => {
  selectedPlace.value = place
  
  // On mobile, scroll to show the place details
  if (isMobile.value) {
    nextTick(() => {
      const bottomSheet = document.querySelector('.fixed.bottom-0')
      if (bottomSheet) {
        bottomSheet.scrollIntoView({ behavior: 'smooth' })
      }
    })
  }
}

const goToPlaceDetails = () => {
  if (selectedPlace.value) {
    const url = selectedPlace.value.canonical_url || `/places/${selectedPlace.value.slug}`
    router.push(url)
  }
}

const showPlaceActions = (place) => {
  // Could open a context menu or modal with more actions
  console.log('Show actions for:', place)
}

const handleSavePlace = (event) => {
  const { place, saved } = event
  const message = saved ? 'Place saved to your favorites' : 'Place removed from favorites'
  showSuccess(saved ? 'Saved' : 'Removed', message)
}

const handleBoundsChange = (bounds) => {
  // Get zoom level from map if available
  const zoom = mapComponent.value?.map?.value?.getZoom() || 10
  updateBounds(bounds, zoom)
}

const handleMapReady = () => {
  // Map is ready, could do additional setup here
}

const clearAllFilters = () => {
  searchQuery.value = ''
  clearFilters()
}

const applyFilters = () => {
  // Filters are applied automatically via reactivity
  showFilters.value = false
  showSuccess('Filters Applied', 'Search results updated')
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

// Add missing methods
const loadMore = () => {
  // No pagination for map view
  console.log('Load more not implemented for map view')
}

const refresh = () => {
  // Refresh is handled by bounds changes
  console.log('Refresh called')
}

// Lifecycle
onMounted(async () => {
  console.log('🗺️ MapDiscovery.vue mounted')
  
  // Check for mobile
  checkMobile()
  window.addEventListener('resize', checkMobile)

  try {
    // Load initial categories and regions
    await loadMetadata()
    
    // Try to get user's default map location
    try {
      console.log('🔍 Fetching user map settings...')
      const mapSettingsRes = await axios.get('/api/user/map-settings')
      const mapSettings = mapSettingsRes.data
      
      console.log('📍 Received map settings:', mapSettings)
      
      if (mapSettings) {
        userLocation.value = {
          latitude: parseFloat(mapSettings.latitude),
          longitude: parseFloat(mapSettings.longitude),
          name: mapSettings.location_name || 'Default Location'
        }
        
        // Store the default zoom level for later use
        if (mapSettings.zoom) {
          userLocation.value.zoom = mapSettings.zoom
        }
        
            console.log('✅ Set userLocation to:', userLocation.value)
      } else {
        console.log('⚠️ No map settings returned from API')
      }
    } catch (err) {
      console.log('⚠️ Could not get default map settings:', err)
      // Fall back to browser geolocation
      try {
        console.log('🌐 Falling back to browser geolocation...')
        const location = await getLocation()
        if (location) {
          userLocation.value = location
          console.log('✅ Set userLocation from browser to:', userLocation.value)
        }
      } catch (geoErr) {
        console.log('❌ Could not get browser location:', geoErr)
      }
    }
    
  } catch (err) {
    showError('Initialization Error', 'Failed to load initial data')
  } finally {
    isInitialLoading.value = false
    console.log('🏁 MapDiscovery initialization complete, userLocation:', userLocation.value)
  }
})

// Load categories and regions
const loadMetadata = async () => {
  try {
    // Load categories
    const categoriesRes = await axios.get('/api/categories')
    categories.value = categoriesRes.data.categories || []
    
    // Try to load regions, but don't fail if it errors
    try {
      const regionsRes = await axios.get('/api/regions/popular')
      regions.value = regionsRes.data.data || []
    } catch (regionErr) {
      console.warn('Could not load popular regions:', regionErr)
      regions.value = [] // Use empty array as fallback
    }
  } catch (err) {
    console.error('Error loading metadata:', err)
    categories.value = []
    regions.value = []
  }
}

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// Watchers
watch(currentSort, (newSort) => {
  // Sort is handled by the composable
})

watch(coordinates, (newCoords) => {
  if (newCoords && mapComponent.value) {
    mapComponent.value.flyToUserLocation()
  }
})

// Set initial search from filters
if (filters.search) {
  searchQuery.value = filters.search
}
</script>

<style scoped>
/* Ensure full height layout */
.h-screen {
  height: 100vh;
  height: 100dvh; /* Dynamic viewport height for mobile */
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  @apply bg-gray-300 rounded-full;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400;
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Focus styles for accessibility */
button:focus,
input:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Mobile specific styles */
@media (max-width: 768px) {
  .w-\[38\%\] {
    width: 100%;
  }
  
  .fixed.bottom-0 {
    transform: translateY(0);
  }
  
  .fixed.bottom-0.translate-y-full {
    transform: translateY(100%);
  }
}

/* Split view responsive adjustments */
@media (max-width: 1024px) {
  .w-\[38\%\] {
    width: 45%;
  }
}

@media (max-width: 768px) {
  .w-80 {
    @apply w-full;
  }
}
</style>