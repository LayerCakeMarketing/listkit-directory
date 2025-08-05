import { ref, computed } from 'vue'
import { useNotification } from './useNotification'

/**
 * Geolocation Composable
 * Handles user location detection with IP-based fallback
 */
export function useGeolocation(options = {}) {
  const { showError, showSuccess } = useNotification()

  // State
  const location = ref(null)
  const isLoading = ref(false)
  const error = ref(null)
  const isSupported = ref(typeof navigator !== 'undefined' && 'geolocation' in navigator)
  const permission = ref('prompt') // 'granted', 'denied', 'prompt'

  // Default options
  const defaultOptions = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 300000, // 5 minutes
    fallbackToIP: true,
    showNotifications: true,
    ...options
  }

  // Computed
  const hasLocation = computed(() => location.value !== null)
  const coordinates = computed(() => {
    if (!location.value) return null
    return {
      lat: location.value.latitude,
      lng: location.value.longitude
    }
  })

  const accuracy = computed(() => location.value?.accuracy || null)
  const timestamp = computed(() => location.value?.timestamp || null)

  /**
   * Get current position using browser geolocation
   */
  const getCurrentPosition = () => {
    return new Promise((resolve, reject) => {
      if (!isSupported.value) {
        reject(new Error('Geolocation is not supported by this browser'))
        return
      }

      isLoading.value = true
      error.value = null

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            altitudeAccuracy: position.coords.altitudeAccuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            timestamp: position.timestamp,
            source: 'gps'
          }

          location.value = locationData
          permission.value = 'granted'
          isLoading.value = false

          if (defaultOptions.showNotifications) {
            showSuccess('Location Found', 'Your location has been detected')
          }

          resolve(locationData)
        },
        (error) => {
          isLoading.value = false
          handleGeolocationError(error)
          reject(error)
        },
        {
          enableHighAccuracy: defaultOptions.enableHighAccuracy,
          timeout: defaultOptions.timeout,
          maximumAge: defaultOptions.maximumAge
        }
      )
    })
  }

  /**
   * Watch position changes (continuous tracking)
   */
  const watchPosition = (callback) => {
    if (!isSupported.value) {
      throw new Error('Geolocation is not supported by this browser')
    }

    const watchId = navigator.geolocation.watchPosition(
      (position) => {
        const locationData = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          altitude: position.coords.altitude,
          altitudeAccuracy: position.coords.altitudeAccuracy,
          heading: position.coords.heading,
          speed: position.coords.speed,
          timestamp: position.timestamp,
          source: 'gps'
        }

        location.value = locationData
        permission.value = 'granted'

        if (callback) {
          callback(locationData)
        }
      },
      (error) => {
        handleGeolocationError(error)
      },
      {
        enableHighAccuracy: defaultOptions.enableHighAccuracy,
        timeout: defaultOptions.timeout,
        maximumAge: defaultOptions.maximumAge
      }
    )

    return watchId
  }

  /**
   * Stop watching position
   */
  const clearWatch = (watchId) => {
    if (watchId && isSupported.value) {
      navigator.geolocation.clearWatch(watchId)
    }
  }

  /**
   * Get location using IP-based geolocation (fallback)
   */
  const getLocationByIP = async () => {
    try {
      isLoading.value = true
      error.value = null

      // Try multiple IP geolocation services
      const services = [
        {
          url: 'https://ipapi.co/json/',
          parse: (data) => ({
            latitude: data.latitude,
            longitude: data.longitude,
            city: data.city,
            region: data.region,
            country: data.country_name,
            accuracy: 50000, // IP location is less accurate
            source: 'ip'
          })
        },
        {
          url: 'https://ip-api.com/json/',
          parse: (data) => ({
            latitude: data.lat,
            longitude: data.lon,
            city: data.city,
            region: data.regionName,
            country: data.country,
            accuracy: 50000,
            source: 'ip'
          })
        },
        {
          url: 'https://api.bigdatacloud.net/data/reverse-geocode-client',
          parse: (data) => ({
            latitude: data.latitude,
            longitude: data.longitude,
            city: data.city,
            region: data.principalSubdivision,
            country: data.countryName,
            accuracy: 50000,
            source: 'ip'
          })
        }
      ]

      let locationData = null

      for (const service of services) {
        try {
          const response = await fetch(service.url)
          const data = await response.json()
          
          if (data && (data.latitude || data.lat) && (data.longitude || data.lon)) {
            locationData = service.parse(data)
            locationData.timestamp = Date.now()
            break
          }
        } catch (serviceError) {
          console.warn(`Failed to get location from ${service.url}:`, serviceError)
        }
      }

      if (locationData) {
        location.value = locationData
        
        if (defaultOptions.showNotifications) {
          showSuccess('Location Found', `Detected location: ${locationData.city}, ${locationData.region}`)
        }
        
        return locationData
      } else {
        throw new Error('Unable to determine location from IP')
      }

    } catch (err) {
      error.value = err.message
      
      if (defaultOptions.showNotifications) {
        showError('Location Error', 'Unable to determine your location')
      }
      
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get user location with GPS first, fallback to IP
   */
  const getLocation = async (forceGPS = false) => {
    if (location.value && !forceGPS) {
      return location.value
    }

    try {
      // Try GPS first
      if (isSupported.value) {
        try {
          return await getCurrentPosition()
        } catch (gpsError) {
          console.warn('GPS location failed:', gpsError)
          
          // Fall back to IP location if enabled
          if (defaultOptions.fallbackToIP) {
            if (defaultOptions.showNotifications) {
              showError('GPS Unavailable', 'Falling back to approximate location')
            }
            return await getLocationByIP()
          } else {
            throw gpsError
          }
        }
      } else {
        // GPS not supported, try IP location
        if (defaultOptions.fallbackToIP) {
          return await getLocationByIP()
        } else {
          throw new Error('Geolocation is not supported')
        }
      }
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  /**
   * Handle geolocation errors
   */
  const handleGeolocationError = (error) => {
    let errorMessage = 'Location detection failed'
    
    switch (error.code) {
      case error.PERMISSION_DENIED:
        errorMessage = 'Location access denied by user'
        permission.value = 'denied'
        break
      case error.POSITION_UNAVAILABLE:
        errorMessage = 'Location information unavailable'
        break
      case error.TIMEOUT:
        errorMessage = 'Location request timeout'
        break
      default:
        errorMessage = 'Unknown location error'
        break
    }

    error.value = errorMessage
    
    if (defaultOptions.showNotifications) {
      showError('Location Error', errorMessage)
    }
  }

  /**
   * Request location permission
   */
  const requestPermission = async () => {
    if (!isSupported.value) {
      throw new Error('Geolocation is not supported')
    }

    try {
      // Check if permissions API is available
      if ('permissions' in navigator) {
        const result = await navigator.permissions.query({ name: 'geolocation' })
        permission.value = result.state
        
        if (result.state === 'granted') {
          return await getCurrentPosition()
        } else if (result.state === 'denied') {
          throw new Error('Location permission denied')
        }
      }

      // Fall back to trying to get position (will trigger permission request)
      return await getCurrentPosition()
    } catch (err) {
      permission.value = 'denied'
      throw err
    }
  }

  /**
   * Clear current location
   */
  const clearLocation = () => {
    location.value = null
    error.value = null
  }

  /**
   * Set location manually (for testing or manual input)
   */
  const setLocation = (lat, lng, source = 'manual') => {
    location.value = {
      latitude: lat,
      longitude: lng,
      accuracy: source === 'manual' ? 0 : 1000,
      timestamp: Date.now(),
      source
    }
  }

  /**
   * Calculate distance between current location and target
   */
  const distanceTo = (targetLat, targetLng) => {
    if (!location.value) return null

    const R = 3959 // Earth's radius in miles
    const dLat = toRadians(targetLat - location.value.latitude)
    const dLng = toRadians(targetLng - location.value.longitude)
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRadians(location.value.latitude)) * Math.cos(toRadians(targetLat)) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2)
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c
  }

  const toRadians = (degrees) => degrees * (Math.PI / 180)

  /**
   * Check if location is within a certain age
   */
  const isLocationFresh = (maxAge = 300000) => { // 5 minutes default
    if (!location.value || !location.value.timestamp) return false
    return (Date.now() - location.value.timestamp) < maxAge
  }

  return {
    // State
    location,
    isLoading,
    error,
    isSupported,
    permission,

    // Computed
    hasLocation,
    coordinates,
    accuracy,
    timestamp,

    // Methods
    getLocation,
    getCurrentPosition,
    getLocationByIP,
    watchPosition,
    clearWatch,
    requestPermission,
    clearLocation,
    setLocation,
    distanceTo,
    isLocationFresh
  }
}