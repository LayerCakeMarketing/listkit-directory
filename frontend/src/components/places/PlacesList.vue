<template>
  <div class="flex flex-col h-full bg-white">
    <!-- Header -->
    <div class="flex-shrink-0 p-4 border-b border-gray-200">
      <!-- Search Bar -->
      <div class="relative mb-4">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="absolute inset-y-0 right-0 pr-3 flex items-center"
        >
          <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Controls Row -->
      <div class="flex items-center justify-between">
        <!-- Results Count and Sort -->
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">
            {{ total }} result{{ total !== 1 ? 's' : '' }}
            <span v-if="hasActiveFilters" class="text-blue-600">
              (filtered)
            </span>
          </span>

          <!-- Sort Dropdown -->
          <div class="relative">
            <select
              :value="currentSort"
              @change="$emit('update:current-sort', $event.target.value)"
              class="text-sm border border-gray-300 rounded-md px-3 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
              <option
                v-for="option in sortOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- View Mode and Filter Toggle -->
        <div class="flex items-center space-x-2">
          <!-- Filter Button -->
          <button
            @click="$emit('toggle-filters')"
            :class="[
              'relative p-2 rounded-md text-gray-400 hover:text-gray-600 transition-colors',
              showFilters ? 'bg-blue-50 text-blue-600' : ''
            ]"
            :aria-label="showFilters ? 'Hide filters' : 'Show filters'"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.121A1 1 0 013 6.414V4z" />
            </svg>
            <span
              v-if="activeFiltersCount > 0"
              class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
            >
              {{ activeFiltersCount }}
            </span>
          </button>

          <!-- View Mode Toggle -->
          <div class="flex border border-gray-300 rounded-md">
            <button
              @click="$emit('update:viewMode', 'list')"
              :class="[
                'p-2 rounded-l-md transition-colors',
                viewMode === 'list' 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-white text-gray-400 hover:text-gray-600'
              ]"
              aria-label="List view"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </button>
            <button
              @click="$emit('update:viewMode', 'grid')"
              :class="[
                'p-2 rounded-r-md transition-colors',
                viewMode === 'grid' 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-white text-gray-400 hover:text-gray-600'
              ]"
              aria-label="Grid view"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Active Filters Pills -->
      <div 
        v-if="hasActiveFilters" 
        class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-100"
      >
        <button
          v-if="filters.category"
          @click="$emit('update-filter', 'category', null)"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors"
        >
          Category: {{ getCategoryName(filters.category) }}
          <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <button
          v-if="filters.rating > 0"
          @click="$emit('update-filter', 'rating', 0)"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors"
        >
          {{ filters.rating }}+ stars
          <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <button
          v-if="filters.openNow"
          @click="$emit('update-filter', 'openNow', false)"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors"
        >
          Open now
          <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <button
          v-if="filters.distance < 25"
          @click="$emit('update-filter', 'distance', 25)"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors"
        >
          Within {{ filters.distance }} mi
          <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <button
          @click="$emit('clear-filters')"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
        >
          Clear all
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading && places.length === 0" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent mx-auto mb-4"></div>
        <p class="text-gray-600">Loading places...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center">
      <div class="text-center p-6">
        <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.768 0L3.046 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Places</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button
          @click="$emit('retry')"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
        >
          Try Again
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="places.length === 0 && !isLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center p-6">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No places found</h3>
        <p class="text-gray-600 mb-4">
          {{ hasActiveFilters 
            ? 'Try adjusting your filters or search terms.' 
            : 'No places available in this area.' 
          }}
        </p>
        <button
          v-if="hasActiveFilters"
          @click="$emit('clear-filters')"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
        >
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Places List -->
    <div 
      v-else
      class="flex-1 overflow-y-auto"
      @scroll="handleScroll"
      ref="scrollContainer"
    >
      <!-- Grid View -->
      <div 
        v-if="viewMode === 'grid'"
        class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        <PlaceCard
          v-for="place in places"
          :key="place.id"
          :place="place"
          :is-selected="selectedPlace?.id === place.id"
          :is-hovered="hoveredPlace?.id === place.id"
          :distance="place.distance_miles || place.distance"
          @click="$emit('select-place', place)"
          @hover="$emit('hover-place', $event)"
          @save="handleSave"
        />
      </div>

      <!-- List View -->
      <div v-else class="divide-y divide-gray-200">
        <PlaceCard
          v-for="place in places"
          :key="place.id"
          :place="place"
          size="compact"
          :is-selected="selectedPlace?.id === place.id"
          :is-hovered="hoveredPlace?.id === place.id"
          :distance="place.distance_miles || place.distance"
          class="mx-4 my-2"
          @click="$emit('select-place', place)"
          @hover="$emit('hover-place', $event)"
          @save="handleSave"
        />
      </div>

      <!-- Load More / Infinite Scroll -->
      <div 
        v-if="hasMore && !isLoading"
        class="p-4 text-center"
      >
        <button
          @click="$emit('load-more')"
          class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 px-6 py-2 rounded-md text-sm font-medium transition-colors"
        >
          Load More Places
        </button>
      </div>

      <!-- Loading More Indicator -->
      <div 
        v-if="isLoading && places.length > 0"
        class="p-4 text-center"
      >
        <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent mx-auto"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { debounce } from 'lodash'
import PlaceCard from './PlaceCard.vue'

// Props
const props = defineProps({
  places: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  hasMore: {
    type: Boolean,
    default: false
  },
  total: {
    type: Number,
    default: 0
  },
  selectedPlace: {
    type: Object,
    default: null
  },
  hoveredPlace: {
    type: Object,
    default: null
  },
  viewMode: {
    type: String,
    default: 'grid',
    validator: (value) => ['grid', 'list'].includes(value)
  },
  showFilters: {
    type: Boolean,
    default: false
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  categories: {
    type: Array,
    default: () => []
  },
  currentSort: {
    type: String,
    default: 'relevance'
  },
  sortOptions: {
    type: Array,
    default: () => [
      { value: 'relevance', label: 'Relevance' },
      { value: 'distance', label: 'Distance' },
      { value: 'rating', label: 'Highest Rated' },
      { value: 'newest', label: 'Newest' },
      { value: 'name', label: 'Name A-Z' }
    ]
  }
})

// Emits
const emit = defineEmits([
  'update:viewMode',
  'update:currentSort',
  'select-place',
  'hover-place',
  'toggle-filters',
  'update-filter',
  'clear-filters',
  'load-more',
  'retry',
  'search',
  'save-place'
])

// Refs
const scrollContainer = ref(null)
const searchQuery = ref('')

// Computed
const hasActiveFilters = computed(() => {
  return props.filters.category || 
         props.filters.rating > 0 || 
         props.filters.openNow ||
         props.filters.distance < 25 ||
         props.filters.search ||
         (props.filters.features && props.filters.features.length > 0)
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (props.filters.category) count++
  if (props.filters.rating > 0) count++
  if (props.filters.openNow) count++
  if (props.filters.distance < 25) count++
  if (props.filters.search) count++
  if (props.filters.features?.length > 0) count += props.filters.features.length
  return count
})

// Methods
const handleSearch = () => {
  emit('search', searchQuery.value)
}

const clearSearch = () => {
  searchQuery.value = ''
  emit('search', '')
}

const handleSave = (event) => {
  emit('save-place', event)
}

const getCategoryName = (categoryId) => {
  const category = props.categories.find(cat => cat.id === categoryId)
  return category ? category.name : 'Unknown'
}

const handleScroll = debounce(() => {
  if (!scrollContainer.value || !props.hasMore || props.isLoading) return
  
  const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value
  const threshold = 200 // pixels from bottom
  
  if (scrollHeight - scrollTop - clientHeight < threshold) {
    emit('load-more')
  }
}, 200)

// Watchers
watch(() => props.currentSort, (newSort) => {
  emit('update:currentSort', newSort)
})

watch(() => props.filters.search, (newSearch) => {
  searchQuery.value = newSearch || ''
})

// Initialize search query from filters
if (props.filters.search) {
  searchQuery.value = props.filters.search
}
</script>

<style scoped>
/* Custom scrollbar styling for webkit browsers */
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
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Focus styles for accessibility */
button:focus,
select:focus,
input:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Grid responsive adjustments are handled by Tailwind's built-in responsive utilities */
</style>