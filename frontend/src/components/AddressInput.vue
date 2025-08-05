<template>
  <div class="space-y-4">
    <!-- Address Line 1 -->
    <div>
      <label for="address" class="block text-sm font-medium text-gray-700">
        Address
      </label>
      <input
        id="address"
        v-model="localAddress.address"
        type="text"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        :class="{ 'border-red-300': errors.address }"
        placeholder="123 Main Street"
        @blur="handleAddressChange"
      />
      <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address }}</p>
    </div>

    <!-- City and State Row -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label for="city" class="block text-sm font-medium text-gray-700">
          City
        </label>
        <input
          id="city"
          v-model="localAddress.city"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          :class="{ 'border-red-300': errors.city }"
          placeholder="San Francisco"
          @blur="handleAddressChange"
        />
        <p v-if="errors.city" class="mt-1 text-sm text-red-600">{{ errors.city }}</p>
      </div>

      <div>
        <label for="state" class="block text-sm font-medium text-gray-700">
          State
        </label>
        <input
          id="state"
          v-model="localAddress.state"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          :class="{ 'border-red-300': errors.state }"
          placeholder="CA"
          maxlength="2"
          @blur="handleAddressChange"
        />
        <p v-if="errors.state" class="mt-1 text-sm text-red-600">{{ errors.state }}</p>
      </div>
    </div>

    <!-- Coordinates Row -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label for="latitude" class="block text-sm font-medium text-gray-700">
          Latitude
        </label>
        <div class="mt-1 flex rounded-md shadow-sm">
          <input
            id="latitude"
            v-model.number="localAddress.latitude"
            type="number"
            step="0.0000001"
            class="block w-full rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            :class="{ 'border-red-300': errors.latitude }"
            placeholder="37.7749"
            :readonly="isGeocoding"
          />
          <button
            type="button"
            @click="geocodeAddress"
            :disabled="isGeocoding || !canGeocode"
            class="inline-flex items-center px-3 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 sm:text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Geocode address"
          >
            <svg v-if="!isGeocoding" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>
        <p v-if="errors.latitude" class="mt-1 text-sm text-red-600">{{ errors.latitude }}</p>
      </div>

      <div>
        <label for="longitude" class="block text-sm font-medium text-gray-700">
          Longitude
        </label>
        <input
          id="longitude"
          v-model.number="localAddress.longitude"
          type="number"
          step="0.0000001"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          :class="{ 'border-red-300': errors.longitude }"
          placeholder="-122.4194"
          :readonly="isGeocoding"
        />
        <p v-if="errors.longitude" class="mt-1 text-sm text-red-600">{{ errors.longitude }}</p>
      </div>
    </div>

    <!-- Validation Status -->
    <div v-if="validationStatus" class="rounded-md p-4" :class="validationStatusClass">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg v-if="validationStatus.valid" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium" :class="validationStatus.valid ? 'text-green-800' : 'text-yellow-800'">
            {{ validationStatus.valid ? 'Address Validated' : 'Address Validation Warning' }}
          </h3>
          <div class="mt-2 text-sm" :class="validationStatus.valid ? 'text-green-700' : 'text-yellow-700'">
            <p v-if="validationStatus.confidence">
              Confidence: {{ Math.round(validationStatus.confidence * 100) }}%
            </p>
            <p v-if="validationStatus.formatted_address" class="mt-1">
              Suggested: {{ validationStatus.formatted_address }}
            </p>
            <div v-if="validationStatus.errors?.length" class="mt-2">
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="error in validationStatus.errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Map Preview -->
    <div v-if="showMapPreview && hasCoordinates" class="mt-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Location Preview
      </label>
      <div ref="mapContainer" class="h-64 rounded-md border border-gray-300"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      address: '',
      city: '',
      state: '',
      latitude: null,
      longitude: null
    })
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  autoGeocode: {
    type: Boolean,
    default: true
  },
  showMapPreview: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'validation-complete'])

// Local state
const localAddress = ref({ ...props.modelValue })
const isGeocoding = ref(false)
const validationStatus = ref(null)
const mapContainer = ref(null)
const map = ref(null)
const marker = ref(null)

// Computed
const canGeocode = computed(() => {
  return localAddress.value.address || 
         (localAddress.value.city && localAddress.value.state)
})

const hasCoordinates = computed(() => {
  return localAddress.value.latitude && localAddress.value.longitude
})

const validationStatusClass = computed(() => {
  if (!validationStatus.value) return ''
  return validationStatus.value.valid 
    ? 'bg-green-50 border border-green-200' 
    : 'bg-yellow-50 border border-yellow-200'
})

// Methods
const geocodeAddress = async () => {
  if (!canGeocode.value || isGeocoding.value) return
  
  isGeocoding.value = true
  validationStatus.value = null
  
  try {
    const addressString = [
      localAddress.value.address,
      localAddress.value.city,
      localAddress.value.state,
      'USA'
    ].filter(Boolean).join(', ')
    
    const response = await axios.post('/api/geocoding/geocode', {
      address: addressString,
      country: 'us'
    })
    
    if (response.data.success && response.data.data) {
      const { coordinates, components, confidence, formatted_address } = response.data.data
      
      // Update coordinates
      localAddress.value.latitude = coordinates.lat
      localAddress.value.longitude = coordinates.lng
      
      // Update address components if high confidence
      if (confidence >= 0.8) {
        if (components.city && !localAddress.value.city) {
          localAddress.value.city = components.city
        }
        if (components.state && !localAddress.value.state) {
          localAddress.value.state = components.state
        }
      }
      
      validationStatus.value = {
        valid: confidence >= 0.8,
        confidence,
        formatted_address,
        errors: confidence < 0.8 ? ['Low confidence match - please verify address'] : []
      }
      
      emitUpdate()
      updateMapMarker()
    }
  } catch (error) {
    console.error('Geocoding error:', error)
    validationStatus.value = {
      valid: false,
      errors: ['Unable to geocode address']
    }
  } finally {
    isGeocoding.value = false
  }
}

const validateAddress = debounce(async () => {
  if (!canGeocode.value || !props.autoGeocode) return
  
  try {
    const response = await axios.post('/api/geocoding/validate', {
      address_line1: localAddress.value.address,
      city: localAddress.value.city,
      state: localAddress.value.state,
      country: 'US'
    })
    
    validationStatus.value = response.data
    
    // Auto-update coordinates if valid
    if (response.data.valid && response.data.coordinates) {
      localAddress.value.latitude = response.data.coordinates.lat
      localAddress.value.longitude = response.data.coordinates.lng
      updateMapMarker()
    }
    
    emit('validation-complete', response.data)
  } catch (error) {
    console.error('Validation error:', error)
  }
}, 1000)

const handleAddressChange = () => {
  emitUpdate()
  if (props.autoGeocode) {
    validateAddress()
  }
}

const emitUpdate = () => {
  emit('update:modelValue', { ...localAddress.value })
}

const initializeMap = async () => {
  if (!mapContainer.value || !window.mapboxgl) return
  
  await nextTick()
  
  try {
    window.mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN
    
    map.value = new window.mapboxgl.Map({
      container: mapContainer.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [-98.5795, 39.8283], // Center of USA
      zoom: 3
    })
    
    marker.value = new window.mapboxgl.Marker({
      draggable: true
    })
    
    // Handle marker drag
    marker.value.on('dragend', () => {
      const lngLat = marker.value.getLngLat()
      localAddress.value.latitude = lngLat.lat
      localAddress.value.longitude = lngLat.lng
      emitUpdate()
    })
    
    if (hasCoordinates.value) {
      updateMapMarker()
    }
  } catch (error) {
    console.error('Map initialization error:', error)
  }
}

const updateMapMarker = () => {
  if (!map.value || !marker.value || !hasCoordinates.value) return
  
  const lngLat = [localAddress.value.longitude, localAddress.value.latitude]
  
  marker.value
    .setLngLat(lngLat)
    .addTo(map.value)
  
  map.value.flyTo({
    center: lngLat,
    zoom: 15,
    essential: true
  })
}

// Watchers
watch(() => props.modelValue, (newVal) => {
  localAddress.value = { ...newVal }
  if (hasCoordinates.value) {
    updateMapMarker()
  }
}, { deep: true })

watch(hasCoordinates, (hasCoords) => {
  if (hasCoords && map.value) {
    updateMapMarker()
  }
})

// Lifecycle
onMounted(() => {
  if (props.showMapPreview && window.mapboxgl) {
    initializeMap()
  }
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})
</script>