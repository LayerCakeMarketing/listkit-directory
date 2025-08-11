/**
 * TypeScript interfaces for the RegionTwoColumnView component
 * These document the expected API response structure for the region map endpoint
 */

export interface Region {
  id: number
  name: string
  type: 'neighborhood' | 'state_park' | 'national_park' | 'city' | 'state'
  slug: string
  description?: string
  place_count?: number
  population?: number
  bounds?: {
    north: number
    south: number
    east: number
    west: number
  }
  park_info?: {
    hours?: string
    entrance_fee?: string
    activities?: string[]
  }
  parent?: Region
  children?: Region[]
}

export interface Place {
  id: number
  name: string
  slug: string
  description?: string
  address?: string
  latitude: number
  longitude: number
  category?: {
    id: number
    name: string
    color?: string
    slug?: string
  }
  is_featured?: boolean
  is_verified?: boolean
  is_claimed?: boolean
  is_saved?: boolean
  cover_image_url?: string
  logo_url?: string
  website_url?: string
  phone?: string
  distance?: number
  average_rating?: number
  review_count?: number
  tags?: string[]
  hours_of_operation?: {
    [day: string]: {
      open: string
      close: string
      closed: boolean
    }
  }
  location?: {
    city?: string
    state?: string
    neighborhood?: string
  }
}

export interface Category {
  id: number
  name: string
  slug: string
  count: number
  color?: string
  icon?: string
}

export interface BreadcrumbItem {
  name: string
  url: string
}

export interface MapBounds {
  north: number
  south: number
  east: number
  west: number
}

export interface RegionTwoColumnAPIResponse {
  region: Region
  places: Place[]
  categories: Category[]
  has_next_page: boolean
  current_page: number
  total_count: number
}

export interface PlacesAPIResponse {
  places: Place[]
  has_next_page: boolean
  current_page: number
  total_count: number
}

/**
 * Component props interface
 */
export interface RegionTwoColumnViewProps {
  regionId?: number
}

/**
 * Component emits interface
 */
export interface RegionTwoColumnViewEmits {
  placeSelected: (place: Place | null) => void
  regionLoaded: (region: Region) => void
}