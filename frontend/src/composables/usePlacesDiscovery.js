import { ref, reactive, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import { useNotification } from './useNotification'
import { debounce } from 'lodash'

/**
 * Places Discovery Composable
 * Manages places data fetching, filtering, search, and state management
 */
export function usePlacesDiscovery(options = {}) {
  const { showError } = useNotification()

  // Core state
  const places = ref([])
  const filteredPlaces = ref([])
  const categories = ref([])
  const regions = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const hasMore = ref(true)
  const currentPage = ref(1)
  const total = ref(0)

  // Filter state
  const filters = reactive({
    search: '',
    category: null,
    region: null,
    distance: 25, // miles
    openNow: false,
    rating: 0,
    priceRange: '',
    features: [],
    bounds: null,
    userLocation: null
  })

  // Sort options
  const sortOptions = [
    { value: 'relevance', label: 'Relevance' },
    { value: 'distance', label: 'Distance' },
    { value: 'rating', label: 'Highest Rated' },
    { value: 'newest', label: 'Newest' },
    { value: 'name', label: 'Name A-Z' }
  ]
  const currentSort = ref('relevance')

  // View state
  const viewMode = ref('grid') // 'grid' | 'list' | 'map'
  const selectedPlace = ref(null)
  const hoveredPlace = ref(null)

  // Computed
  const hasFilters = computed(() => {
    return filters.search || 
           filters.category || 
           filters.region ||
           filters.openNow ||
           filters.rating > 0 ||
           filters.priceRange ||
           filters.features.length > 0
  })

  const activeFiltersCount = computed(() => {
    let count = 0
    if (filters.search) count++
    if (filters.category) count++
    if (filters.region) count++
    if (filters.openNow) count++
    if (filters.rating > 0) count++
    if (filters.priceRange) count++
    if (filters.features.length > 0) count += filters.features.length
    return count
  })

  // API endpoints
  const API_BASE = '/api'

  /**
   * Fetch places with current filters
   */
  const fetchPlaces = async (reset = false) => {
    if (isLoading.value && !reset) return

    try {
      isLoading.value = true
      error.value = null

      if (reset) {
        currentPage.value = 1
        places.value = []
      }

      const params = new URLSearchParams()
      
      // Basic filters
      if (filters.search) params.append('q', filters.search)
      if (filters.category) params.append('category_id', filters.category)
      if (filters.region) params.append('region_id', filters.region)
      if (filters.openNow) params.append('open_now', '1')
      if (filters.rating > 0) params.append('min_rating', filters.rating)
      if (filters.priceRange) params.append('price_range', filters.priceRange)
      
      // Feature filters
      filters.features.forEach(feature => {
        params.append('features[]', feature)
      })
      
      // Location-based filters
      if (filters.userLocation && filters.distance) {
        params.append('lat', filters.userLocation.lat)
        params.append('lng', filters.userLocation.lng)
        params.append('radius', filters.distance)
      } else if (filters.bounds) {
        params.append('bounds', `${filters.bounds.north},${filters.bounds.south},${filters.bounds.east},${filters.bounds.west}`)
      }
      
      // Sorting
      params.append('sort', currentSort.value)
      
      // Pagination
      params.append('page', currentPage.value)
      params.append('per_page', 20)

      const response = await axios.get(`${API_BASE}/places?${params.toString()}`)
      const { data } = response

      if (reset) {
        places.value = data.entries?.data || data.data || []
      } else {
        places.value.push(...(data.entries?.data || data.data || []))
      }

      // Handle pagination metadata
      const pagination = data.entries || data
      total.value = pagination.total || 0
      hasMore.value = pagination.current_page < pagination.last_page
      
      if (!reset) {
        currentPage.value++
      }

      // Apply local filtering and sorting
      applyLocalFilters()

    } catch (err) {
      console.error('Error fetching places:', err)
      error.value = err.response?.data?.message || 'Failed to load places'
      showError('Error', 'Failed to load places. Please try again.')
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch nearby places based on coordinates
   */
  const fetchNearbyPlaces = async (lat, lng, radius = 25) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await axios.get(`${API_BASE}/places/nearby`, {
        params: {
          lat,
          lng,
          radius,
          sort: 'distance'
        }
      })

      places.value = response.data.entries || []
      total.value = response.data.total || 0
      applyLocalFilters()

    } catch (err) {
      console.error('Error fetching nearby places:', err)   
      error.value = err.response?.data?.message || 'Failed to load nearby places'
      showError('Error', 'Failed to load nearby places')
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Search places with autocomplete suggestions
   */
  const searchPlaces = debounce(async (query) => {
    if (!query || query.length < 2) {
      filteredPlaces.value = places.value
      return []
    }

    try {
      const response = await axios.get(`${API_BASE}/places/search`, {
        params: {
          q: query,
          limit: 10,
          suggest: true
        }
      })

      return response.data.suggestions || []
    } catch (err) {
      console.error('Error searching places:', err)
      return []
    }
  }, 300)

  /**
   * Fetch categories for filtering
   */
  const fetchCategories = async () => {
    try {
      const response = await axios.get(`${API_BASE}/categories`)
      categories.value = response.data.categories || response.data || []
    } catch (err) {
      console.error('Error fetching categories:', err)
    }
  }

  /**
   * Fetch regions for location filtering  
   */
  const fetchRegions = async (type = null) => {
    try {
      const params = new URLSearchParams()
      if (type) params.append('type', type)
      
      const response = await axios.get(`${API_BASE}/regions?${params.toString()}`)
      regions.value = response.data.regions || response.data || []
    } catch (err) {
      console.error('Error fetching regions:', err)
    }
  }

  /**
   * Get place details by ID
   */
  const fetchPlaceDetails = async (placeId) => {
    try {
      const response = await axios.get(`${API_BASE}/places/${placeId}`)
      return response.data.entry || response.data
    } catch (err) {
      console.error('Error fetching place details:', err)
      throw err
    }
  }

  /**
   * Apply local filters to the places array
   */
  const applyLocalFilters = () => {
    let filtered = [...places.value]

    // Text search (if not handled by API)
    if (filters.search && !isLoading.value) {
      const searchTerm = filters.search.toLowerCase()
      filtered = filtered.filter(place => 
        place.name?.toLowerCase().includes(searchTerm) ||
        place.title?.toLowerCase().includes(searchTerm) ||
        place.description?.toLowerCase().includes(searchTerm) ||
        place.category?.name?.toLowerCase().includes(searchTerm)
      )
    }

    // Sort places
    filtered = sortPlaces(filtered)

    filteredPlaces.value = filtered
  }

  /**
   * Sort places based on current sort option
   */
  const sortPlaces = (placesToSort) => {
    const sorted = [...placesToSort]

    switch (currentSort.value) {
      case 'distance':
        return sorted.sort((a, b) => {
          const distanceA = a.distance_miles || a.distance || 0
          const distanceB = b.distance_miles || b.distance || 0
          return distanceA - distanceB
        })

      case 'rating':
        return sorted.sort((a, b) => {
          const ratingA = a.average_rating || 0
          const ratingB = b.average_rating || 0
          return ratingB - ratingA
        })

      case 'newest':
        return sorted.sort((a, b) => {
          const dateA = new Date(a.created_at || 0)
          const dateB = new Date(b.created_at || 0)
          return dateB - dateA
        })

      case 'name':
        return sorted.sort((a, b) => {
          const nameA = (a.name || a.title || '').toLowerCase()
          const nameB = (b.name || b.title || '').toLowerCase()
          return nameA.localeCompare(nameB)
        })

      case 'relevance':
      default:
        // Keep original order (relevance from API)
        return sorted
    }
  }

  /**
   * Update filter and refresh results
   */
  const updateFilter = (key, value) => {
    filters[key] = value
    fetchPlaces(true)
  }

  /**
   * Update multiple filters at once
   */
  const updateFilters = (newFilters) => {
    Object.assign(filters, newFilters)
    fetchPlaces(true)
  }

  /**
   * Clear all filters
   */
  const clearFilters = () => {
    Object.assign(filters, {
      search: '',
      category: null,
      region: null,
      distance: 25,
      openNow: false,
      rating: 0,
      priceRange: '',
      features: [],
      bounds: null
      // Keep userLocation
    })
    currentSort.value = 'relevance'
    fetchPlaces(true)
  }

  /**
   * Update user location for distance-based filtering
   */
  const updateUserLocation = (location) => {
    filters.userLocation = location
    if (location) {
      fetchNearbyPlaces(location.lat, location.lng, filters.distance)
    }
  }

  /**
   * Update map bounds for viewport-based filtering
   */
  const updateBounds = (bounds) => {
    filters.bounds = bounds
    // Debounced bounds update to avoid too many API calls
    debouncedBoundsUpdate()
  }

  const debouncedBoundsUpdate = debounce(() => {
    if (filters.bounds) {
      fetchPlaces(true)
    }
  }, 500)

  /**
   * Load more places (infinite scroll)
   */
  const loadMore = () => {
    if (!isLoading.value && hasMore.value) {
      fetchPlaces(false)
    }
  }

  /**
   * Refresh places data
   */
  const refresh = () => {
    fetchPlaces(true)
  }

  /**
   * Get places within a specific category
   */
  const getPlacesByCategory = (categoryId) => {
    return filteredPlaces.value.filter(place => 
      place.category_id === categoryId ||
      place.category?.id === categoryId
    )
  }

  /**
   * Get places within distance from a point
   */
  const getPlacesWithinDistance = (lat, lng, maxDistance) => {
    return filteredPlaces.value.filter(place => {
      if (!place.latitude || !place.longitude) return false
      
      const distance = calculateDistance(
        lat, lng,
        parseFloat(place.latitude),
        parseFloat(place.longitude)
      )
      
      return distance <= maxDistance
    })
  }

  /**
   * Calculate distance between two points (Haversine formula)
   */
  const calculateDistance = (lat1, lng1, lat2, lng2) => {
    const R = 3959 // Earth's radius in miles
    const dLat = toRadians(lat2 - lat1)
    const dLng = toRadians(lng2 - lng1)
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2)
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c
  }

  const toRadians = (degrees) => degrees * (Math.PI / 180)

  // Watch for filter changes
  watch(() => filters.search, (newSearch) => {
    if (newSearch !== filters.search) {
      fetchPlaces(true)
    }
  })

  watch(currentSort, () => {
    applyLocalFilters()
  })

  // Initialize
  const initialize = async () => {
    await Promise.all([
      fetchCategories(),
      fetchRegions(),
      fetchPlaces(true)
    ])
  }

  return {
    // State
    places,
    filteredPlaces,
    categories,
    regions,
    isLoading,
    error,
    hasMore,
    currentPage,
    total,
    filters,
    sortOptions,
    currentSort,
    viewMode,
    selectedPlace,
    hoveredPlace,

    // Computed
    hasFilters,
    activeFiltersCount,
    
    // Methods
    fetchPlaces,
    fetchNearbyPlaces,
    searchPlaces,
    fetchCategories,
    fetchRegions,
    fetchPlaceDetails,
    updateFilter,
    updateFilters,
    clearFilters,
    updateUserLocation,
    updateBounds,
    loadMore,
    refresh,
    getPlacesByCategory,
    getPlacesWithinDistance,
    initialize
  }
}