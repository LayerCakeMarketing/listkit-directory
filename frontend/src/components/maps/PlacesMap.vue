<template>
  <div class="relative w-full h-full bg-gray-100">
    <!-- Map Container -->
    <div 
      ref="mapContainer" 
      class="w-full h-full"
      :class="{ 'opacity-50': isLoading }"
    ></div>

    <!-- Loading Overlay -->
    <div 
      v-if="isLoading" 
      class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10"
    >
      <div class="flex flex-col items-center space-y-4">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
        <p class="text-sm text-gray-600">Loading map...</p>
      </div>
    </div>

    <!-- Error State -->
    <div 
      v-if="error && !isLoading" 
      class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 z-10"
    >
      <div class="text-center p-6 max-w-md">
        <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.768 0L3.046 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Map Error</h3>
        <p class="text-sm text-gray-600 mb-4">{{ error }}</p>
        <button 
          @click="initializeMap"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Map Controls Overlay -->
    <div class="absolute top-4 left-4 z-20 space-y-2">
      <!-- View Mode Toggle -->
      <div class="bg-white rounded-lg shadow-lg p-1 flex">
        <button
          @click="$emit('update:viewMode', 'map')"
          :class="[
            'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
            viewMode === 'map' 
              ? 'bg-blue-500 text-white' 
              : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'
          ]"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7" />
          </svg>
          Map
        </button>
        <button
          @click="$emit('update:viewMode', 'split')"
          :class="[
            'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
            viewMode === 'split' 
              ? 'bg-blue-500 text-white' 
              : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'
          ]"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2z" />
          </svg>
          Split
        </button>
      </div>

      <!-- Fit Bounds Button -->
      <button
        v-if="places.length > 0"
        @click="fitToPlaces"
        class="bg-white hover:bg-gray-50 text-gray-700 p-2 rounded-lg shadow-lg transition-colors"
        title="Fit all places in view"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
        </svg>
      </button>

      <!-- Search Places on Map -->
      <button
        @click="searchInViewport"
        class="bg-white hover:bg-gray-50 text-gray-700 p-2 rounded-lg shadow-lg transition-colors"
        title="Search places in current view"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
    </div>

    <!-- Legend (bottom left) -->
    <div 
      v-if="isLoaded && places.length > 0"
      class="absolute bottom-4 left-4 bg-white rounded-lg shadow-lg p-3 z-20"
    >
      <h4 class="text-sm font-medium text-gray-900 mb-2">Legend</h4>
      <div class="space-y-1 text-xs">
        <div class="flex items-center">
          <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
          <span class="text-gray-600">Featured Places</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
          <span class="text-gray-600">Verified Places</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 rounded-full bg-gray-500 mr-2"></div>
          <span class="text-gray-600">Standard Places</span>
        </div>
      </div>
    </div>

    <!-- Place Count (bottom right) -->
    <div 
      v-if="isLoaded"
      class="absolute bottom-4 right-4 bg-white rounded-lg shadow-lg px-3 py-2 z-20"
    >
      <span class="text-sm text-gray-600">
        {{ places.length }} place{{ places.length !== 1 ? 's' : '' }} shown
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useMapbox } from '@/composables/useMapbox'

// Props
const props = defineProps({
  places: {
    type: Array,
    default: () => []
  },
  viewMode: {
    type: String,
    default: 'split'
  },
  userLocation: {
    type: Object,
    default: null
  },
  selectedPlace: {
    type: Object,
    default: null
  },
  hoveredPlace: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits([
  'update:viewMode',
  'place-click',
  'bounds-change',
  'map-ready'
])

// Refs
const mapContainer = ref(null)

// Mapbox composable
const {
  map,
  isLoaded,
  isLoading,
  error,
  initializeMap: initMap,
  updatePlaces,
  fitBounds,
  flyTo,
  getBounds,
  showPlacePopup,
  closeAllPopups
} = useMapbox({
  center: [-98.5795, 39.8283], // Center of US
  zoom: 4,
  onMarkerClick: async (place) => {
    // Emit click event to parent
    emit('place-click', place)
    
    // Return place data for popup (could fetch more details here)
    return {
      ...place,
      name: place.name || place.title
    }
  }
})

// Methods
const initializeMap = async () => {
  console.log('🗺️ PlacesMap.vue initializeMap called')
  console.log('📍 Props userLocation:', props.userLocation)
  
  if (!mapContainer.value) {
    console.log('❌ No map container found')
    return
  }
  
  // Set default center to San Francisco if no user location
  const defaultCenter = props.userLocation && props.userLocation.longitude && props.userLocation.latitude
    ? [props.userLocation.longitude, props.userLocation.latitude]
    : [-122.4194, 37.7749] // San Francisco
  
  // Use zoom from userLocation if available, otherwise default to 12
  const defaultZoom = props.userLocation?.zoom || 12
  
  console.log('🎯 Map center will be:', defaultCenter)
  console.log('🔍 Map zoom will be:', defaultZoom)
  
  const success = await initMap(mapContainer.value, {
    center: defaultCenter,
    zoom: defaultZoom
  })
  
  if (success) {
    console.log('✅ Map initialized successfully')
    emit('map-ready')
    
    // Setup map event listeners
    map.value.on('moveend', handleMapMove)
    map.value.on('zoomend', handleMapMove)
    
    // If we have places, fit to them after map loads
    if (props.places && props.places.length > 0) {
      console.log('📍 Fitting map to', props.places.length, 'places after 500ms delay')
      setTimeout(() => {
        fitToPlaces()
      }, 500)
    }
  } else {
    console.log('❌ Map initialization failed')
  }
}

const handleMapMove = () => {
  const bounds = getBounds()
  if (bounds) {
    emit('bounds-change', bounds)
  }
}

const fitToPlaces = () => {
  if (props.places.length > 0) {
    fitBounds(props.places, 50)
  }
}

const searchInViewport = () => {
  const bounds = getBounds()
  if (bounds) {
    emit('bounds-change', bounds)
  }
}

const flyToPlace = (place) => {
  if (place && place.longitude && place.latitude) {
    flyTo(
      parseFloat(place.longitude),
      parseFloat(place.latitude),
      15
    )
  }
}

const flyToUserLocation = () => {
  if (props.userLocation && props.userLocation.longitude && props.userLocation.latitude) {
    flyTo(
      props.userLocation.longitude,
      props.userLocation.latitude,
      12
    )
  }
}

// Watchers
watch(() => props.places, (newPlaces) => {
  if (isLoaded.value && newPlaces) {
    updatePlaces(newPlaces)
  }
}, { deep: true })

watch(() => props.selectedPlace, (newPlace, oldPlace) => {
  if (newPlace && newPlace !== oldPlace) {
    flyToPlace(newPlace)
    
    // Show popup for selected place
    if (newPlace.longitude && newPlace.latitude) {
      nextTick(() => {
        showPlacePopup(newPlace, {
          lng: parseFloat(newPlace.longitude),
          lat: parseFloat(newPlace.latitude)
        })
      })
    }
  } else if (!newPlace) {
    closeAllPopups()
  }
})

watch(() => props.hoveredPlace, (newPlace) => {
  // Could add hover effects here
  if (newPlace && map.value) {
    // Highlight hovered place on map
    map.value.getCanvas().style.cursor = 'pointer'
  } else if (map.value) {
    map.value.getCanvas().style.cursor = ''
  }
})

watch(() => props.userLocation, (newLocation, oldLocation) => {
  console.log('📋 Watching userLocation changed to:', newLocation)
  console.log('📋 Old location was:', oldLocation)
  console.log('🗺️ Map loaded?', isLoaded.value, 'Map exists?', !!map.value)
  
  if (newLocation && newLocation.longitude && newLocation.latitude) {
    if (map.value && isLoaded.value) {
      console.log('🚁 Flying to user location:', newLocation)
      flyToUserLocation()
    } else {
      console.log('🔄 Map not ready yet, will reinitialize when location changes')
      // If map isn't ready yet but we have user location, reinitialize
      if (mapContainer.value) {
        initializeMap()
      }
    }
  }
}, { immediate: true })

// Lifecycle
onMounted(async () => {
  console.log('🔥 PlacesMap.vue onMounted called')
  console.log('📍 userLocation on mount:', props.userLocation)
  await nextTick()
  await initializeMap()
})

onUnmounted(() => {
  if (map.value) {
    map.value.off('moveend', handleMapMove)
    map.value.off('zoomend', handleMapMove)
  }
})

// Expose methods for parent component
defineExpose({
  fitToPlaces,
  flyToPlace,
  flyToUserLocation,
  getBounds,
  initializeMap
})
</script>

<style scoped>
/* Mapbox GL CSS will be imported in the main app */
.place-popup {
  @apply min-w-0 max-w-sm;
}

.popup-loading {
  @apply flex items-center justify-center p-4;
}

/* Custom map controls styling */
:deep(.mapboxgl-ctrl-bottom-left) {
  display: none;
}

:deep(.mapboxgl-ctrl-bottom-right) {
  display: none;
}

:deep(.mapboxgl-popup-content) {
  @apply p-0 rounded-lg shadow-lg;
}

:deep(.mapboxgl-popup-close-button) {
  @apply text-gray-400 hover:text-gray-600 text-lg font-bold;
  right: 8px;
  top: 8px;
}

:deep(.mapboxgl-popup-tip) {
  border-top-color: white;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .absolute.top-4.left-4 {
    @apply top-2 left-2;
  }
  
  .absolute.bottom-4.left-4,
  .absolute.bottom-4.right-4 {
    @apply bottom-2;
  }
  
  .absolute.bottom-4.left-4 {
    @apply left-2;
  }
  
  .absolute.bottom-4.right-4 {
    @apply right-2;
  }
}
</style>