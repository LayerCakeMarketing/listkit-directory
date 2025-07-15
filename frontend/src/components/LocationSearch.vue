<template>
    <div class="relative">
        <div class="relative">
            <input
                v-model="searchQuery"
                @input="handleSearch"
                @focus="showDropdown = true"
                @blur="handleBlur"
                type="text"
                :placeholder="placeholder"
                class="w-full px-4 py-2 pl-10 pr-4 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
            />
            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div v-if="loading" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown Results -->
        <div v-if="showDropdown && (searchResults.length > 0 || searchQuery.length > 0)" 
             class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto">
            
            <!-- Current Location Option -->
            <div v-if="detectingLocation" class="p-3 text-sm text-gray-500 flex items-center">
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Detecting your location...
            </div>
            
            <button v-else-if="!searchQuery && currentLocation"
                    @mousedown.prevent="selectCurrentLocation"
                    class="w-full p-3 text-left hover:bg-gray-50 flex items-center">
                <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <div>
                    <div class="font-medium text-gray-900">Use current location</div>
                    <div v-if="detectedLocation" class="text-sm text-gray-500">
                        {{ formatLocation(detectedLocation) }}
                    </div>
                </div>
            </button>

            <!-- Search Results -->
            <div v-if="searchResults.length > 0">
                <div v-if="!searchQuery" class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Popular Locations
                </div>
                <button v-for="region in searchResults" 
                        :key="region.id"
                        @mousedown.prevent="selectRegion(region)"
                        class="w-full p-3 text-left hover:bg-gray-50 flex items-center border-t border-gray-100">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">{{ region.name }}</div>
                        <div class="text-sm text-gray-500">
                            {{ getRegionHierarchy(region) }}
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 ml-2">{{ getRegionTypeLabel(region.level) }}</span>
                </button>
            </div>

            <!-- No Results -->
            <div v-else-if="searchQuery && !loading" class="p-4 text-center text-gray-500">
                No locations found matching "{{ searchQuery }}"
            </div>

            <!-- Browse All Option -->
            <button v-if="showBrowseAll"
                    @mousedown.prevent="$emit('browse-all')"
                    class="w-full p-3 text-center text-blue-600 hover:bg-blue-50 border-t border-gray-200 font-medium">
                Browse all locations
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

const props = defineProps({
    modelValue: Object,
    placeholder: {
        type: String,
        default: 'Search for a city, neighborhood, or state...'
    },
    showBrowseAll: {
        type: Boolean,
        default: false
    },
    autoDetect: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'browse-all'])

// State
const searchQuery = ref('')
const searchResults = ref([])
const showDropdown = ref(false)
const loading = ref(false)
const detectingLocation = ref(false)
const currentLocation = ref(null)
const detectedLocation = ref(null)

// Popular locations (can be customized based on your data)
const popularLocations = ref([])

// Methods
const handleSearch = debounce(async () => {
    if (!searchQuery.value) {
        // Show popular locations when search is empty
        loadPopularLocations()
        return
    }

    loading.value = true
    try {
        const response = await axios.get('/api/regions/search', {
            params: { q: searchQuery.value }
        })
        searchResults.value = response.data.data || []
    } catch (error) {
        console.error('Error searching regions:', error)
        searchResults.value = []
    } finally {
        loading.value = false
    }
}, 300)

const loadPopularLocations = async () => {
    try {
        const response = await axios.get('/api/regions/popular')
        popularLocations.value = response.data.data || []
        searchResults.value = popularLocations.value
    } catch (error) {
        console.error('Error loading popular locations:', error)
    }
}

const detectCurrentLocation = async () => {
    if (!props.autoDetect) return
    
    detectingLocation.value = true
    try {
        const response = await axios.get('/api/location/detect')
        if (response.data.location) {
            detectedLocation.value = response.data.location
            currentLocation.value = response.data.location
        }
    } catch (error) {
        console.error('Error detecting location:', error)
    } finally {
        detectingLocation.value = false
    }
}

const selectRegion = (region) => {
    emit('update:modelValue', region)
    emit('change', region)
    searchQuery.value = formatLocation(region)
    showDropdown.value = false
    
    // Update session location
    axios.post('/api/places/set-location', { region_id: region.id })
}

const selectCurrentLocation = () => {
    if (detectedLocation.value) {
        selectRegion(detectedLocation.value)
    }
}

const handleBlur = () => {
    // Delay hiding dropdown to allow click events to fire
    setTimeout(() => {
        showDropdown.value = false
    }, 200)
}

const formatLocation = (region) => {
    if (!region) return ''
    
    const parts = []
    if (region.level === 3) { // Neighborhood
        parts.push(region.name)
        if (region.parent) parts.push(region.parent.name)
    } else if (region.level === 2) { // City
        parts.push(region.name)
        if (region.parent) parts.push(region.parent.name)
    } else { // State
        parts.push(region.name)
    }
    
    return parts.join(', ')
}

const getRegionHierarchy = (region) => {
    const parts = []
    
    if (region.level === 3 && region.parent) { // Neighborhood
        parts.push(region.parent.name)
        if (region.parent.parent) parts.push(region.parent.parent.name)
    } else if (region.level === 2 && region.parent) { // City
        parts.push(region.parent.name)
    }
    
    return parts.join(', ')
}

const getRegionTypeLabel = (level) => {
    switch (level) {
        case 1: return 'State'
        case 2: return 'City'
        case 3: return 'Neighborhood'
        default: return ''
    }
}

// Initialize
onMounted(() => {
    if (props.modelValue) {
        searchQuery.value = formatLocation(props.modelValue)
    }
    detectCurrentLocation()
    loadPopularLocations()
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        searchQuery.value = formatLocation(newValue)
    }
})
</script>