/**
 * Generate the canonical URL for a place entry
 * @param {Object} entry - The place entry object
 * @returns {string} The URL path for the place
 */
export function getPlaceUrl(entry) {
    // If the backend already provides canonical_url, use it
    if (entry.canonical_url) {
        return entry.canonical_url
    }
    
    // Use canonical URL structure: /places/state/city/parent-category/entry-slug-id
    // For subcategories, use the parent category in the URL
    if (entry.state_region && entry.city_region && entry.category) {
        const state = entry.state_region.slug || 'ca'  // Default to CA if no state
        const city = entry.city_region.slug  
        // Use parent category if it exists, otherwise use the category itself
        const category = entry.category.parent?.slug || entry.category.slug
        const entrySlug = `${entry.slug}-${entry.id}`
        return `/places/${state}/${city}/${category}/${entrySlug}`
    }
    // Fallback for entries without complete regional data
    return `/places/entry/${entry.id}`
}

/**
 * Parse a canonical place URL to extract its components
 * @param {string} url - The URL path
 * @returns {Object} Object with state, city, category, and entry components
 */
export function parsePlaceUrl(url) {
    const match = url.match(/^\/places\/([^\/]+)\/([^\/]+)\/([^\/]+)\/(.+)$/)
    if (match) {
        const [, state, city, category, entryWithId] = match
        const lastDash = entryWithId.lastIndexOf('-')
        const slug = entryWithId.substring(0, lastDash)
        const id = entryWithId.substring(lastDash + 1)
        
        return {
            state,
            city,
            category,
            slug,
            id: parseInt(id)
        }
    }
    return null
}