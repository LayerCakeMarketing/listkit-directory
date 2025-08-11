<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header with photo grid -->
    <div class="relative">
      <!-- Photo Grid Header -->
      <div v-if="region && photos.length > 0" class="relative h-80 overflow-hidden">
        <div class="grid grid-cols-3 gap-1 h-full">
          <div class="col-span-2 relative">
            <img 
              :src="photos[0]" 
              :alt="region.name"
              class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
              @click="openPhotoGallery(0)"
            >
          </div>
          <div class="grid grid-rows-2 gap-1">
            <div v-for="(photo, index) in photos.slice(1, 3)" :key="index" class="relative">
              <img 
                :src="photo" 
                :alt="`${region.name} photo ${index + 2}`"
                class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
                @click="openPhotoGallery(index + 1)"
              >
            </div>
          </div>
        </div>
        
        <!-- View All Photos Button -->
        <button 
          v-if="photos.length > 3"
          @click="openPhotoGallery()"
          class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-white transition-colors flex items-center gap-2"
        >
          <CameraIcon class="w-4 h-4" />
          View all {{ photos.length }} photos
        </button>
      </div>

      <!-- Region Info Bar -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-4">
          <div class="flex items-start justify-between">
            <div>
              <!-- Breadcrumbs -->
              <nav class="flex items-center text-sm text-gray-500 mb-2">
                <router-link to="/" class="hover:text-gray-700">Home</router-link>
                <ChevronRightIcon class="w-4 h-4 mx-1" />
                <router-link to="/local" class="hover:text-gray-700">Explore</router-link>
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                  <ChevronRightIcon class="w-4 h-4 mx-1" />
                  <router-link :to="crumb.url" class="hover:text-gray-700">
                    {{ crumb.name }}
                  </router-link>
                </template>
              </nav>
              
              <!-- Title and Type Badge -->
              <div class="flex items-center gap-3 mb-2">
                <h1 class="text-3xl font-bold text-gray-900">{{ region?.name }}</h1>
                <span 
                  v-if="regionType"
                  :class="regionTypeBadgeClass"
                  class="px-3 py-1 rounded-full text-xs font-medium"
                >
                  {{ regionTypeLabel }}
                </span>
              </div>
              
              <!-- Stats Row -->
              <div class="flex items-center gap-6 text-sm">
                <!-- Rating -->
                <div class="flex items-center gap-1">
                  <div class="flex">
                    <StarIcon 
                      v-for="i in 5" 
                      :key="i"
                      :class="i <= Math.floor(averageRating) ? 'text-green-500' : 'text-gray-300'"
                      class="w-4 h-4 fill-current"
                    />
                  </div>
                  <span class="font-medium text-gray-900">{{ averageRating.toFixed(1) }}</span>
                  <span class="text-gray-500">({{ totalReviews }} reviews)</span>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center gap-1 text-gray-600">
                  <MapPinIcon class="w-4 h-4" />
                  <span>{{ totalPlaces }} places</span>
                </div>
                
                <div v-if="region?.area_acres" class="flex items-center gap-1 text-gray-600">
                  <span>{{ formatArea(region.area_acres) }}</span>
                </div>
                
                <div v-if="region?.difficulty_level" class="flex items-center gap-1">
                  <span :class="difficultyClass">{{ region.difficulty_level }}</span>
                </div>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
              <SaveButton
                v-if="authStore.isAuthenticated && region?.id"
                item-type="region"
                :item-id="region.id"
                :initial-saved="region.is_saved || false"
                button-style="outline"
              />
              
              <button
                @click="shareRegion"
                class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors"
              >
                <ArrowUpOnSquareIcon class="w-5 h-5 text-gray-600" />
              </button>
              
              <button
                @click="printMap"
                class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors"
              >
                <PrinterIcon class="w-5 h-5 text-gray-600" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Two Column Layout -->
    <div class="flex h-[calc(100vh-24rem)]">
      <!-- Left Column - Places List (40%) -->
      <div class="w-2/5 bg-white border-r border-gray-200 flex flex-col">
        <!-- Filters Bar -->
        <div class="p-4 border-b border-gray-200">
          <!-- Search -->
          <div class="relative mb-3">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search places in this area..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
              @input="debouncedSearch"
            >
          </div>
          
          <!-- Filter Pills -->
          <div class="flex items-center gap-2 mb-3">
            <span class="text-sm text-gray-600">Filter:</span>
            <button
              v-for="filter in filters"
              :key="filter.id"
              @click="toggleFilter(filter)"
              :class="[
                'px-3 py-1.5 rounded-full text-xs font-medium transition-colors',
                activeFilters.includes(filter.id)
                  ? 'bg-green-100 text-green-800 border border-green-300'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              {{ filter.label }}
              <span v-if="filter.count" class="ml-1">({{ filter.count }})</span>
            </button>
          </div>
          
          <!-- Sort Dropdown -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">
              Showing {{ filteredPlaces.length }} of {{ totalPlaces }} places
            </span>
            <select
              v-model="sortBy"
              class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              <option value="popular">Most Popular</option>
              <option value="rating">Highest Rated</option>
              <option value="reviews">Most Reviews</option>
              <option value="distance" v-if="userLocation">Nearest</option>
              <option value="difficulty" v-if="isPark">Difficulty</option>
              <option value="length" v-if="isPark">Trail Length</option>
            </select>
          </div>
        </div>
        
        <!-- Scrollable Places List -->
        <div class="flex-1 overflow-y-auto">
          <div class="divide-y divide-gray-200">
            <!-- Place Card -->
            <div
              v-for="(place, index) in paginatedPlaces"
              :key="place.id"
              @click="selectPlace(place)"
              @mouseenter="hoveredPlace = place"
              @mouseleave="hoveredPlace = null"
              :class="[
                'p-4 cursor-pointer transition-colors',
                selectedPlace?.id === place.id ? 'bg-green-50' : 'hover:bg-gray-50'
              ]"
            >
              <div class="flex gap-3">
                <!-- Rank Number -->
                <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
                  {{ index + 1 }}
                </div>
                
                <!-- Place Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between mb-1">
                    <h3 class="font-medium text-gray-900 hover:text-green-600 transition-colors">
                      {{ place.title || place.name }}
                    </h3>
                    <SaveButton
                      v-if="authStore.isAuthenticated"
                      item-type="place"
                      :item-id="place.id"
                      :initial-saved="place.is_saved || false"
                      size="sm"
                    />
                  </div>
                  
                  <!-- Rating and Reviews -->
                  <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center gap-1">
                      <div class="flex">
                        <StarIcon 
                          v-for="i in 5" 
                          :key="i"
                          :class="i <= Math.floor(place.rating || 0) ? 'text-green-500' : 'text-gray-300'"
                          class="w-3 h-3 fill-current"
                        />
                      </div>
                      <span class="text-xs text-gray-600">{{ place.rating?.toFixed(1) || '0.0' }}</span>
                      <span class="text-xs text-gray-500">({{ place.review_count || 0 }})</span>
                    </div>
                    
                    <!-- Difficulty Badge (for trails) -->
                    <span 
                      v-if="place.difficulty"
                      :class="getDifficultyClass(place.difficulty)"
                      class="px-2 py-0.5 rounded text-xs font-medium"
                    >
                      {{ place.difficulty }}
                    </span>
                  </div>
                  
                  <!-- Trail/Place Stats -->
                  <div class="flex items-center gap-4 text-xs text-gray-600 mb-2">
                    <span v-if="place.length">Length: {{ formatDistance(place.length) }}</span>
                    <span v-if="place.elevation_gain">Elev. gain: {{ place.elevation_gain }}ft</span>
                    <span v-if="place.duration">Est. {{ place.duration }}</span>
                    <span v-if="place.category">{{ place.category.name }}</span>
                  </div>
                  
                  <!-- Description Preview -->
                  <p class="text-sm text-gray-600 line-clamp-2">
                    {{ place.description }}
                  </p>
                  
                  <!-- Photo Thumbnails -->
                  <div v-if="place.photos && place.photos.length > 0" class="flex gap-1 mt-2">
                    <img
                      v-for="(photo, idx) in place.photos.slice(0, 3)"
                      :key="idx"
                      :src="photo.thumbnail"
                      :alt="`${place.title} photo ${idx + 1}`"
                      class="w-16 h-16 object-cover rounded"
                    >
                    <div 
                      v-if="place.photos.length > 3"
                      class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-600 font-medium"
                    >
                      +{{ place.photos.length - 3 }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Load More -->
          <div v-if="hasMorePages" class="p-4 text-center border-t border-gray-200">
            <button
              @click="loadMore"
              :disabled="loadingMore"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
            >
              {{ loadingMore ? 'Loading...' : 'Load More Places' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Right Column - Map (60%) -->
      <div class="w-3/5 relative">
        <!-- Map Controls Overlay -->
        <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
          <!-- Map Style Switcher -->
          <div class="bg-white rounded-lg shadow-lg p-1 flex">
            <button
              @click="mapStyle = 'streets'"
              :class="mapStyle === 'streets' ? 'bg-gray-200' : 'hover:bg-gray-100'"
              class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
            >
              Map
            </button>
            <button
              @click="mapStyle = 'satellite'"
              :class="mapStyle === 'satellite' ? 'bg-gray-200' : 'hover:bg-gray-100'"
              class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
            >
              Satellite
            </button>
            <button
              @click="mapStyle = 'terrain'"
              :class="mapStyle === 'terrain' ? 'bg-gray-200' : 'hover:bg-gray-100'"
              class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
            >
              Terrain
            </button>
          </div>
          
          <!-- Zoom Controls -->
          <div class="bg-white rounded-lg shadow-lg p-1">
            <button
              @click="zoomIn"
              class="p-2 hover:bg-gray-100 rounded transition-colors"
            >
              <PlusIcon class="w-4 h-4" />
            </button>
            <button
              @click="zoomOut"
              class="p-2 hover:bg-gray-100 rounded transition-colors"
            >
              <MinusIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        
        <!-- Legend -->
        <div class="absolute bottom-4 left-4 z-10 bg-white rounded-lg shadow-lg p-3 text-xs">
          <div class="font-medium text-gray-700 mb-2">Legend</div>
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              <span>Trail/Place</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span>Your Location</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
              <span>Selected</span>
            </div>
          </div>
        </div>
        
        <!-- Mapbox Map -->
        <div id="region-explorer-map" class="w-full h-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'
import { useMapbox } from '@/composables/useMapbox'
import {
  ChevronRightIcon,
  CameraIcon,
  StarIcon,
  MapPinIcon,
  ArrowUpOnSquareIcon,
  PrinterIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'
import SaveButton from '@/components/SaveButton.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  regionId: {
    type: [Number, String],
    required: true
  }
})

// Composables
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showToast } = useToast()

// Initialize Mapbox composable
const {
  map,
  initializeMap,
  addMarker,
  removeMarker,
  clearMarkers,
  flyTo,
  fitBounds,
  updateMarkers,
  changeStyle
} = useMapbox()

// State
const region = ref(null)
const places = ref([])
const filteredPlaces = ref([])
const selectedPlace = ref(null)
const hoveredPlace = ref(null)
const photos = ref([])
const breadcrumbs = ref([])
const searchQuery = ref('')
const activeFilters = ref([])
const sortBy = ref('popular')
const mapStyle = ref('streets')
const userLocation = ref(null)
const currentPage = ref(1)
const itemsPerPage = 20
const loadingMore = ref(false)
const isLoading = ref(true)
const totalPlaces = ref(0)
const averageRating = ref(4.5)
const totalReviews = ref(0)

// Computed
const regionType = computed(() => {
  if (!region.value) return null
  return region.value.park_designation || region.value.type
})

const regionTypeLabel = computed(() => {
  const type = regionType.value
  if (!type) return ''
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
})

const regionTypeBadgeClass = computed(() => {
  const type = regionType.value
  if (type === 'national_park') return 'bg-purple-100 text-purple-800'
  if (type === 'state_park') return 'bg-green-100 text-green-800'
  if (type === 'neighborhood') return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
})

const isPark = computed(() => {
  return regionType.value?.includes('park')
})

const filters = computed(() => {
  return [
    { id: 'easy', label: 'Easy', count: 12 },
    { id: 'moderate', label: 'Moderate', count: 28 },
    { id: 'hard', label: 'Hard', count: 15 },
    { id: 'dog-friendly', label: 'Dog friendly', count: 31 },
    { id: 'kid-friendly', label: 'Kid friendly', count: 22 },
    { id: 'wheelchair', label: 'Wheelchair accessible', count: 8 }
  ]
})

const difficultyClass = computed(() => {
  const level = region.value?.difficulty_level?.toLowerCase()
  if (level === 'easy') return 'text-green-600 font-medium'
  if (level === 'moderate') return 'text-yellow-600 font-medium'
  if (level === 'hard') return 'text-red-600 font-medium'
  return 'text-gray-600'
})

const paginatedPlaces = computed(() => {
  const start = 0
  const end = currentPage.value * itemsPerPage
  return filteredPlaces.value.slice(start, end)
})

const hasMorePages = computed(() => {
  return paginatedPlaces.value.length < filteredPlaces.value.length
})

// Methods
const loadRegionData = async () => {
  try {
    isLoading.value = true
    
    // Fetch region data with places
    const response = await fetch(`/api/region-map/${props.regionId}/two-column`)
    if (!response.ok) throw new Error('Failed to load region data')
    
    const data = await response.json()
    region.value = data.region
    places.value = data.places || []
    totalPlaces.value = data.pagination?.total || data.places?.length || 0
    
    // Set some sample data for demo
    if (data.region) {
      // Sample photos (you'd get these from the API)
      photos.value = [
        '/api/placeholder/800/600',
        '/api/placeholder/400/300',
        '/api/placeholder/400/300',
        '/api/placeholder/400/300'
      ]
      
      // Sample breadcrumbs
      if (data.region.parent) {
        breadcrumbs.value = [
          { name: data.region.parent.name, url: `/regions/${data.region.parent.slug}` }
        ]
      }
      
      // Calculate average rating from places
      if (places.value.length > 0) {
        const ratings = places.value.filter(p => p.rating).map(p => p.rating)
        if (ratings.length > 0) {
          averageRating.value = ratings.reduce((a, b) => a + b, 0) / ratings.length
        }
        totalReviews.value = places.value.reduce((sum, p) => sum + (p.review_count || 0), 0)
      }
    }
    
    // Apply initial filtering/sorting
    filterAndSortPlaces()
    
    // Initialize map after data is loaded
    await initMap()
    
  } catch (error) {
    console.error('Error loading region:', error)
    showToast('Failed to load region data', 'error')
  } finally {
    isLoading.value = false
  }
}

const filterAndSortPlaces = () => {
  let filtered = [...places.value]
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(place => 
      place.title?.toLowerCase().includes(query) ||
      place.name?.toLowerCase().includes(query) ||
      place.description?.toLowerCase().includes(query)
    )
  }
  
  // Apply difficulty filters (example)
  if (activeFilters.value.length > 0) {
    // This would be implemented based on actual place attributes
    console.log('Applying filters:', activeFilters.value)
  }
  
  // Sort places
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'rating':
        return (b.rating || 0) - (a.rating || 0)
      case 'reviews':
        return (b.review_count || 0) - (a.review_count || 0)
      case 'distance':
        if (userLocation.value) {
          const distA = calculateDistance(userLocation.value, { lat: a.latitude, lng: a.longitude })
          const distB = calculateDistance(userLocation.value, { lat: b.latitude, lng: b.longitude })
          return distA - distB
        }
        return 0
      case 'difficulty':
        // Would need difficulty levels in data
        return 0
      case 'length':
        return (b.length || 0) - (a.length || 0)
      case 'popular':
      default:
        return (b.review_count || 0) - (a.review_count || 0)
    }
  })
  
  filteredPlaces.value = filtered
}

const initMap = async () => {
  if (!region.value) return
  
  await nextTick() // Ensure DOM is ready
  
  // Initialize map using the composable
  const mapContainer = document.getElementById('region-explorer-map')
  if (!mapContainer) {
    console.error('Map container not found')
    return
  }
  
  const mapOptions = {
    style: 'mapbox://styles/mapbox/outdoors-v12',
    center: [region.value.longitude || -122.4194, region.value.latitude || 37.7749],
    zoom: 11
  }
  
  const initialized = await initializeMap(mapContainer, mapOptions)
  
  if (initialized && map.value) {
    // Wait for map to load
    map.value.on('load', () => {
      // Add place markers
      addPlaceMarkers()
      
      // Fit bounds if available
      if (region.value.bounds) {
        fitBounds(region.value.bounds, { padding: 50 })
      }
    })
  }
}

const addPlaceMarkers = () => {
  if (!map.value || !places.value.length) return
  
  // Clear existing markers first
  clearMarkers()
  
  // Use mapboxgl from window
  const mapboxgl = window.mapboxgl
  if (!mapboxgl) {
    console.error('Mapbox GL JS not loaded')
    return
  }
  
  // Add markers for each place
  places.value.forEach((place, index) => {
    if (!place.latitude || !place.longitude) return
    
    // Create marker element
    const el = document.createElement('div')
    el.className = 'marker'
    el.id = `place-${place.id}`
    el.innerHTML = `<div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-lg transition-transform">${index + 1}</div>`
    
    // Create popup
    const popupContent = `
      <div class="p-2">
        <h3 class="font-medium">${place.title || place.name}</h3>
        <p class="text-sm text-gray-600">${place.category?.name || ''}</p>
        ${place.rating ? `<div class="text-sm mt-1">★ ${place.rating.toFixed(1)}</div>` : ''}
      </div>
    `
    
    // Add marker using composable method
    const markerId = `place-${place.id}`
    const markerOptions = {
      element: el,
      popup: popupContent,
      popupOptions: { offset: 25 }
    }
    
    addMarker(
      markerId,
      [place.longitude, place.latitude],
      markerOptions
    )
    
    // Handle marker click
    el.addEventListener('click', () => {
      selectPlace(place)
    })
    
    // Handle hover
    el.addEventListener('mouseenter', () => {
      hoveredPlace.value = place
      el.querySelector('div').classList.add('scale-110')
    })
    
    el.addEventListener('mouseleave', () => {
      hoveredPlace.value = null
      el.querySelector('div').classList.remove('scale-110')
    })
  })
}

const selectPlace = (place) => {
  selectedPlace.value = place
  
  // Pan map to place using composable
  if (place.latitude && place.longitude) {
    flyTo([place.longitude, place.latitude], 14)
  }
  
  // Update marker styles
  document.querySelectorAll('.marker > div').forEach(el => {
    el.classList.remove('bg-orange-500')
    el.classList.add('bg-green-500')
  })
  
  // Highlight selected marker
  const selectedMarker = document.querySelector(`#place-${place.id}`)
  if (selectedMarker) {
    const markerDiv = selectedMarker.querySelector('div')
    if (markerDiv) {
      markerDiv.classList.remove('bg-green-500')
      markerDiv.classList.add('bg-orange-500')
    }
  }
}

const toggleFilter = (filter) => {
  const index = activeFilters.value.indexOf(filter.id)
  if (index > -1) {
    activeFilters.value.splice(index, 1)
  } else {
    activeFilters.value.push(filter.id)
  }
  filterAndSortPlaces()
}

const loadMore = async () => {
  loadingMore.value = true
  currentPage.value++
  // In a real app, you'd fetch more data from the API
  setTimeout(() => {
    loadingMore.value = false
  }, 500)
}

const debouncedSearch = debounce(() => {
  filterAndSortPlaces()
}, 300)

const shareRegion = () => {
  if (navigator.share) {
    navigator.share({
      title: region.value?.name,
      text: `Check out ${region.value?.name}`,
      url: window.location.href
    })
  } else {
    navigator.clipboard.writeText(window.location.href)
    showToast('Link copied to clipboard!', 'success')
  }
}

const printMap = () => {
  window.print()
}

const openPhotoGallery = (startIndex = 0) => {
  // Would open a photo gallery modal
  console.log('Open photo gallery at index:', startIndex)
}

const zoomIn = () => {
  if (map.value) map.value.zoomIn()
}

const zoomOut = () => {
  if (map.value) map.value.zoomOut()
}

const formatArea = (acres) => {
  if (acres > 1000) {
    return `${(acres / 1000).toFixed(1)}k acres`
  }
  return `${acres} acres`
}

const formatDistance = (miles) => {
  return `${miles.toFixed(1)} mi`
}

const getDifficultyClass = (difficulty) => {
  const level = difficulty?.toLowerCase()
  if (level === 'easy') return 'bg-green-100 text-green-800'
  if (level === 'moderate') return 'bg-yellow-100 text-yellow-800'
  if (level === 'hard') return 'bg-red-100 text-red-800'
  return 'bg-gray-100 text-gray-800'
}

const calculateDistance = (point1, point2) => {
  // Haversine formula for distance calculation
  const R = 3959 // Earth's radius in miles
  const dLat = (point2.lat - point1.lat) * Math.PI / 180
  const dLon = (point2.lng - point1.lng) * Math.PI / 180
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(point1.lat * Math.PI / 180) * Math.cos(point2.lat * Math.PI / 180) *
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
  return R * c
}

// Watchers
watch(mapStyle, (newStyle) => {
  if (!map) return
  
  const styles = {
    streets: 'mapbox://styles/mapbox/streets-v12',
    satellite: 'mapbox://styles/mapbox/satellite-streets-v12',
    terrain: 'mapbox://styles/mapbox/outdoors-v12'
  }
  
  map.setStyle(styles[newStyle])
})

watch(sortBy, () => {
  filterAndSortPlaces()
})

// Lifecycle
onMounted(() => {
  loadRegionData()
  
  // Get user location
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        userLocation.value = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
      },
      (error) => {
        console.warn('Could not get user location:', error)
      }
    )
  }
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Custom marker styles */
:deep(.marker) {
  cursor: pointer;
  transition: transform 0.2s;
}

:deep(.marker:hover) {
  z-index: 10;
}

/* Map print styles */
@media print {
  .no-print {
    display: none !important;
  }
}

/* Scrollbar styles */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #9ca3af;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>