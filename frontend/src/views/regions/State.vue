<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumb -->
      <nav class="mb-4">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
          <li>
            <router-link to="/local" class="hover:text-gray-700">Local</router-link>
          </li>
          <li>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="font-medium text-gray-900">{{ stateData?.name || state }}</li>
        </ol>
      </nav>

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ stateData?.name || 'Loading...' }}</h1>
            <p v-if="stateData?.description" class="mt-2 text-lg text-gray-600">{{ stateData.description }}</p>
          </div>
          <SaveButton
            v-if="authStore.isAuthenticated && stateData?.id"
            item-type="region"
            :item-id="stateData.id"
            :initial-saved="stateData.is_saved || false"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Cover Image -->
        <div v-if="stateData?.cover_image_url" class="mb-8 rounded-lg overflow-hidden shadow-lg">
          <img 
            :src="stateData.cover_image_url" 
            :alt="stateData.name"
            class="w-full h-64 object-cover"
          >
        </div>

        <!-- Stats -->
        <div v-if="stateData" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Total Cities</div>
            <div class="text-2xl font-bold text-gray-900">{{ cities.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Total Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ stateData.entries_count || 0 }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Featured Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ featuredPlaces.length }}</div>
          </div>
        </div>

        <!-- Region Details Tab -->
        <RegionDetailsTab
          v-if="stateData"
          :region="stateData"
          class="mb-8"
        />

        <!-- State Symbols -->
        <div v-if="stateData?.state_symbols && Object.keys(stateData.state_symbols).some(key => stateData.state_symbols[key] && (typeof stateData.state_symbols[key] === 'string' ? stateData.state_symbols[key].length > 0 : stateData.state_symbols[key].length > 0))" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">State Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="stateData.state_symbols.nickname" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">Nickname:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.nickname }}</span>
            </div>
            <div v-if="stateData.state_symbols.capital" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">Capital:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.capital }}</span>
            </div>
            <div v-if="stateData.state_symbols.motto" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">Motto:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.motto }}</span>
            </div>
            <div v-if="stateData.state_symbols.bird && stateData.state_symbols.bird.length > 0" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">State Bird:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.bird[0] }}</span>
            </div>
            <div v-if="stateData.state_symbols.flower && stateData.state_symbols.flower.length > 0" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">State Flower:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.flower[0] }}</span>
            </div>
            <div v-if="stateData.state_symbols.tree && stateData.state_symbols.tree.length > 0" class="flex items-start">
              <span class="text-gray-500 font-medium mr-2">State Tree:</span>
              <span class="text-gray-900">{{ stateData.state_symbols.tree[0] }}</span>
            </div>
          </div>
        </div>

        <!-- Facts -->
        <div v-if="stateData?.facts && stateData.facts.length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Facts</h2>
          <ul class="list-disc list-inside space-y-2 text-gray-700">
            <li v-for="(fact, index) in stateData.facts" :key="index">{{ fact }}</li>
          </ul>
        </div>

        <!-- Featured Regions -->
        <FeaturedRegionsGrid 
          v-if="stateData"
          :region-slug="state"
          :region-name="stateData.name"
          class="mb-8"
        />

        <!-- Cities List with Map -->
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Explore {{ stateData?.name }}</h2>
          </div>
          
          <div class="p-6">
            <!-- Two-column layout: Cities list on left (40%), map on right (60%) -->
            <div v-if="cities.length > 0" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
              <!-- Left Column - Cities List (40% width) -->
              <div class="lg:col-span-2 space-y-4 overflow-y-auto" style="max-height: 90vh;">
                <div
                  v-for="city in cities"
                  :key="city.id"
                  @click="handleCityClick(city)"
                  @mouseenter="hoveredCityId = city.id"
                  @mouseleave="hoveredCityId = null"
                  class="group cursor-pointer rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
                >
                  <!-- City Cover Image -->
                  <div v-if="city.cover_image_url" class="relative h-32">
                    <img 
                      :src="city.cover_image_url" 
                      :alt="city.name"
                      class="w-full h-full object-cover"
                    >
                    <div v-if="city.is_featured" class="absolute top-2 left-2 bg-purple-600 text-white px-2 py-1 rounded text-xs font-medium">
                      Featured
                    </div>
                  </div>
                  
                  <!-- City Info -->
                  <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                      <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600 flex-1">
                        {{ city.name }}
                      </h3>
                      <span v-if="city.entries_count > 0" class="text-sm text-gray-500">
                        {{ city.entries_count }} places
                      </span>
                    </div>
                    
                    <p v-if="city.description" class="text-sm text-gray-500 line-clamp-2 mb-2">
                      {{ stripHtml(city.description) }}
                    </p>
                    
                    <div v-if="city.children_count > 0" class="text-xs text-gray-400 mb-3">
                      <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                      </svg>
                      {{ city.children_count }} neighborhoods
                    </div>
                    
                    <!-- View Details Link -->
                    <router-link
                      :to="`/local/${stateData?.slug || state}/${city.slug}`"
                      @click.stop
                      class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800"
                    >
                      View City
                      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </router-link>
                  </div>
                </div>
              </div>
              
              <!-- Right Column - Map (60% width) -->
              <div class="lg:col-span-3 relative">
                <div 
                  ref="mapContainer"
                  id="state-map"
                  class="w-full h-[90vh] rounded-lg shadow-inner"
                >
                  <div v-if="mapLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg">
                    <div class="text-center">
                      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                      <p class="mt-3 text-sm text-gray-600">Loading map...</p>
                    </div>
                  </div>
                  
                  <div v-if="mapError" class="absolute inset-0 flex items-center justify-center bg-red-50 rounded-lg">
                    <div class="text-center">
                      <svg class="w-12 h-12 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <p class="mt-3 text-sm text-red-600">{{ mapError }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- Map Style Selector -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg">
                  <select 
                    v-model="currentMapStyle" 
                    @change="changeMapStyle"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-transparent border-0 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <!-- Standard Mapbox Styles -->
                    <option value="mapbox://styles/mapbox/streets-v12">Streets</option>
                    <option value="mapbox://styles/mapbox/outdoors-v12">Outdoors</option>
                    <option value="mapbox://styles/mapbox/light-v11">Light</option>
                    <option value="mapbox://styles/mapbox/dark-v11">Dark</option>
                    <option value="mapbox://styles/mapbox/satellite-v9">Satellite</option>
                    <option value="mapbox://styles/mapbox/satellite-streets-v12">Hybrid</option>
                    
                    <!-- Navigation Styles -->
                    <option value="mapbox://styles/mapbox/navigation-day-v1">Navigation Day</option>
                    <option value="mapbox://styles/mapbox/navigation-night-v1">Navigation Night</option>
                    
                    <!-- Custom styles from Mapbox Studio -->
                    <option value="mapbox://styles/illuminatelocal/clf0ha89i000a01o7fyggdklz">Illuminate Local</option>
                  </select>
                </div>
                
                <!-- Map Legend -->
                <div class="absolute bottom-4 right-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg p-3">
                  <div class="text-xs font-semibold text-gray-700 mb-2">Legend</div>
                  <div class="space-y-1">
                    <div class="flex items-center">
                      <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                      <span class="text-xs text-gray-600">Cities</span>
                    </div>
                    <div v-if="featuredCities.length > 0" class="flex items-center">
                      <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
                      <span class="text-xs text-gray-600">Featured Cities</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Original grid layout fallback for smaller screens -->
            <div v-else-if="cities.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:hidden">
              <router-link
                v-for="city in cities"
                :key="city.id"
                :to="`/local/${stateData?.slug || state}/${city.slug}`"
                class="group block p-4 rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                      {{ city.name }}
                    </h3>
                    <p v-if="city.entries_count > 0" class="text-sm text-gray-500">
                      {{ city.entries_count }} {{ city.entries_count === 1 ? 'place' : 'places' }}
                    </p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </router-link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No cities found</h3>
              <p class="mt-1 text-sm text-gray-500">No cities have been added to this state yet.</p>
            </div>
          </div>
        </div>

        <!-- Featured Places -->
        <div v-if="featuredPlaces.length > 0" class="mt-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Featured Places</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="place in featuredPlaces"
                :key="place.id"
                :to="place.canonical_url || `/p/${place.id}`"
                class="group block rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
              >
                <div v-if="place.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="place.featured_image_url" 
                    :alt="place.title"
                    class="w-full h-48 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                    {{ place.title }}
                  </h3>
                  <p v-if="place.category" class="text-sm text-gray-500">{{ place.category.name }}</p>
                  <p v-if="place.city_region" class="text-sm text-gray-400">{{ place.city_region.name }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin Edit Button -->
      <button
        v-if="authStore.user && (authStore.user.role === 'admin' || authStore.user.role === 'manager')"
        @click="isDrawerOpen = true"
        class="fixed bottom-6 right-6 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <CogIcon class="h-6 w-6" />
      </button>

      <!-- Admin Edit Drawer -->
      <RegionEditDrawer
        v-if="authStore.user && (authStore.user.role === 'admin' || authStore.user.role === 'manager')"
        :is-open="isDrawerOpen"
        :region="stateData"
        @close="isDrawerOpen = false"
        @updated="handleRegionUpdated"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import SaveButton from '@/components/SaveButton.vue'
import RegionDetailsTab from '@/components/regions/RegionDetailsTab.vue'
import RegionEditDrawer from '@/components/regions/RegionEditDrawer.vue'
import FeaturedRegionsGrid from '@/components/regions/FeaturedRegionsGrid.vue'
import { CogIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useMapbox } from '@/composables/useMapbox'

const props = defineProps({
  state: {
    type: String,
    required: true
  }
})

const route = useRoute()
const authStore = useAuthStore()
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cities = ref([])
const featuredPlaces = ref([])
const isDrawerOpen = ref(false)
const hoveredCityId = ref(null)
const mapContainer = ref(null)
const mapLoading = ref(false)
const mapError = ref(null)
const currentMapStyle = ref('mapbox://styles/illuminatelocal/clf0ha89i000a01o7fyggdklz') // Illuminate Local custom style

// Mapbox composable
const {
  map,
  initializeMap,
  cleanup,
  isLoaded
} = useMapbox()

// Computed properties
const featuredCities = computed(() => cities.value.filter(c => c.is_featured))

// Helper functions
const stripHtml = (html) => {
  if (!html) return ''
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  return tmp.textContent || tmp.innerText || ''
}

const fetchStateData = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch state data
    const stateResponse = await axios.get(`/api/regions/by-slug/${props.state}`)
    stateData.value = stateResponse.data.data // Access the data property
    
    console.log('State data response:', stateData.value)
    console.log('Cloudflare image ID:', stateData.value?.cloudflare_image_id)
    console.log('Cover image URL:', stateData.value?.cover_image_url)
    
    // Fetch cities in this state - add validation for stateData.value.id
    if (stateData.value && stateData.value.id) {
      const citiesResponse = await axios.get(`/api/regions/${stateData.value.id}/children`)
      cities.value = citiesResponse.data.data || []
      
      // Fetch featured places
      const featuredResponse = await axios.get(`/api/regions/${stateData.value.id}/featured`)
      featuredPlaces.value = featuredResponse.data.data || []
    } else {
      console.error('State data missing or invalid:', stateData.value)
      error.value = 'Invalid state data received'
    }
    
  } catch (err) {
    console.error('Error fetching state data:', err)
    error.value = 'Failed to load state information. Please try again later.'
  } finally {
    loading.value = false
  }
}

const handleRegionUpdated = (updatedRegion) => {
  stateData.value = updatedRegion
  isDrawerOpen.value = false
  // Optionally refresh other data that might have changed
  fetchStateData()
}

const initStateMap = async () => {
  console.log('initStateMap called for state:', stateData.value?.name)
  
  // Reset states
  mapLoading.value = true
  mapError.value = null
  
  try {
    if (cities.value.length === 0) {
      mapError.value = 'No cities to display on map'
      mapLoading.value = false
      return
    }
    
    // Wait for DOM to update
    await nextTick()
    
    // Use the ref directly or fallback to getElementById
    let mapContainerEl = mapContainer.value || document.getElementById('state-map')
    
    // Retry logic if container not found
    let retries = 0
    while (!mapContainerEl && retries < 5) {
      await new Promise(resolve => setTimeout(resolve, 100))
      mapContainerEl = mapContainer.value || document.getElementById('state-map')
      retries++
    }
    
    if (!mapContainerEl) {
      console.error('Map container not found after retries')
      mapError.value = 'Map container element not found'
      mapLoading.value = false
      return
    }
    
    console.log('State map container found:', mapContainerEl)
    
    // Initialize map centered on state
    const centerLat = stateData.value?.lat || stateData.value?.latitude || 34.0522
    const centerLng = stateData.value?.lng || stateData.value?.longitude || -118.2437
    
    console.log('Initializing state map at:', { centerLat, centerLng })
    
    const success = await initializeMap('state-map', {
      center: [centerLng, centerLat],
      zoom: 6,
      style: currentMapStyle.value
    })
    
    if (!success) {
      console.error('Failed to initialize state map')
      mapError.value = 'Failed to initialize map. Please check your internet connection.'
      mapLoading.value = false
      return
    }
    
    console.log('State map initialized successfully')
    
    // Add markers for cities
    if (map.value) {
      cities.value.forEach(city => {
        if (city.lat && city.lng) {
          const markerColor = city.is_featured ? '#9333EA' : '#3B82F6' // Purple for featured, blue for regular
          const marker = new window.mapboxgl.Marker({
            color: markerColor
          })
            .setLngLat([city.lng, city.lat])
            .setPopup(new window.mapboxgl.Popup().setHTML(`
              <div class="p-3">
                <h3 class="font-semibold">${city.name}</h3>
                <p class="text-sm text-gray-600 mt-1">${city.entries_count || 0} places</p>
                ${city.children_count ? `<p class="text-sm text-gray-600">${city.children_count} neighborhoods</p>` : ''}
                <a href="/local/${props.state}/${city.slug}" 
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                  View City →
                </a>
              </div>
            `))
            .addTo(map.value)
        }
      })
      
      // Add markers for featured regions
      if (stateData.value?.featured_regions_map_data) {
        stateData.value.featured_regions_map_data.forEach(region => {
          if (region.coordinates) {
            // Different marker styles based on region type
            const markerColors = {
              'national_park': '#10B981', // Green
              'state_park': '#06B6D4', // Cyan
              'regional_park': '#8B5CF6', // Violet
              'local_park': '#F59E0B', // Amber
              'city': '#EC4899', // Pink
              'neighborhood': '#F97316' // Orange
            }
            const markerColor = markerColors[region.type] || '#6B7280' // Gray default
            
            // Create custom marker element for featured regions
            const el = document.createElement('div')
            el.className = 'featured-region-marker'
            el.style.width = '30px'
            el.style.height = '30px'
            el.style.borderRadius = '50%'
            el.style.backgroundColor = markerColor
            el.style.border = '3px solid white'
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)'
            el.style.cursor = 'pointer'
            
            const marker = new window.mapboxgl.Marker(el)
              .setLngLat([region.coordinates.lng, region.coordinates.lat])
              .setPopup(new window.mapboxgl.Popup().setHTML(`
                <div class="p-3">
                  <h3 class="font-semibold">${region.name}</h3>
                  ${region.label ? `<span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">${region.label}</span>` : ''}
                  <p class="text-sm text-gray-600 mt-1 capitalize">${region.type.replace(/_/g, ' ')}</p>
                  ${region.description ? `<p class="text-sm text-gray-600 mt-1">${region.description}</p>` : ''}
                  <a href="${region.url}" 
                     class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                    View ${region.type.replace(/_/g, ' ')} →
                  </a>
                </div>
              `))
              .addTo(map.value)
          }
        })
      }
      
      // Fit bounds to show all cities and featured regions
      const allMarkers = []
      
      // Add cities to bounds
      cities.value.forEach(city => {
        if (city.lat && city.lng) {
          allMarkers.push([city.lng, city.lat])
        }
      })
      
      // Add featured regions to bounds
      if (stateData.value?.featured_regions_map_data) {
        stateData.value.featured_regions_map_data.forEach(region => {
          if (region.coordinates) {
            allMarkers.push([region.coordinates.lng, region.coordinates.lat])
          }
        })
      }
      
      if (allMarkers.length > 1) {
        const bounds = new window.mapboxgl.LngLatBounds()
        allMarkers.forEach(coords => {
          bounds.extend(coords)
        })
        map.value.fitBounds(bounds, { padding: 50 })
      }
    }
    
  } catch (err) {
    console.error('Error initializing state map:', err)
    mapError.value = 'Failed to initialize map'
  } finally {
    mapLoading.value = false
  }
}

const handleCityClick = (city) => {
  console.log('City clicked:', city)
  
  // Check if city has coordinates
  if (!city.lat || !city.lng) {
    console.log('City has no coordinates')
    return
  }
  
  // Fly to the city location
  if (map.value) {
    map.value.flyTo({
      center: [city.lng, city.lat],
      zoom: 10,
      essential: true
    })
  }
}

const changeMapStyle = () => {
  if (map.value && currentMapStyle.value) {
    map.value.setStyle(currentMapStyle.value)
    
    // Re-add cities after style change (styles clear all sources/layers)
    map.value.once('styledata', () => {
      // Wait for style to load then re-add our data
      setTimeout(() => {
        // Re-add city markers
        cities.value.forEach(city => {
          if (city.lat && city.lng) {
            const markerColor = city.is_featured ? '#9333EA' : '#3B82F6'
            const marker = new window.mapboxgl.Marker({
              color: markerColor
            })
              .setLngLat([city.lng, city.lat])
              .setPopup(new window.mapboxgl.Popup().setHTML(`
                <div class="p-3">
                  <h3 class="font-semibold">${city.name}</h3>
                  <p class="text-sm text-gray-600 mt-1">${city.entries_count || 0} places</p>
                  ${city.children_count ? `<p class="text-sm text-gray-600">${city.children_count} neighborhoods</p>` : ''}
                  <a href="/local/${props.state}/${city.slug}" 
                     class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                    View City →
                  </a>
                </div>
              `))
              .addTo(map.value)
          }
        })
      }, 500)
    })
  }
}

onMounted(() => {
  fetchStateData()
})

// Watch for cities to be loaded and initialize map
watch(cities, async (newCities) => {
  if (newCities.length > 0) {
    // Wait for multiple ticks to ensure DOM is fully rendered
    await nextTick()
    await nextTick()
    
    // Add a small delay to ensure the DOM is ready
    setTimeout(() => {
      initStateMap()
    }, 100)
  }
}, { immediate: false })

// Clean up map on component unmount
onUnmounted(() => {
  if (map.value) {
    cleanup()
  }
})
</script>