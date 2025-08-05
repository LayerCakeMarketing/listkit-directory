import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'
import { useNotification } from './useNotification'
import { debounce } from 'lodash'

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

/**
 * Map-specific places composable
 * Fetches places in GeoJSON format for Mapbox display
 */
export function useMapPlaces() {
  const { showError } = useNotification()

  // State
  const places = ref([])
  const features = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const total = ref(0)
  const bounds = ref(null)
  const zoom = ref(10)

  // Filters
  const filters = reactive({
    category_id: null,
    type: null,
    search: ''
  })

  /**
   * Fetch places for map display
   */
  const fetchMapData = async () => {
    if (!bounds.value) return
    
    try {
      isLoading.value = true
      error.value = null

      const params = new URLSearchParams({
        'bounds[north]': bounds.value.north,
        'bounds[south]': bounds.value.south,
        'bounds[east]': bounds.value.east,
        'bounds[west]': bounds.value.west,
        zoom: zoom.value
      })

      // Add filters if present
      if (filters.category_id) params.append('category_id', filters.category_id)
      if (filters.type) params.append('type', filters.type)
      if (filters.search) params.append('search', filters.search)

      // Use relative URL if baseURL is already set
      const url = axios.defaults.baseURL ? '/api/places/map-data' : `${API_BASE}/places/map-data`
      const response = await axios.get(`${url}?${params.toString()}`)
      const data = response.data

      // Store both raw places and GeoJSON features
      features.value = data.features || []
      total.value = data.total || 0
      
      // Extract places from features for list view
      places.value = features.value.map(feature => ({
        id: feature.properties.id,
        title: feature.properties.title,
        slug: feature.properties.slug,
        category: feature.properties.category,
        address: feature.properties.address,
        city: feature.properties.city,
        state: feature.properties.state,
        logo_url: feature.properties.logo_url,
        cover_image_url: feature.properties.cover_image_url,
        is_featured: feature.properties.is_featured,
        latitude: feature.geometry.coordinates[1],
        longitude: feature.geometry.coordinates[0],
        canonical_url: feature.properties.canonical_url || `/places/${feature.properties.slug}`
      }))

    } catch (err) {
      console.error('Error fetching map data:', err)
      error.value = err.response?.data?.message || 'Failed to load map data'
      showError('Map Error', error.value)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update map bounds
   */
  const updateBounds = (newBounds, newZoom = null) => {
    bounds.value = newBounds
    if (newZoom !== null) zoom.value = newZoom
    debouncedFetch()
  }

  /**
   * Update filter
   */
  const updateFilter = (key, value) => {
    filters[key] = value
    if (bounds.value) {
      debouncedFetch()
    }
  }

  /**
   * Clear all filters
   */
  const clearFilters = () => {
    filters.category_id = null
    filters.type = null
    filters.search = ''
    if (bounds.value) {
      fetchMapData()
    }
  }

  // Debounced fetch to avoid too many API calls
  const debouncedFetch = debounce(fetchMapData, 500)

  /**
   * Get GeoJSON for map
   */
  const getGeoJSON = computed(() => ({
    type: 'FeatureCollection',
    features: features.value
  }))

  return {
    // State
    places,
    features,
    isLoading,
    error,
    total,
    bounds,
    zoom,
    filters,
    
    // Computed
    getGeoJSON,
    
    // Methods
    fetchMapData,
    updateBounds,
    updateFilter,
    clearFilters
  }
}