<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Location Banner -->
      <div v-if="locationData?.current && !loading" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <div>
              <p class="text-sm text-blue-800 font-medium">
                Showing content for {{ locationData.current.name }}{{ locationData.current.level > 1 && locationData.hierarchy?.length > 1 ? `, ${locationData.hierarchy[0].name}` : '' }}
              </p>
              <p class="text-xs text-blue-600">
                {{ locationData.detected_via === 'manual' ? 'Location selected manually' : 'Location detected automatically' }}
              </p>
            </div>
          </div>
          <LocationSearch
            v-model="selectedLocation"
            @change="handleLocationChange"
            :placeholder="'Change location'"
            class="w-48"
          />
        </div>
      </div>

      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          {{ pageSettings?.title || 'Browse by Region' }}
        </h1>
        <p class="mt-2 text-lg text-gray-600">
          {{ pageSettings?.intro_text || 'Explore places organized by states and cities' }}
        </p>
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
        <!-- Featured Lists Section -->
        <div v-if="featuredLists.length > 0" class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Lists{{ locationData?.current ? ` in ${locationData.current.name}` : '' }}</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <router-link
              v-for="list in featuredLists"
              :key="list.id"
              :to="`/lists/${list.slug}`"
              class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-200"
            >
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ list.name }}</h3>
              <p v-if="list.description" class="text-gray-600 text-sm mb-3 line-clamp-2">{{ list.description }}</p>
              <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-500">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  {{ list.items_count || 0 }} places
                </div>
                <span class="text-blue-600 text-sm font-medium">View →</span>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Featured Places Section -->
        <div v-if="featuredPlaces.length > 0" class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Places{{ locationData?.current ? ` in ${locationData.current.name}` : '' }}</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <router-link
              v-for="place in featuredPlaces"
              :key="place.id"
              :to="place.canonical_url || `/p/${place.id}`"
              class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
            >
              <div v-if="place.cover_image_url || place.logo_url" class="h-48 bg-gray-200">
                <img
                  :src="place.cover_image_url || place.logo_url"
                  :alt="place.title"
                  class="w-full h-full object-cover"
                />
              </div>
              <div v-else class="h-48 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                <span class="text-6xl opacity-80">{{ place.category?.icon || '📍' }}</span>
              </div>
              <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                  <span v-if="place.category" class="text-xs text-gray-500">{{ place.category.name }}</span>
                  <span v-if="place.pivot?.tagline" class="text-xs text-gray-600 italic">{{ place.pivot.tagline }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ place.title }}</h3>
                <div v-if="place.location" class="flex items-center text-gray-500 text-sm">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  {{ place.location.city }}, {{ place.location.state }}
                </div>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Custom Content Sections -->
        <div v-if="contentSections.length > 0" class="mb-8">
          <div v-for="(section, index) in contentSections" :key="index" class="mb-8">
            <div v-if="section.type === 'html'" class="bg-white rounded-lg shadow-sm p-6" v-html="section.content"></div>
            <div v-else-if="section.type === 'text'" class="bg-white rounded-lg shadow-sm p-6">
              <h3 v-if="section.title" class="text-xl font-semibold text-gray-900 mb-4">{{ section.title }}</h3>
              <p class="text-gray-700 whitespace-pre-line">{{ section.content }}</p>
            </div>
          </div>
        </div>

        <!-- States Grid -->
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Browse All States</h2>
          </div>
          
          <div class="p-6">
            <!-- States List -->
            <div v-if="states.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div v-for="state in states" :key="state.id" class="group">
                <router-link 
                  :to="`/local/${state.slug}`"
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
                      <p v-if="state.directory_entries_count > 0" class="text-xs text-gray-400 mt-1">
                        {{ state.directory_entries_count }} {{ state.directory_entries_count === 1 ? 'place' : 'places' }}
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
                :to="`/local/${city.parent.slug}/${city.slug}`"
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import LocationSearch from '@/components/LocationSearch.vue'

const loading = ref(true)
const error = ref(null)
const states = ref([])
const popularCities = ref([])
const featuredLists = ref([])
const featuredPlaces = ref([])
const locationData = ref(null)
const pageSettings = ref(null)
const contentSections = ref([])
const selectedLocation = ref(null)

const fetchLocalPageData = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch local page data
    const response = await axios.get('/api/local')
    
    // Set data from API response
    locationData.value = response.data.location
    pageSettings.value = response.data.page_settings
    featuredLists.value = response.data.featured_lists || []
    featuredPlaces.value = response.data.featured_places || []
    contentSections.value = response.data.content_sections || []
    
    // Use popular regions from API or fallback to fetching separately
    if (response.data.popular_regions) {
      states.value = response.data.popular_regions
    } else {
      // Fetch states as fallback
      await fetchRegions()
    }
    
  } catch (err) {
    console.error('Error fetching local page data:', err)
    error.value = 'Failed to load content. Please try again later.'
    // Try to at least load regions
    await fetchRegions()
  } finally {
    loading.value = false
  }
}

const fetchRegions = async () => {
  try {
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
    if (!error.value) {
      error.value = 'Failed to load regions. Please try again later.'
    }
  }
}

const handleLocationChange = async (newLocation) => {
  if (newLocation) {
    // Update session location
    await axios.post('/api/places/set-location', { region_id: newLocation.id })
    // Refresh page data
    await fetchLocalPageData()
  }
}

onMounted(() => {
  document.title = pageSettings.value?.meta_description || 'Browse Local Places'
  fetchLocalPageData()
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