<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumb -->
      <nav class="mb-4">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
          <li>
            <router-link to="/regions" class="hover:text-gray-700">Regions</router-link>
          </li>
          <li>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li>
            <router-link :to="`/regions/${state}`" class="hover:text-gray-700">{{ stateData?.display_name || stateData?.full_name || stateData?.name || state }}</router-link>
          </li>
          <template v-if="isNeighborhood">
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li>
              <router-link :to="`/regions/${state}/${city}`" class="hover:text-gray-700">{{ cityData?.parent?.name || city }}</router-link>
            </li>
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="font-medium text-gray-900">{{ cityData?.name || neighborhood }}</li>
          </template>
          <template v-else>
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="font-medium text-gray-900">{{ cityData?.name || city }}</li>
          </template>
        </ol>
      </nav>

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ cityData?.display_name || cityData?.full_name || cityData?.name || 'Loading...' }}<span v-if="isNeighborhood && cityData?.parent">, {{ cityData.parent.name }}</span><span v-if="stateData">, {{ stateData.display_name || stateData.full_name || stateData.name }}</span>
            </h1>
            <p v-if="cityData?.description" class="mt-2 text-lg text-gray-600">{{ cityData.description }}</p>
          </div>
          <SaveButton
            v-if="authStore.isAuthenticated && cityData?.id"
            item-type="region"
            :item-id="cityData.id"
            :initial-saved="cityData.is_saved || false"
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
        <div v-if="cityData?.cover_image_url" class="mb-8 rounded-lg overflow-hidden shadow-lg">
          <img 
            :src="cityData.cover_image_url" 
            :alt="cityData.name"
            class="w-full h-64 object-cover"
          >
        </div>

        <!-- Intro Text -->
        <div v-if="cityData?.intro_text" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <p class="text-gray-700 whitespace-pre-line">{{ cityData.intro_text }}</p>
        </div>

        <!-- Stats -->
        <div v-if="cityData" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Total Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ cityData.entries_count || places.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Featured Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ featuredPlaces.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Categories</div>
            <div class="text-2xl font-bold text-gray-900">{{ uniqueCategories }}</div>
          </div>
          <div v-if="!isNeighborhood" class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Neighborhoods</div>
            <div class="text-2xl font-bold text-gray-900">{{ neighborhoods.length }}</div>
          </div>
        </div>

        <!-- Featured Places -->
        <div v-if="featuredPlaces.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Featured Places</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="place in featuredPlaces"
                :key="place.id"
                :to="place.canonical_url || `/p/${place.id}`"
                class="group block rounded-lg border-2 border-purple-200 hover:border-purple-500 hover:shadow-md transition-all duration-200 overflow-hidden bg-purple-50"
              >
                <div v-if="place.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="place.featured_image_url" 
                    :alt="place.title"
                    class="w-full h-48 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-purple-600">
                    {{ place.title }}
                  </h3>
                  <p v-if="place.category" class="text-sm text-gray-600">{{ place.category.name }}</p>
                  <p v-if="place.pivot?.tagline" class="text-sm text-gray-500 mt-1">{{ place.pivot.tagline }}</p>
                  <div class="flex items-center gap-2 mt-2">
                    <span class="text-purple-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </span>
                    <span class="text-sm text-purple-600 font-medium">Featured</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Featured Lists -->
        <div v-if="featuredLists.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Curated Lists</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="list in featuredLists"
                :key="list.id"
                :to="`/lists/${list.slug}`"
                class="group block rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
              >
                <div v-if="list.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="list.featured_image_url" 
                    :alt="list.name"
                    class="w-full h-32 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                    {{ list.name }}
                  </h3>
                  <p v-if="list.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                    {{ list.description }}
                  </p>
                  <div class="flex items-center gap-2 mt-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>{{ list.items_count || 0 }} places</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div v-if="categories.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Browse by Category</h2>
          </div>
          <div class="p-6">
            <div class="flex flex-wrap gap-2">
              <router-link
                v-for="category in categories"
                :key="category.id"
                :to="`/places/${state}/${city}/${category.slug}`"
                class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
              >
                {{ category.name }}
                <span class="ml-2 text-xs bg-gray-200 px-2 py-0.5 rounded-full">
                  {{ category.count }}
                </span>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Places List -->
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">All Places in {{ cityData?.name }}</h2>
          </div>
          
          <div class="p-6">
            <div v-if="places.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="place in places"
                :key="place.id"
                :to="place.canonical_url || `/p/${place.id}`"
                class="group block rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
              >
                <div v-if="place.logo_url || place.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="place.logo_url || place.featured_image_url" 
                    :alt="place.title"
                    class="w-full h-48 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                    {{ place.title }}
                  </h3>
                  <p v-if="place.category" class="text-sm text-gray-500">{{ place.category.name }}</p>
                  <p v-if="place.location?.address_line1" class="text-sm text-gray-400 mt-1">
                    {{ place.location.address_line1 }}
                  </p>
                  <div v-if="place.is_featured || place.is_verified" class="flex items-center gap-2 mt-2">
                    <span v-if="place.is_featured" class="text-purple-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </span>
                    <span v-if="place.is_verified" class="text-blue-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No places found</h3>
              <p class="mt-1 text-sm text-gray-500">No places have been added to this city yet.</p>
            </div>
          </div>
        </div>

        <!-- Neighborhoods -->
        <div v-if="neighborhoods.length > 0" class="mt-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Neighborhoods</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
              <div
                v-for="neighborhood in neighborhoods"
                :key="neighborhood.id"
                class="p-3 rounded-lg bg-gray-50 text-gray-700 text-sm"
              >
                {{ neighborhood.name }}
                <span v-if="neighborhood.entries_count > 0" class="text-xs text-gray-500 ml-1">
                  ({{ neighborhood.entries_count }})
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import SaveButton from '@/components/SaveButton.vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  state: {
    type: String,
    required: true
  },
  city: {
    type: String,
    required: true
  },
  neighborhood: {
    type: String,
    required: false,
    default: null
  },
  isNeighborhood: {
    type: Boolean,
    required: false,
    default: false
  }
})

const route = useRoute()
const authStore = useAuthStore()
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cityData = ref(null)
const places = ref([])
const neighborhoods = ref([])
const categories = ref([])
const featuredPlaces = ref([])
const featuredLists = ref([])

const uniqueCategories = computed(() => {
  const cats = new Set(places.value.filter(p => p.category).map(p => p.category.id))
  return cats.size
})

const fetchCityData = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch region data based on whether it's a neighborhood or city
    let response
    if (props.isNeighborhood && props.neighborhood) {
      response = await axios.get(`/api/regions/by-slug/${props.state}/${props.city}/${props.neighborhood}`)
    } else {
      response = await axios.get(`/api/regions/by-slug/${props.state}/${props.city}`)
    }
    cityData.value = response.data.city || response.data.data
    stateData.value = response.data.state
    
    console.log('City data response:', { cityData: cityData.value, stateData: stateData.value })
    
    if (cityData.value && cityData.value.id) {
      // Fetch places in this city
      const placesResponse = await axios.get(`/api/regions/${cityData.value.id}/entries`)
      places.value = placesResponse.data.data || []
      
      // Fetch featured places for this city
      try {
        const featuredResponse = await axios.get(`/api/regions/${cityData.value.id}/featured`)
        featuredPlaces.value = featuredResponse.data.data || []
      } catch (err) {
        console.log('No featured places found')
        featuredPlaces.value = []
      }
      
      // Fetch featured lists for this city
      try {
        const listsResponse = await axios.get(`/api/admin/regions/${cityData.value.id}/featured-lists`)
        featuredLists.value = listsResponse.data.data || []
      } catch (err) {
        console.log('No featured lists found')
        featuredLists.value = []
      }
      
      // Calculate categories with counts
      const categoryMap = new Map()
      places.value.forEach(place => {
        if (place.category) {
          const cat = categoryMap.get(place.category.id) || { ...place.category, count: 0 }
          cat.count++
          categoryMap.set(place.category.id, cat)
        }
      })
      categories.value = Array.from(categoryMap.values()).sort((a, b) => b.count - a.count)
      
      // Fetch neighborhoods
      const neighborhoodsResponse = await axios.get(`/api/regions/${cityData.value.id}/children`)
      neighborhoods.value = neighborhoodsResponse.data.data || []
    } else {
      console.error('City data missing or invalid:', cityData.value)
      error.value = 'Invalid city data received'
    }
    
  } catch (err) {
    console.error('Error fetching city data:', err)
    error.value = 'Failed to load city information. Please try again later.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCityData()
})
</script>