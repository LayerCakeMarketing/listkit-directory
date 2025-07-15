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
          <li class="font-medium text-gray-900">{{ stateData?.display_name || stateData?.full_name || stateData?.name || state }}</li>
        </ol>
      </nav>

      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ stateData?.display_name || stateData?.full_name || stateData?.name || 'Loading...' }}</h1>
        <p v-if="stateData?.description" class="mt-2 text-lg text-gray-600">{{ stateData.description }}</p>
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

        <!-- Cities List -->
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Cities in {{ stateData?.display_name || stateData?.full_name || stateData?.name }}</h2>
          </div>
          
          <div class="p-6">
            <div v-if="cities.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="city in cities"
                :key="city.id"
                :to="`/regions/${state}/${city.slug}`"
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const props = defineProps({
  state: {
    type: String,
    required: true
  }
})

const route = useRoute()
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cities = ref([])
const featuredPlaces = ref([])

const fetchStateData = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch state data
    const stateResponse = await axios.get(`/api/regions/by-slug/${props.state}`)
    stateData.value = stateResponse.data.data // Access the data property
    
    console.log('State data response:', stateData.value)
    
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

onMounted(() => {
  fetchStateData()
})
</script>