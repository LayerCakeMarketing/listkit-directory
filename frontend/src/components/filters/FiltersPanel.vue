<template>
  <div 
    :class="[
      'bg-white border-r border-gray-200 flex flex-col transition-all duration-300',
      isOpen ? 'w-80' : 'w-0 overflow-hidden'
    ]"
  >
    <!-- Header -->
    <div class="flex-shrink-0 p-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
        <button
          @click="$emit('close')"
          class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
          aria-label="Close filters"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <!-- Clear All Button -->
      <button
        v-if="hasActiveFilters"
        @click="$emit('clear-all')"
        class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
      >
        Clear all filters
      </button>
    </div>

    <!-- Filters Content -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6">
      
      <!-- Location Filter -->
      <div>
        <h4 class="text-sm font-medium text-gray-900 mb-3">Location</h4>
        
        <!-- User Location Button -->
        <button
          @click="handleUseMyLocation"
          :disabled="geoLoading"
          class="w-full flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg 
            :class="[
              'w-4 h-4 mr-2',
              geoLoading ? 'animate-spin' : ''
            ]" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              v-if="!geoLoading"
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
            />
            <circle v-else cx="12" cy="12" r="3"/>
          </svg>
          {{ geoLoading ? 'Finding location...' : 'Use my location' }}
        </button>

        <!-- Distance Range -->
        <div v-if="userLocation" class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Distance: {{ filters.distance }} miles
          </label>
          <input
            type="range"
            min="1"
            max="50"
            step="1"
            :value="filters.distance"
            @input="$emit('update-filter', 'distance', parseInt($event.target.value))"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
          />
          <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>1 mi</span>
            <span>50 mi</span>
          </div>
        </div>

        <!-- Region Selection -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
          <select
            :value="filters.region"
            @change="$emit('update-filter', 'region', $event.target.value || null)"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All regions</option>
            <optgroup 
              v-for="stateGroup in groupedRegions"
              :key="stateGroup.state"
              :label="stateGroup.state"
            >
              <option
                v-for="region in stateGroup.regions"
                :key="region.id"
                :value="region.id"
              >
                {{ region.name }}
              </option>
            </optgroup>
          </select>
        </div>
      </div>

      <!-- Category Filter -->
      <div>
        <h4 class="text-sm font-medium text-gray-900 mb-3">Category</h4>
        <div class="space-y-2 max-h-48 overflow-y-auto">
          <label class="flex items-center">
            <input
              type="radio"
              name="category"
              :checked="!filters.category"
              @change="$emit('update-filter', 'category', null)"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">All categories</span>
          </label>
          <label
            v-for="category in categories"
            :key="category.id"
            class="flex items-center"
          >
            <input
              type="radio"
              name="category"
              :value="category.id"
              :checked="filters.category === category.id"
              @change="$emit('update-filter', 'category', category.id)"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">
              {{ category.name }}
              <span v-if="category.entries_count" class="text-gray-500">
                ({{ category.entries_count }})
              </span>
            </span>
          </label>
        </div>
      </div>

      <!-- Rating Filter -->
      <div>
        <h4 class="text-sm font-medium text-gray-900 mb-3">Minimum Rating</h4>
        <div class="space-y-2">
          <label
            v-for="rating in [0, 3, 4, 4.5]"
            :key="rating"
            class="flex items-center cursor-pointer"
          >
            <input
              type="radio"
              name="rating"
              :value="rating"
              :checked="filters.rating === rating"
              @change="$emit('update-filter', 'rating', rating)"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
            />
            <div class="ml-3 flex items-center">
              <div v-if="rating === 0" class="text-sm text-gray-700">
                Any rating
              </div>
              <div v-else class="flex items-center">
                <div class="flex text-yellow-400">
                  <svg
                    v-for="i in 5"
                    :key="i"
                    :class="[
                      'w-4 h-4',
                      i <= rating ? 'fill-current' : 'text-gray-300'
                    ]"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
                <span class="ml-1 text-sm text-gray-700">& up</span>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Price Range Filter -->
      <div>
        <h4 class="text-sm font-medium text-gray-900 mb-3">Price Range</h4>
        <div class="space-y-2">
          <label
            v-for="price in priceRanges"
            :key="price.value"
            class="flex items-center cursor-pointer"
          >
            <input
              type="radio"
              name="priceRange"
              :value="price.value"
              :checked="filters.priceRange === price.value"
              @change="$emit('update-filter', 'priceRange', price.value)"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">{{ price.label }}</span>
          </label>
        </div>
      </div>

      <!-- Business Features -->
      <div>
        <h4 class="text-sm font-medium text-gray-900 mb-3">Features</h4>
        <div class="space-y-2">
          <label class="flex items-center cursor-pointer">
            <input
              type="checkbox"
              :checked="filters.openNow"
              @change="$emit('update-filter', 'openNow', $event.target.checked)"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">Open now</span>
          </label>

          <label
            v-for="feature in businessFeatures"
            :key="feature.value"
            class="flex items-center cursor-pointer"
          >
            <input
              type="checkbox"
              :value="feature.value"
              :checked="filters.features?.includes(feature.value)"
              @change="handleFeatureChange(feature.value, $event.target.checked)"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">{{ feature.label }}</span>
          </label>
        </div>
      </div>

      <!-- Status Filters (for authenticated users) -->
      <div v-if="showStatusFilters">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Status</h4>
        <div class="space-y-2">
          <label
            v-for="status in statusOptions"
            :key="status.value"
            class="flex items-center cursor-pointer"
          >
            <input
              type="checkbox"
              :value="status.value"
              :checked="filters.status?.includes(status.value)"
              @change="handleStatusChange(status.value, $event.target.checked)"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <span class="ml-3 text-sm text-gray-700">
              {{ status.label }}
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-1" :class="status.badgeClass">
                {{ status.badge }}
              </span>
            </span>
          </label>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex-shrink-0 p-4 border-t border-gray-200">
      <div class="flex space-x-3">
        <button
          @click="$emit('apply-filters')"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Apply Filters
        </button>
        <button
          @click="$emit('clear-all')"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Clear
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

// Props
const props = defineProps({
  isOpen: {
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
  regions: {
    type: Array,
    default: () => []
  },
  userLocation: {
    type: Object,
    default: null
  },
  showStatusFilters: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits([
  'close',
  'update-filter',
  'clear-all',
  'apply-filters',
  'use-location'
])

// State
const geoLoading = ref(false)

// Price range options
const priceRanges = [
  { value: '', label: 'Any price' },
  { value: '$', label: '$ - Inexpensive' },
  { value: '$$', label: '$$ - Moderate' },
  { value: '$$$', label: '$$$ - Expensive' },
  { value: '$$$$', label: '$$$$ - Very Expensive' }
]

// Business features
const businessFeatures = [
  { value: 'wifi_available', label: 'WiFi' },
  { value: 'accepts_credit_cards', label: 'Credit Cards' },
  { value: 'wheelchair_accessible', label: 'Wheelchair Accessible' },
  { value: 'pet_friendly', label: 'Pet Friendly' },
  { value: 'kid_friendly', label: 'Kid Friendly' },
  { value: 'outdoor_seating', label: 'Outdoor Seating' },
  { value: 'takes_reservations', label: 'Takes Reservations' },
  { value: 'parking_available', label: 'Parking Available' }
]

// Status options (for authenticated users)
const statusOptions = [
  { 
    value: 'featured', 
    label: 'Featured', 
    badge: 'F',
    badgeClass: 'bg-green-100 text-green-800'
  },
  { 
    value: 'verified', 
    label: 'Verified', 
    badge: 'V',
    badgeClass: 'bg-blue-100 text-blue-800'
  },
  { 
    value: 'claimed', 
    label: 'Claimed', 
    badge: 'C',
    badgeClass: 'bg-purple-100 text-purple-800'
  }
]

// Computed
const hasActiveFilters = computed(() => {
  return props.filters.category || 
         props.filters.region ||
         props.filters.rating > 0 || 
         props.filters.priceRange ||
         props.filters.openNow ||
         (props.filters.features && props.filters.features.length > 0) ||
         (props.filters.status && props.filters.status.length > 0)
})

const groupedRegions = computed(() => {
  const grouped = {}
  
  props.regions.forEach(region => {
    let stateName = 'Other'
    
    if (region.type === 'state') {
      stateName = region.name
    } else if (region.parent) {
      stateName = region.parent.name
    } else if (region.stateRegion) {
      stateName = region.stateRegion.name
    }
    
    if (!grouped[stateName]) {
      grouped[stateName] = {
        state: stateName,
        regions: []
      }
    }
    
    grouped[stateName].regions.push(region)
  })
  
  // Sort states and regions within each state
  return Object.values(grouped)
    .map(group => ({
      ...group,
      regions: group.regions.sort((a, b) => a.name.localeCompare(b.name))
    }))
    .sort((a, b) => a.state.localeCompare(b.state))
})

// Methods
const handleUseMyLocation = async () => {
  geoLoading.value = true
  try {
    await emit('use-location')
  } catch (error) {
    console.error('Failed to get location:', error)
  } finally {
    geoLoading.value = false
  }
}

const handleFeatureChange = (feature, checked) => {
  const currentFeatures = props.filters.features || []
  let newFeatures
  
  if (checked) {
    newFeatures = [...currentFeatures, feature]
  } else {
    newFeatures = currentFeatures.filter(f => f !== feature)
  }
  
  emit('update-filter', 'features', newFeatures)
}

const handleStatusChange = (status, checked) => {
  const currentStatus = props.filters.status || []
  let newStatus
  
  if (checked) {
    newStatus = [...currentStatus, status]
  } else {
    newStatus = currentStatus.filter(s => s !== status)
  }
  
  emit('update-filter', 'status', newStatus)
}
</script>

<style scoped>
/* Custom scrollbar for filter sections */
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
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

/* Range slider styling */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-webkit-slider-track {
  height: 8px;
  background: #E5E7EB;
  border-radius: 4px;
}

input[type="range"]::-moz-range-track {
  height: 8px;
  background: #E5E7EB;
  border-radius: 4px;
  border: none;
}

/* Focus styles for accessibility */
input[type="radio"]:focus,
input[type="checkbox"]:focus {
  @apply ring-2 ring-offset-2 ring-blue-500;
}

button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .w-80 {
    @apply w-full;
  }
}
</style>