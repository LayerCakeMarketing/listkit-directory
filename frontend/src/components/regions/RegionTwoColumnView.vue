<template>
  <div class="flex flex-col h-full bg-gray-50">
    <!-- Mobile Header -->
    <div class="md:hidden bg-white border-b border-gray-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">{{ region?.name }}</h1>
        <button
          @click="toggleMobileMap"
          class="flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          :aria-label="showMobileMap ? 'Show places list' : 'Show map'"
        >
          <component :is="showMobileMap ? ListBulletIcon : MapIcon" class="w-4 h-4 mr-1" />
          {{ showMobileMap ? 'List' : 'Map' }}
        </button>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <!-- Left Column - Places List -->
      <div 
        class="flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out"
        :class="[
          'md:w-2/5',
          showMobileMap ? 'hidden md:flex' : 'w-full md:flex'
        ]"
      >
        <!-- Region Header -->
        <div class="flex-shrink-0 px-6 py-6 border-b border-gray-200">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <!-- Breadcrumbs -->
              <nav class="flex text-sm text-gray-500 mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                  <li>
                    <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                  </li>
                  <li v-for="(crumb, index) in breadcrumbs" :key="index">
                    <div class="flex items-center">
                      <ChevronRightIcon class="w-4 h-4 text-gray-400 mx-1" />
                      <router-link 
                        :to="crumb.url" 
                        :class="[
                          'text-sm',
                          index === breadcrumbs.length - 1 
                            ? 'text-gray-900 font-medium' 
                            : 'text-gray-500 hover:text-gray-700'
                        ]"
                      >
                        {{ crumb.name }}
                      </router-link>
                    </div>
                  </li>
                </ol>
              </nav>

              <!-- Region Title and Type -->
              <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 truncate">{{ region?.name }}</h1>
                <span 
                  v-if="region?.type"
                  :class="regionTypeBadgeClasses"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ formatRegionType(region.type) }}
                </span>
              </div>
              
              <!-- Region Description -->
              <p v-if="region?.description" class="mt-2 text-sm text-gray-600 line-clamp-2">
                {{ region.description }}
              </p>

              <!-- Statistics -->
              <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                <span v-if="region?.place_count">{{ region.place_count }} places</span>
                <span v-if="region?.population">{{ formatPopulation(region.population) }} residents</span>
                <span v-if="selectedPlaces.length">{{ selectedPlaces.length }} shown</span>
              </div>
            </div>
          </div>

          <!-- Park Information Card (for parks) -->
          <div v-if="isPark && region?.park_info" class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <h3 class="text-sm font-medium text-green-900 mb-2">Park Information</h3>
            <div class="space-y-1 text-xs text-green-800">
              <p v-if="region.park_info.hours">
                <strong>Hours:</strong> {{ region.park_info.hours }}
              </p>
              <p v-if="region.park_info.entrance_fee">
                <strong>Fee:</strong> {{ region.park_info.entrance_fee }}
              </p>
              <p v-if="region.park_info.activities">
                <strong>Activities:</strong> {{ region.park_info.activities.join(', ') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="flex-shrink-0 px-6 py-4 bg-gray-50 border-b border-gray-200">
          <!-- Search -->
          <div class="relative mb-4">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Search places..."
              @input="handleSearch"
            />
          </div>

          <!-- Category Filter -->
          <div class="flex flex-wrap gap-2">
            <button
              @click="selectedCategory = null"
              :class="[
                'px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
                !selectedCategory 
                  ? 'bg-blue-100 text-blue-800 ring-2 ring-blue-500 ring-opacity-30' 
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              All
            </button>
            <button
              v-for="category in availableCategories"
              :key="category.id"
              @click="selectedCategory = category"
              :class="[
                'px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
                selectedCategory?.id === category.id
                  ? 'bg-blue-100 text-blue-800 ring-2 ring-blue-500 ring-opacity-30'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              {{ category.name }} ({{ category.count }})
            </button>
          </div>
        </div>

        <!-- Places List -->
        <div class="flex-1 overflow-y-auto" @scroll="handleScroll">
          <!-- Loading State -->
          <div v-if="isLoading" class="p-6">
            <div v-for="n in 3" :key="n" class="mb-4 animate-pulse">
              <div class="flex space-x-3">
                <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                  <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="p-6 text-center">
            <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Places</h3>
            <p class="text-sm text-gray-600 mb-4">{{ error }}</p>
            <button
              @click="loadPlaces"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Retry
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredPlaces.length === 0" class="p-6 text-center">
            <MapPinIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Places Found</h3>
            <p class="text-sm text-gray-600">
              {{ searchQuery || selectedCategory 
                ? 'Try adjusting your search or filter criteria.' 
                : `No places have been added to ${region?.name} yet.` 
              }}
            </p>
          </div>

          <!-- Places List -->
          <div v-else class="divide-y divide-gray-200">
            <PlaceCard
              v-for="place in filteredPlaces"
              :key="place.id"
              :place="place"
              :is-selected="selectedPlace?.id === place.id"
              :is-hovered="hoveredPlace?.id === place.id"
              :distance="place.distance"
              size="normal"
              @click="selectPlace(place)"
              @hover="(p) => hoveredPlace = p"
            />
          </div>

          <!-- Load More -->
          <div v-if="hasNextPage && !isLoading" class="p-4 text-center">
            <button
              @click="loadMore"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Load More
            </button>
          </div>
        </div>
      </div>

      <!-- Right Column - Map -->
      <div 
        class="flex flex-col bg-gray-100 transition-all duration-300 ease-in-out relative"
        :class="[
          'md:w-3/5',
          showMobileMap ? 'w-full' : 'hidden md:flex'
        ]"
      >
        <!-- Map Header (Mobile Only) -->
        <div v-if="showMobileMap" class="md:hidden bg-white border-b border-gray-200 px-4 py-2 sticky top-0 z-20">
          <h2 class="text-sm font-medium text-gray-900">{{ region?.name }} - Map View</h2>
        </div>

        <!-- Map Component -->
        <div class="flex-1 relative" style="margin-top: 64px;">
          <PlacesMap
            :places="filteredPlaces"
            :region-bounds="regionBounds"
            :selected-place="selectedPlace"
            :hovered-place="hoveredPlace"
            :user-location="userLocation"
            view-mode="map"
            @place-click="selectPlace"
            @bounds-change="handleMapBoundsChange"
            @map-ready="handleMapReady"
            class="h-full w-full"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'
import {
  MagnifyingGlassIcon,
  MapIcon,
  ListBulletIcon,
  ChevronRightIcon,
  MapPinIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import PlacesMap from '@/components/maps/PlacesMap.vue'
import PlaceCard from '@/components/places/PlaceCard.vue'

// Props
const props = defineProps({
  regionId: {
    type: Number,
    default: undefined
  }
})

// Emits
const emit = defineEmits(['placeSelected', 'regionLoaded'])

// Composables
const route = useRoute()
const router = useRouter()

// Reactive state
const region = ref(null)
const places = ref([])
const selectedPlace = ref(null)
const hoveredPlace = ref(null)
const userLocation = ref(null)
const searchQuery = ref('')
const selectedCategory = ref(null)
const isLoading = ref(true)
const error = ref(null)
const hasNextPage = ref(false)
const currentPage = ref(1)
const showMobileMap = ref(false)

// Computed properties
const regionTypeBadgeClasses = computed(() => {
  switch (region.value?.type) {
    case 'national_park':
      return 'bg-green-100 text-green-800'
    case 'state_park':
      return 'bg-emerald-100 text-emerald-800'
    case 'neighborhood':
      return 'bg-blue-100 text-blue-800'
    case 'city':
      return 'bg-purple-100 text-purple-800'
    case 'state':
      return 'bg-indigo-100 text-indigo-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const isPark = computed(() => {
  return region.value?.type === 'national_park' || region.value?.type === 'state_park'
})

const breadcrumbs = computed(() => {
  if (!region.value) return []
  
  // This would be populated based on the region's hierarchy
  // For now, returning empty array as we'd need parent region data
  return []
})

const availableCategories = computed(() => {
  const categoryMap = new Map()
  
  places.value.forEach(place => {
    if (place.category) {
      const existing = categoryMap.get(place.category.id)
      if (existing) {
        existing.count++
      } else {
        categoryMap.set(place.category.id, {
          ...place.category,
          count: 1
        })
      }
    }
  })
  
  return Array.from(categoryMap.values()).sort((a, b) => b.count - a.count)
})

const filteredPlaces = computed(() => {
  let filtered = places.value
  
  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(place => 
      place.name.toLowerCase().includes(query) ||
      place.description?.toLowerCase().includes(query) ||
      place.address?.toLowerCase().includes(query) ||
      place.category?.name.toLowerCase().includes(query)
    )
  }
  
  // Filter by category
  if (selectedCategory.value) {
    filtered = filtered.filter(place => place.category?.id === selectedCategory.value?.id)
  }
  
  return filtered
})

const selectedPlaces = computed(() => {
  return filteredPlaces.value
})

const regionBounds = computed(() => {
  return region.value?.bounds || null
})

// Methods
const formatRegionType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatPopulation = (population) => {
  if (population >= 1000000) {
    return (population / 1000000).toFixed(1) + 'M'
  } else if (population >= 1000) {
    return (population / 1000).toFixed(0) + 'K'
  }
  return population.toString()
}

const loadRegion = async (regionId) => {
  const targetId = regionId || props.regionId
  if (!targetId) {
    error.value = 'Region ID is required'
    return
  }
  
  try {
    isLoading.value = true
    error.value = null
    
    // API call to load region
    const response = await fetch(`/api/region-map/${targetId}/two-column`)
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    region.value = data.region
    places.value = data.places || []
    hasNextPage.value = data.has_next_page || false
    currentPage.value = 1
    
    emit('regionLoaded', region.value)
    
    // Set initial map view if region has bounds
    if (region.value.bounds) {
      // Map will automatically fit to bounds
    }
  } catch (err) {
    console.error('Error loading region:', err)
    error.value = err instanceof Error ? err.message : 'Failed to load region data'
  } finally {
    isLoading.value = false
  }
}

const loadPlaces = async (page = 1, append = false) => {
  if (!region.value) return
  
  try {
    if (!append) {
      isLoading.value = true
    }
    
    const params = new URLSearchParams({
      page: page.toString(),
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedCategory.value && { category: selectedCategory.value.id.toString() })
    })
    
    const response = await fetch(`/api/region-map/${region.value.id}/places?${params}`)
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    
    if (append) {
      places.value = [...places.value, ...data.places]
    } else {
      places.value = data.places || []
    }
    
    hasNextPage.value = data.has_next_page || false
    currentPage.value = page
  } catch (err) {
    console.error('Error loading places:', err)
    if (!append) {
      error.value = err instanceof Error ? err.message : 'Failed to load places'
    }
  } finally {
    isLoading.value = false
  }
}

const loadMore = async () => {
  await loadPlaces(currentPage.value + 1, true)
}

const selectPlace = (place) => {
  selectedPlace.value = selectedPlace.value?.id === place.id ? null : place
  emit('placeSelected', selectedPlace.value)
}

const handleSearch = debounce(async () => {
  await loadPlaces(1, false)
}, 300)

const handleScroll = (event) => {
  const target = event.target
  const { scrollTop, scrollHeight, clientHeight } = target
  
  // Load more when scrolled to bottom
  if (scrollHeight - scrollTop <= clientHeight + 100 && hasNextPage.value && !isLoading.value) {
    loadMore()
  }
}

const handleMapBoundsChange = (bounds) => {
  // Could implement viewport-based filtering here
  console.log('Map bounds changed:', bounds)
}

const handleMapReady = () => {
  console.log('Map is ready')
}

const toggleMobileMap = () => {
  showMobileMap.value = !showMobileMap.value
}

// Watchers
watch(() => selectedCategory.value, async () => {
  currentPage.value = 1
  await loadPlaces(1, false)
})

watch(() => props.regionId, async (newId) => {
  if (newId) {
    await loadRegion(newId)
  }
})

// Lifecycle
onMounted(async () => {
  // Try to get user's location
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
  
  // Load region data
  const regionId = props.regionId || parseInt(route.params.regionId)
  if (regionId) {
    await loadRegion(regionId)
  }
})

// Expose methods for parent component
defineExpose({
  loadRegion,
  selectPlace,
  selectedPlace: computed(() => selectedPlace.value),
  filteredPlaces: computed(() => filteredPlaces.value)
})
</script>

<style scoped>
/* Custom scrollbar for places list */
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

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus styles for accessibility */
button:focus-visible,
input:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .sticky {
    position: sticky;
    top: 0;
  }
}
</style>