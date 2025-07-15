<template>
  <div>
    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="text-center py-12">
      <h2 class="text-2xl font-semibold text-gray-900">Region not found</h2>
      <p class="mt-2 text-gray-600">{{ error }}</p>
      <router-link to="/" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
        Return to home
      </router-link>
    </div>

    <!-- Region content -->
    <div v-else-if="region">
      <!-- Hero section with cover image -->
      <div class="relative h-64 bg-gray-900">
        <img 
          v-if="region.cover_image_url" 
          :src="region.cover_image_url" 
          :alt="region.name"
          class="w-full h-full object-cover opacity-75"
        >
        <div class="absolute inset-0 "></div>
        <div class="absolute bottom-0 left-0 p-6">
          <nav class="text-sm mb-2">
            <router-link to="/" class="text-gray-300 hover:text-white">Home</router-link>
            <template v-if="breadcrumbs.length">
              <span class="text-gray-400 mx-2">/</span>
              <router-link 
                v-for="(crumb, index) in breadcrumbs" 
                :key="index"
                :to="crumb.url"
                class="text-gray-300 hover:text-white"
              >
                {{ crumb.name }}
                <span v-if="index < breadcrumbs.length - 1" class="text-gray-400 mx-2">/</span>
              </router-link>
            </template>
          </nav>
          <h1 class="text-4xl font-bold text-white">{{ region.name }}</h1>
          <p v-if="region.tagline" class="text-gray-200 mt-2">{{ region.tagline }}</p>
        </div>
      </div>

      <!-- Region intro -->
      <div v-if="region.intro_text" class="max-w-4xl mx-auto px-4 py-8">
        <div class="prose max-w-none" v-html="region.intro_text"></div>
      </div>

      <!-- Child regions -->
      <div v-if="childRegions.length > 0" class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">
          {{ region.level === 1 ? 'Cities' : 'Neighborhoods' }} in {{ region.name }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <router-link
            v-for="child in childRegions"
            :key="child.id"
            :to="child.url"
            class="block bg-white rounded-lg shadow hover:shadow-lg transition-shadow"
          >
            <div v-if="child.cover_image_url" class="h-48 rounded-t-lg overflow-hidden">
              <img :src="child.cover_image_url" :alt="child.name" class="w-full h-full object-cover">
            </div>
            <div class="p-4">
              <h3 class="text-lg font-semibold">{{ child.name }}</h3>
              <p class="text-gray-600 text-sm mt-1">{{ child.entries_count }} places</p>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Featured entries -->
      <div v-if="featuredEntries.length > 0" class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Featured in {{ region.name }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="entry in featuredEntries"
            :key="entry.id"
            class="bg-white rounded-lg shadow"
          >
            <div v-if="entry.images?.length" class="h-48 rounded-t-lg overflow-hidden">
              <img :src="entry.images[0].url" :alt="entry.name" class="w-full h-full object-cover">
            </div>
            <div class="p-4">
              <h3 class="text-lg font-semibold">{{ entry.name }}</h3>
              <p class="text-gray-600 text-sm mt-1">{{ entry.category?.name }}</p>
              <p v-if="entry.description" class="text-gray-700 mt-2 line-clamp-2">
                {{ entry.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- All entries -->
      <div class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">All Places in {{ region.name }}</h2>
        <div v-if="entries.length > 0" class="space-y-4">
          <div
            v-for="entry in entries"
            :key="entry.id"
            class="bg-white rounded-lg shadow p-4"
          >
            <h3 class="text-lg font-semibold">{{ entry.name }}</h3>
            <p class="text-gray-600 text-sm">{{ entry.category?.name }}</p>
            <p v-if="entry.address" class="text-gray-600 text-sm mt-1">{{ entry.address }}</p>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-600">
          No places found in this region yet.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useRegionsStore } from '@/stores/regions'

const route = useRoute()
const regionsStore = useRegionsStore()

const region = ref(null)
const childRegions = ref([])
const featuredEntries = ref([])
const entries = ref([])
const loading = ref(true)
const error = ref(null)

const breadcrumbs = computed(() => {
  if (!region.value) return []
  
  const crumbs = []
  
  if (region.value.parent_state) {
    crumbs.push({
      name: region.value.parent_state.name,
      url: `/local/${region.value.parent_state.slug}`
    })
  }
  
  if (region.value.parent_city) {
    crumbs.push({
      name: region.value.parent_city.name,
      url: `/local/${region.value.parent_state.slug}/${region.value.parent_city.slug}`
    })
  }
  
  if (region.value.level > 1) {
    crumbs.push({
      name: region.value.name,
      url: region.value.url
    })
  }
  
  return crumbs
})

async function loadRegion() {
  loading.value = true
  error.value = null
  
  try {
    // Fetch region data
    const regionData = await regionsStore.fetchRegionBySlug(
      route.params.state,
      route.params.city,
      route.params.neighborhood
    )
    region.value = regionData
    
    // Fetch child regions
    if (regionData.level < 3) {
      childRegions.value = await regionsStore.fetchRegionChildren(regionData.id)
    }
    
    // Fetch featured entries
    featuredEntries.value = await regionsStore.fetchFeaturedEntries(regionData.id)
    
    // Fetch all entries
    const entriesResponse = await regionsStore.fetchRegionEntries(regionData.id)
    entries.value = entriesResponse.data
  } catch (err) {
    console.error('Error loading region:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load region'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadRegion()
})

watch(() => route.params, () => {
  if (route.name === 'State' || route.name === 'City' || route.name === 'Neighborhood') {
    loadRegion()
  }
})
</script>