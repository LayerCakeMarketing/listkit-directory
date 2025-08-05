import { ref, onMounted, onUnmounted, nextTick } from 'vue'

/**
 * Mapbox GL JS Composable
 * Provides reactive map state management and utilities for Mapbox integration
 */
export function useMapbox(options = {}) {
  const map = ref(null)
  const mapContainer = ref(null)
  const isLoaded = ref(false)
  const isLoading = ref(false)
  const error = ref(null)
  const userLocation = ref(null)
  const markers = ref(new Map())
  const clusteredMarkers = ref(new Map())
  const popups = ref(new Map())

  // Default configuration
  const defaultConfig = {
    style: 'mapbox://styles/mapbox/light-v11',
    center: [-74.006, 40.7128], // NYC default
    zoom: 12,
    bearing: 0,
    pitch: 0,
    accessToken: import.meta.env.VITE_MAPBOX_TOKEN || import.meta.env.VITE_MAPBOX_ACCESS_TOKEN || 'pk.test',
    ...options
  }

  /**
   * Initialize the Mapbox map
   */
  const initializeMap = async (container, customOptions = {}) => {
    console.log('🌍 useMapbox initializeMap called with:', { container: !!container, customOptions })
    
    if (!container) {
      error.value = 'Map container element is required'
      return false
    }

    if (!defaultConfig.accessToken || defaultConfig.accessToken === 'pk.test') {
      error.value = 'Mapbox access token is required. Please set VITE_MAPBOX_ACCESS_TOKEN in your .env file.'
      console.error('Mapbox token missing. Add to .env: VITE_MAPBOX_ACCESS_TOKEN=your_token_here')
      return false
    }

    try {
      isLoading.value = true
      error.value = null

      // Check if Mapbox GL JS is loaded from CDN
      if (typeof window.mapboxgl === 'undefined') {
        error.value = 'Mapbox GL JS is not loaded. Please check your internet connection.'
        console.error('Mapbox GL JS not found in window object')
        return false
      }
      const mapboxgl = window.mapboxgl

      mapboxgl.accessToken = defaultConfig.accessToken

      // Merge custom options with defaults
      const mapOptions = {
        container,
        style: defaultConfig.style,
        center: customOptions.center || defaultConfig.center,
        zoom: customOptions.zoom || defaultConfig.zoom,
        bearing: customOptions.bearing || defaultConfig.bearing,
        pitch: customOptions.pitch || defaultConfig.pitch,
        antialias: true,
        attributionControl: false
      }
      
      console.log('🗺️ Creating Mapbox map with options:', mapOptions)

      // Create map instance
      const mapInstance = new mapboxgl.Map(mapOptions)

      // Add controls
      mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right')
      mapInstance.addControl(new mapboxgl.FullscreenControl(), 'top-right')
      
      // Add geolocate control
      const geolocateControl = new mapboxgl.GeolocateControl({
        positionOptions: {
          enableHighAccuracy: true
        },
        trackUserLocation: true,
        showUserHeading: true
      })
      mapInstance.addControl(geolocateControl, 'top-right')

      // Handle geolocation events
      geolocateControl.on('geolocate', (e) => {
        userLocation.value = {
          lat: e.coords.latitude,
          lng: e.coords.longitude,
          accuracy: e.coords.accuracy
        }
      })

      // Wait for map to load
      await new Promise((resolve, reject) => {
        mapInstance.on('load', resolve)
        mapInstance.on('error', reject)
        setTimeout(() => reject(new Error('Map load timeout')), 10000)
      })

      map.value = mapInstance
      mapContainer.value = container
      isLoaded.value = true
      
      console.log('✅ Map loaded successfully! Center:', mapInstance.getCenter(), 'Zoom:', mapInstance.getZoom())
      
      // Setup clustering source and layers
      await setupClustering()
      
      return true

    } catch (err) {
      error.value = `Failed to initialize map: ${err.message}`
      console.error('Map initialization error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Setup marker clustering
   */
  const setupClustering = async () => {
    if (!map.value) return

    // Add data source for places
    map.value.addSource('places', {
      type: 'geojson',
      data: {
        type: 'FeatureCollection',
        features: []
      },
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 50
    })

    // Add cluster circles
    map.value.addLayer({
      id: 'clusters',
      type: 'circle',
      source: 'places',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step',
          ['get', 'point_count'],
          '#51bbd6', 10,
          '#f1f075', 30,
          '#f28cb1'
        ],
        'circle-radius': [
          'step',
          ['get', 'point_count'],
          15, 10,
          20, 30,
          25
        ],
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
      }
    })

    // Add cluster count labels
    map.value.addLayer({
      id: 'cluster-count',
      type: 'symbol',
      source: 'places',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
        'text-size': 12
      },
      paint: {
        'text-color': '#ffffff'
      }
    })

    // Add individual markers
    map.value.addLayer({
      id: 'unclustered-point',
      type: 'circle',
      source: 'places',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': [
          'case',
          ['boolean', ['get', 'featured'], false], '#22c55e',
          ['boolean', ['get', 'verified'], false], '#3b82f6',
          '#6b7280'
        ],
        'circle-radius': [
          'case',
          ['boolean', ['get', 'featured'], false], 8,
          6
        ],
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
      }
    })

    // Add click handlers
    map.value.on('click', 'clusters', handleClusterClick)
    map.value.on('click', 'unclustered-point', handleMarkerClick)
    
    // Add hover effects
    map.value.on('mouseenter', 'clusters', () => {
      map.value.getCanvas().style.cursor = 'pointer'
    })
    map.value.on('mouseleave', 'clusters', () => {
      map.value.getCanvas().style.cursor = ''
    })
    map.value.on('mouseenter', 'unclustered-point', () => {
      map.value.getCanvas().style.cursor = 'pointer'
    })
    map.value.on('mouseleave', 'unclustered-point', () => {
      map.value.getCanvas().style.cursor = ''
    })
  }

  /**
   * Handle cluster click to zoom in
   */
  const handleClusterClick = (e) => {
    const features = map.value.queryRenderedFeatures(e.point, {
      layers: ['clusters']
    })

    const clusterId = features[0].properties.cluster_id
    map.value.getSource('places').getClusterExpansionZoom(
      clusterId,
      (err, zoom) => {
        if (err) return

        map.value.easeTo({
          center: features[0].geometry.coordinates,
          zoom: zoom
        })
      }
    )
  }

  /**
   * Handle individual marker click
   */
  const handleMarkerClick = (e) => {
    const features = map.value.queryRenderedFeatures(e.point, {
      layers: ['unclustered-point']
    })

    if (features.length > 0) {
      const place = features[0].properties
      showPlacePopup(place, e.lngLat)
    }
  }

  /**
   * Show popup for a place
   */
  const showPlacePopup = async (place, lngLat) => {
    if (!map.value) return

    // Close existing popups
    closeAllPopups()

    try {
      // Use global Mapbox GL
      const MapboxGL = window.mapboxgl

      const popup = new MapboxGL.Popup({
        closeButton: true,
        closeOnClick: false,
        maxWidth: '300px'
      })
        .setLngLat(lngLat)
        .setHTML(`
          <div class="place-popup">
            <div class="popup-loading">
              <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
            </div>
          </div>
        `)
        .addTo(map.value)

      // Store popup reference
      popups.value.set(place.id, popup)

      // Emit event for parent component to handle popup content
      if (options.onMarkerClick) {
        const placeData = await options.onMarkerClick(place)
        if (placeData && popup.isOpen()) {
          updatePopupContent(popup, placeData)
        }
      }

    } catch (err) {
      console.error('Error showing popup:', err)
    }
  }

  /**
   * Update popup content with place data
   */
  const updatePopupContent = (popup, place) => {
    const imageUrl = place.logo_url || place.cover_image_url || ''
    const rating = place.average_rating || 0
    const reviewCount = place.review_count || 0
    
    const content = `
      <div class="place-popup">
        ${imageUrl ? `<img src="${imageUrl}" alt="${place.name}" class="w-full h-32 object-cover rounded-t-lg">` : ''}
        <div class="p-4">
          <h3 class="font-semibold text-gray-900 mb-1">${place.name || place.title}</h3>
          <p class="text-sm text-gray-600 mb-2">${place.category?.name || ''}</p>
          ${rating > 0 ? `
            <div class="flex items-center mb-2">
              <div class="flex text-yellow-400">
                ${Array.from({length: 5}, (_, i) => 
                  `<svg class="w-4 h-4 ${i < Math.floor(rating) ? 'fill-current' : 'text-gray-300'}" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>`
                ).join('')}
              </div>
              <span class="text-sm text-gray-600 ml-1">(${reviewCount})</span>
            </div>
          ` : ''}
          <p class="text-sm text-gray-700 mb-3">${(place.description || '').substring(0, 100)}${place.description?.length > 100 ? '...' : ''}</p>
          <div class="flex justify-between items-center">
            <a href="${place.canonical_url || `/places/${place.slug}`}" 
               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
              View Details
            </a>
            ${place.website_url ? `
              <a href="${place.website_url}" target="_blank" rel="noopener" 
                 class="text-gray-600 hover:text-gray-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
              </a>
            ` : ''}
          </div>
        </div>
      </div>
    `
    
    popup.setHTML(content)
  }

  /**
   * Close all open popups
   */
  const closeAllPopups = () => {
    popups.value.forEach(popup => {
      if (popup.isOpen()) {
        popup.remove()
      }
    })
    popups.value.clear()
  }

  /**
   * Update places data on the map
   */
  const updatePlaces = (places) => {
    if (!map.value || !isLoaded.value) return

    const features = places.map(place => ({
      type: 'Feature',
      properties: {
        id: place.id,
        name: place.name || place.title,
        category: place.category?.name || '',
        featured: place.is_featured || false,
        verified: place.is_verified || false,
        description: place.description || '',
        website_url: place.website_url || '',
        canonical_url: place.canonical_url || '',
        slug: place.slug,
        average_rating: place.average_rating || 0,
        review_count: place.review_count || 0,
        logo_url: place.logo_url || '',
        cover_image_url: place.cover_image_url || ''
      },
      geometry: {
        type: 'Point',
        coordinates: [
          parseFloat(place.longitude || place.location?.longitude || 0),
          parseFloat(place.latitude || place.location?.latitude || 0)
        ]
      }
    })).filter(feature => 
      feature.geometry.coordinates[0] !== 0 && 
      feature.geometry.coordinates[1] !== 0
    )

    const source = map.value.getSource('places')
    if (source) {
      source.setData({
        type: 'FeatureCollection',
        features
      })
    }
  }

  /**
   * Fit map bounds to show all places
   */
  const fitBounds = (places, padding = 50) => {
    if (!map.value || !places?.length) return

    const validPlaces = places.filter(place => 
      place.longitude && place.latitude &&
      parseFloat(place.longitude) !== 0 && 
      parseFloat(place.latitude) !== 0
    )

    if (validPlaces.length === 0) return

    if (validPlaces.length === 1) {
      // Single place - center and zoom
      const place = validPlaces[0]
      map.value.easeTo({
        center: [parseFloat(place.longitude), parseFloat(place.latitude)],
        zoom: 15
      })
      return
    }

    // Multiple places - fit bounds
    const bounds = validPlaces.reduce((bounds, place) => {
      const lng = parseFloat(place.longitude || place.location?.longitude)
      const lat = parseFloat(place.latitude || place.location?.latitude)
      return bounds.extend([lng, lat])
    }, new window.mapboxgl.LngLatBounds())

    map.value.fitBounds(bounds, {
      padding,
      maxZoom: 16
    })
  }

  /**
   * Fly to a specific location
   */
  const flyTo = (lng, lat, zoom = 14) => {
    if (!map.value) return
    
    map.value.flyTo({
      center: [lng, lat],
      zoom,
      essential: true
    })
  }

  /**
   * Get current map bounds
   */
  const getBounds = () => {
    if (!map.value) return null
    
    const bounds = map.value.getBounds()
    return {
      north: bounds.getNorth(),
      south: bounds.getSouth(),
      east: bounds.getEast(),
      west: bounds.getWest()
    }
  }

  /**
   * Get current map center and zoom
   */
  const getViewState = () => {
    if (!map.value) return null
    
    return {
      center: map.value.getCenter(),
      zoom: map.value.getZoom(),
      bearing: map.value.getBearing(),
      pitch: map.value.getPitch()
    }
  }

  /**
   * Cleanup when component unmounts
   */
  const cleanup = () => {
    closeAllPopups()
    
    if (map.value) {
      map.value.remove()
      map.value = null
    }
    
    markers.value.clear()
    clusteredMarkers.value.clear()
    isLoaded.value = false
  }

  // Cleanup on unmount
  onUnmounted(() => {
    cleanup()
  })

  return {
    // State
    map,
    mapContainer,
    isLoaded,
    isLoading,
    error,
    userLocation,
    markers,
    popups,

    // Methods
    initializeMap,
    updatePlaces,
    fitBounds,
    flyTo,
    getBounds,
    getViewState,
    showPlacePopup,
    closeAllPopups,
    cleanup
  }
}