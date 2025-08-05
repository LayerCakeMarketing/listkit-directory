<template>
  <div class="relative">
    <div class="relative">
      <input
        ref="inputRef"
        v-model="searchQuery"
        @input="handleInput"
        @focus="showSuggestions = true"
        @blur="handleBlur"
        @keydown="handleKeydown"
        :placeholder="placeholder"
        :class="inputClass"
        type="text"
        autocomplete="off"
      />
      
      <!-- Loading indicator -->
      <div v-if="isSearching" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      
      <!-- Clear button -->
      <button
        v-else-if="searchQuery && !isSearching"
        @click="clearInput"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3"
      >
        <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Suggestions dropdown -->
    <div
      v-if="showSuggestions && suggestions.length > 0"
      class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
    >
      <div
        v-for="(suggestion, index) in suggestions"
        :key="suggestion.id"
        @click="selectSuggestion(suggestion)"
        @mouseenter="highlightedIndex = index"
        :class="[
          'cursor-pointer select-none relative py-2 pl-3 pr-9',
          highlightedIndex === index ? 'text-white bg-indigo-600' : 'text-gray-900 hover:bg-gray-50'
        ]"
      >
        <div class="flex items-start">
          <svg 
            class="flex-shrink-0 h-5 w-5 mr-2 mt-0.5"
            :class="highlightedIndex === index ? 'text-white' : 'text-gray-400'"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <div class="flex-1">
            <span class="block truncate font-medium">
              {{ suggestion.text }}
            </span>
            <span 
              class="block truncate text-sm"
              :class="highlightedIndex === index ? 'text-indigo-200' : 'text-gray-500'"
            >
              {{ suggestion.place_name }}
            </span>
          </div>
        </div>
        
        <!-- Checkmark for selected item -->
        <span 
          v-if="isSelected(suggestion)"
          class="absolute inset-y-0 right-0 flex items-center pr-4"
          :class="highlightedIndex === index ? 'text-white' : 'text-indigo-600'"
        >
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </span>
      </div>
    </div>

    <!-- Error message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    
    <!-- Help text -->
    <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">{{ helpText }}</p>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import debounce from 'lodash/debounce'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Enter city, state or zip code'
  },
  inputClass: {
    type: String,
    default: 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
  },
  types: {
    type: Array,
    default: () => ['place', 'region', 'postcode', 'locality', 'neighborhood']
  },
  country: {
    type: String,
    default: 'US'
  },
  proximity: {
    type: String,
    default: null // Can be 'ip' or coordinates like '-74.70850,40.78375'
  },
  bbox: {
    type: Array,
    default: null // Bounding box [minLon, minLat, maxLon, maxLat]
  },
  limit: {
    type: Number,
    default: 5
  },
  helpText: {
    type: String,
    default: ''
  },
  returnFullData: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'place-selected', 'error'])

// State
const searchQuery = ref('')
const suggestions = ref([])
const showSuggestions = ref(false)
const isSearching = ref(false)
const highlightedIndex = ref(-1)
const error = ref('')
const selectedPlace = ref(null)
const inputRef = ref(null)

// Get Mapbox token from environment
const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN

// Initialize search query with model value
onMounted(() => {
  searchQuery.value = props.modelValue || ''
  
  // Debug: Check if token is loaded
  if (!mapboxToken) {
    console.warn('Mapbox token not found. Make sure VITE_MAPBOX_TOKEN is set in your .env file and restart the Vite dev server.')
  }
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue !== searchQuery.value) {
    searchQuery.value = newValue || ''
  }
})

// Mapbox Geocoding API search
const searchPlaces = async (query) => {
  if (!query || query.length < 2) {
    suggestions.value = []
    return
  }

  if (!mapboxToken) {
    error.value = 'Mapbox API key not configured'
    return
  }

  isSearching.value = true
  error.value = ''

  try {
    const params = new URLSearchParams({
      access_token: mapboxToken,
      types: props.types.join(','),
      country: props.country,
      limit: props.limit,
      autocomplete: true
    })

    if (props.proximity) {
      params.append('proximity', props.proximity)
    }

    if (props.bbox && props.bbox.length === 4) {
      params.append('bbox', props.bbox.join(','))
    }

    const response = await fetch(
      `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?${params}`
    )

    if (!response.ok) {
      throw new Error('Failed to fetch suggestions')
    }

    const data = await response.json()
    
    // Transform Mapbox results to our format
    suggestions.value = data.features.map(feature => ({
      id: feature.id,
      text: feature.text,
      place_name: feature.place_name,
      center: feature.center,
      bbox: feature.bbox,
      properties: feature.properties,
      context: feature.context,
      place_type: feature.place_type,
      // Extract useful context
      city: extractContext(feature, 'place'),
      state: extractContext(feature, 'region'),
      postcode: extractContext(feature, 'postcode'),
      country: extractContext(feature, 'country')
    }))
  } catch (err) {
    console.error('Geocoding error:', err)
    error.value = 'Failed to fetch location suggestions'
    emit('error', err)
  } finally {
    isSearching.value = false
  }
}

// Extract context from Mapbox response
const extractContext = (feature, type) => {
  if (!feature.context) return null
  const context = feature.context.find(c => c.id.startsWith(type))
  return context ? context.text : null
}

// Debounced search
const debouncedSearch = debounce(searchPlaces, 300)

// Handle input changes
const handleInput = (event) => {
  const value = event.target.value
  searchQuery.value = value
  emit('update:modelValue', value)
  
  if (value.length >= 2) {
    debouncedSearch(value)
    showSuggestions.value = true
  } else {
    suggestions.value = []
  }
}

// Select a suggestion
const selectSuggestion = (suggestion) => {
  // Format the location string based on what's available
  let formattedLocation = ''
  
  if (suggestion.place_type.includes('postcode')) {
    // For zip codes, show the full place name
    formattedLocation = suggestion.place_name
  } else {
    // For other types, build a clean city, state format
    const parts = []
    
    // Add city
    if (suggestion.text) {
      parts.push(suggestion.text)
    } else if (suggestion.city) {
      parts.push(suggestion.city)
    }
    
    // Add state
    if (suggestion.state) {
      parts.push(suggestion.state)
    }
    
    // Add country if not US
    if (suggestion.country && suggestion.country !== 'United States') {
      parts.push(suggestion.country)
    }
    
    formattedLocation = parts.join(', ')
  }
  
  searchQuery.value = formattedLocation
  emit('update:modelValue', formattedLocation)
  
  // Emit the full place data
  if (props.returnFullData) {
    emit('place-selected', {
      formatted: formattedLocation,
      raw: suggestion,
      coordinates: suggestion.center ? {
        lng: suggestion.center[0],
        lat: suggestion.center[1]
      } : null,
      city: suggestion.city || suggestion.text,
      state: suggestion.state,
      postcode: suggestion.postcode,
      country: suggestion.country
    })
  } else {
    emit('place-selected', formattedLocation)
  }
  
  selectedPlace.value = suggestion
  showSuggestions.value = false
  suggestions.value = []
}

// Clear input
const clearInput = () => {
  searchQuery.value = ''
  emit('update:modelValue', '')
  suggestions.value = []
  selectedPlace.value = null
  error.value = ''
  inputRef.value?.focus()
}

// Handle blur
const handleBlur = () => {
  // Delay to allow click on suggestion
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Keyboard navigation
const handleKeydown = (event) => {
  if (!showSuggestions.value || suggestions.value.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, suggestions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0) {
        selectSuggestion(suggestions.value[highlightedIndex.value])
      }
      break
    case 'Escape':
      showSuggestions.value = false
      highlightedIndex.value = -1
      break
  }
}

// Check if suggestion is selected
const isSelected = (suggestion) => {
  return selectedPlace.value?.id === suggestion.id
}
</script>