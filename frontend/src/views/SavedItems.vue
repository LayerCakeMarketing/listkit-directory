<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">Saved Items</h1>
          <div class="text-sm text-gray-500">
            {{ totalItems }} items saved
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.name }}
            <span
              v-if="tab.count > 0"
              :class="[
                'ml-2 py-0.5 px-2 rounded-full text-xs',
                activeTab === tab.id
                  ? 'bg-blue-100 text-blue-600'
                  : 'bg-gray-100 text-gray-600'
              ]"
            >
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="currentItems.length === 0" class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No saved {{ activeTab }}</h3>
        <p class="mt-1 text-sm text-gray-500">
          Start saving {{ activeTab }} to see them here.
        </p>
      </div>

      <!-- Places Grid -->
      <div v-else-if="activeTab === 'places'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="item in currentItems"
          :key="item.id"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden"
        >
          <div v-if="item.item.image_url" class="h-48 bg-gray-200">
            <img
              :src="item.item.image_url"
              :alt="item.item.title"
              class="w-full h-full object-cover"
            />
          </div>
          <div class="p-4">
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                  <router-link
                    :to="item.item.url"
                    class="hover:text-blue-600"
                  >
                    {{ item.item.title }}
                  </router-link>
                </h3>
                <p v-if="item.item.category" class="text-sm text-gray-500">
                  {{ item.item.category }}
                </p>
                <p v-if="item.item.location" class="text-sm text-gray-500">
                  {{ item.item.location }}
                </p>
              </div>
              <button
                @click="unsaveItem(item)"
                class="ml-2 p-1 text-gray-400 hover:text-red-600"
                title="Remove from saved"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <p v-if="item.item.description" class="mt-2 text-sm text-gray-600 line-clamp-2">
              {{ item.item.description }}
            </p>
            <div class="mt-3 text-xs text-gray-500">
              Saved {{ formatDate(item.saved_at) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Lists -->
      <div v-else-if="activeTab === 'lists'" class="space-y-4">
        <div
          v-for="item in currentItems"
          :key="item.id"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">
                <router-link
                  :to="item.item.url"
                  class="hover:text-blue-600"
                >
                  {{ item.item.name }}
                </router-link>
              </h3>
              <p class="text-sm text-gray-500 mt-1">
                by {{ item.item.user.name }} · {{ item.item.items_count }} items
                <span v-if="item.item.category"> · {{ item.item.category }}</span>
              </p>
              <p v-if="item.item.description" class="mt-2 text-gray-600">
                {{ item.item.description }}
              </p>
            </div>
            <button
              @click="unsaveItem(item)"
              class="ml-4 p-1 text-gray-400 hover:text-red-600"
              title="Remove from saved"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="mt-3 text-xs text-gray-500">
            Saved {{ formatDate(item.saved_at) }}
          </div>
        </div>
      </div>

      <!-- Regions -->
      <div v-else-if="activeTab === 'regions'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="item in currentItems"
          :key="item.id"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">
                <router-link
                  :to="item.item?.url || getRegionUrl(item.item)"
                  class="hover:text-blue-600"
                >
                  {{ item.item?.name || 'Unknown Region' }}
                </router-link>
              </h3>
              <p v-if="item.item" class="text-sm text-gray-500">
                {{ item.item.type }}
                <span v-if="item.item.parent"> in {{ item.item.parent }}</span>
              </p>
              <p v-if="item.item" class="text-sm text-gray-500 mt-1">
                {{ item.item.place_count || item.item.cached_place_count || 0 }} places
              </p>
            </div>
            <button
              @click="unsaveItem(item)"
              class="ml-2 p-1 text-gray-400 hover:text-red-600"
              title="Remove from saved"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="mt-3 text-xs text-gray-500">
            Saved {{ formatDate(item.saved_at) }}
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <span class="px-4 py-2 text-sm text-gray-700">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

const { showSuccess, showError } = useNotification()

const activeTab = ref('places')
const loading = ref(false)
const savedItems = ref({
  places: [],
  lists: [],
  regions: []
})
const pagination = ref({
  places: { current_page: 1, last_page: 1, total: 0 },
  lists: { current_page: 1, last_page: 1, total: 0 },
  regions: { current_page: 1, last_page: 1, total: 0 }
})

const tabs = computed(() => [
  { id: 'places', name: 'Places', count: pagination.value.places.total },
  { id: 'lists', name: 'Lists', count: pagination.value.lists.total },
  { id: 'regions', name: 'Regions', count: pagination.value.regions.total }
])

const currentItems = computed(() => savedItems.value[activeTab.value] || [])
const currentPage = computed({
  get: () => pagination.value[activeTab.value].current_page,
  set: (value) => {
    pagination.value[activeTab.value].current_page = value
    fetchSavedItems()
  }
})
const totalPages = computed(() => pagination.value[activeTab.value].last_page)
const totalItems = computed(() => {
  return pagination.value.places.total + 
         pagination.value.lists.total + 
         pagination.value.regions.total
})

// Fetch saved items
async function fetchSavedItems() {
  loading.value = true
  
  try {
    const response = await axios.get('/api/saved-items', {
      params: {
        type: activeTab.value === 'places' ? 'place' : activeTab.value === 'lists' ? 'list' : 'region',
        page: currentPage.value,
        per_page: 12
      }
    })
    
    savedItems.value[activeTab.value] = response.data.data
    pagination.value[activeTab.value] = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total
    }
  } catch (error) {
    console.error('Error fetching saved items:', error)
    showError('Error', 'Failed to load saved items')
  } finally {
    loading.value = false
  }
}

// Unsave item
async function unsaveItem(item) {
  try {
    // Use saveable_id if the item has been deleted (item.item is null)
    const itemId = item.item?.id || item.saveable_id
    
    if (!itemId) {
      showError('Error', 'Cannot remove item: missing ID')
      return
    }
    
    await axios.delete(`/api/saved-items/${item.type}/${itemId}`)
    
    // Remove from local list
    const index = savedItems.value[activeTab.value].findIndex(i => i.id === item.id)
    if (index > -1) {
      savedItems.value[activeTab.value].splice(index, 1)
      pagination.value[activeTab.value].total--
    }
    
    showSuccess('Removed', 'Item removed from saved items')
  } catch (error) {
    console.error('Error unsaving item:', error)
    showError('Error', 'Failed to remove item')
  }
}

// Format date
function formatDate(date) {
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'today'
  if (days === 1) return 'yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  return d.toLocaleDateString()
}

// Get region URL based on region type and hierarchy
function getRegionUrl(region) {
  if (!region) return '#'
  
  // If the region already has a URL property, use it
  if (region.url) return region.url
  
  // Otherwise, construct based on type and hierarchy
  if (region.type === 'state' || region.level === 1) {
    return `/regions/${region.slug}`
  } else if (region.type === 'city' || region.level === 2) {
    // Need parent state slug
    const stateSlug = region.parent?.slug || region.parent_state?.slug
    if (stateSlug) {
      return `/regions/${stateSlug}/${region.slug}`
    }
    // Fallback if no parent info
    return `/regions/${region.slug}`
  } else if (region.type === 'neighborhood' || region.level === 3) {
    // Need parent city and state slugs
    if (region.parent?.parent?.slug && region.parent?.slug) {
      return `/regions/${region.parent.parent.slug}/${region.parent.slug}/${region.slug}`
    }
    // Fallback
    return `/regions/${region.slug}`
  }
  
  // Default fallback
  return `/regions/${region.slug}`
}

// Watch tab changes
watch(activeTab, () => {
  if (!savedItems.value[activeTab.value].length) {
    fetchSavedItems()
  }
})

// Initial load
onMounted(() => {
  fetchSavedItems()
})
</script>