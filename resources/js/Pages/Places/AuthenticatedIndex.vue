<template>
    <Head title="Browse Places" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-32">
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Browse Places</h1>
                    <p class="text-gray-600 mt-2">Discover local businesses, restaurants, and attractions</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('places.create')"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                    >
                        + Add Place
                    </Link>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Places</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name, description..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select
                            v-model="selectedCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="handleCategoryFilter"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.slug">
                                {{ category.name }} ({{ category.total_entries_count || category.directory_entries_count || 0 }})
                            </option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select
                            v-model="sortOption"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="handleSort"
                        >
                            <option value="latest">Latest Added</option>
                            <option value="name">Name (A-Z)</option>
                            <option value="featured">Featured First</option>
                        </select>
                    </div>
                </div>

                <!-- Location Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <!-- State Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                        <select
                            v-model="selectedState"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="handleLocationFilter"
                        >
                            <option value="">All States</option>
                            <option v-for="state in availableStates" :key="state.code" :value="state.code">
                                {{ state.name }}
                            </option>
                        </select>
                    </div>

                    <!-- City Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <select
                            v-model="selectedCity"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="handleLocationFilter"
                            :disabled="!selectedState"
                        >
                            <option value="">All Cities</option>
                            <option v-for="city in availableCities" :key="city" :value="city">
                                {{ city }}
                            </option>
                        </select>
                    </div>

                    <!-- Neighborhood Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Neighborhood</label>
                        <select
                            v-model="selectedNeighborhood"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="handleLocationFilter"
                            :disabled="!selectedCity"
                        >
                            <option value="">All Neighborhoods</option>
                            <option v-for="neighborhood in availableNeighborhoods" :key="neighborhood" :value="neighborhood">
                                {{ neighborhood }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Browse by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <Link
                        v-for="category in categories.slice(0, 12)"
                        :key="category.id"
                        :href="`/places/category/${category.slug}`"
                        class="bg-white rounded-lg p-4 text-center border hover:shadow-md hover:border-green-300 transition-all cursor-pointer group"
                    >
                        <div class="text-2xl mb-2">{{ category.icon || '📁' }}</div>
                        <h3 class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors">
                            {{ category.name }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ category.total_entries_count || category.directory_entries_count || 0 }}
                        </p>
                    </Link>
                </div>
            </div>

            <!-- Places Grid -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Places
                        <span class="text-lg text-gray-500 font-normal">({{ entries.total }} total)</span>
                    </h2>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>View:</span>
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'text-green-600' : 'text-gray-400'"
                            class="p-1 hover:text-green-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'text-green-600' : 'text-gray-400'"
                            class="p-1 hover:text-green-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid' && entries.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="entry in entries.data" 
                        :key="entry.id"
                        :href="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
                    >
                        <!-- Image placeholder or actual image -->
                        <div v-if="entry.cover_image_url || entry.logo_url" class="h-40 bg-gray-200 overflow-hidden relative">
                            <img 
                                :src="entry.cover_image_url || entry.logo_url" 
                                :alt="entry.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <!-- Show logo overlay if we have both cover and logo -->
                            <div v-if="entry.logo_url && entry.cover_image_url" class="absolute bottom-2 left-2">
                                <img
                                    :src="entry.logo_url"
                                    :alt="entry.title + ' logo'"
                                    class="w-12 h-12 rounded-lg object-cover border-2 border-white"
                                />
                            </div>
                        </div>
                        <div v-else class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <span class="text-4xl text-gray-400">{{ entry.category?.icon || '🏢' }}</span>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ entry.category?.name || 'Uncategorized' }}
                                </span>
                                <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    ⭐ Featured
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-green-600 transition-colors">{{ entry.title }}</h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ stripHtml(entry.description) }}</p>
                            
                            <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span v-if="entry.location.neighborhood">{{ entry.location.neighborhood }}, </span>
                                {{ entry.location.city }}, {{ entry.location.state }}
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- List View -->
                <div v-if="viewMode === 'list' && entries.data.length > 0" class="space-y-4">
                    <Link
                        v-for="entry in entries.data" 
                        :key="entry.id"
                        :href="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer block group"
                    >
                        <div class="flex items-start space-x-4">
                            <div v-if="entry.featured_image" class="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    :src="entry.featured_image" 
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div v-else class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                <span class="text-2xl text-gray-400">{{ entry.category?.icon || '🏢' }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ entry.category?.name || 'Uncategorized' }}
                                        </span>
                                        <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⭐ Featured
                                        </span>
                                    </div>
                                </div>
                                
                                <h3 class="text-xl font-semibold text-gray-900 mb-1 group-hover:text-green-600 transition-colors">{{ entry.title }}</h3>
                                <p class="text-gray-600 mb-3 line-clamp-2">{{ stripHtml(entry.description) }}</p>
                                
                                <div class="flex items-center justify-between">
                                    <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span v-if="entry.location.neighborhood">{{ entry.location.neighborhood }}, </span>
                                        {{ entry.location.city }}, {{ entry.location.state }}
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <a v-if="entry.website_url" 
                                           :href="entry.website_url" 
                                           target="_blank" 
                                           class="text-green-600 hover:text-green-800 text-sm"
                                        >
                                            Visit Website
                                        </a>
                                        <span v-if="entry.phone" class="text-gray-500 text-sm">{{ entry.phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="entries.data.length === 0" class="text-center py-12">
                    <div class="text-gray-500 text-lg">No places found.</div>
                    <p class="text-gray-400 mt-2">Try adjusting your search or filters.</p>
                    <Link
                        :href="route('places.create')"
                        class="mt-4 inline-flex items-center text-green-600 hover:text-green-800 font-medium"
                    >
                        Add the first place →
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="entries.links && entries.links.length > 3" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <template v-for="(link, index) in entries.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-green-600 text-white' 
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-green-600 text-white' 
                                    : 'text-gray-400 cursor-not-allowed'
                            ]"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usStates } from '@/Data/usStates'

const props = defineProps({
    entries: Object,
    categories: Array,
    filters: Object,
    locationData: Object
})

const searchQuery = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || '')
const sortOption = ref(props.filters?.sort || 'latest')
const viewMode = ref('grid')

// Location filters
const selectedState = ref(props.filters?.state || '')
const selectedCity = ref(props.filters?.city || '')
const selectedNeighborhood = ref(props.filters?.neighborhood || '')

// Available location options
const availableStates = computed(() => {
    // If we have location data from backend, use it, otherwise use the full list
    return props.locationData?.states || usStates
})

const availableCities = computed(() => {
    // Get cities for selected state from backend data
    if (!selectedState.value || !props.locationData?.cities) return []
    return props.locationData.cities[selectedState.value] || []
})

const availableNeighborhoods = computed(() => {
    // Get neighborhoods for selected city from backend data
    if (!selectedCity.value || !props.locationData?.neighborhoods) return []
    return props.locationData.neighborhoods[selectedCity.value] || []
})

// Watch for state changes to reset city and neighborhood
watch(selectedState, () => {
    selectedCity.value = ''
    selectedNeighborhood.value = ''
})

// Watch for city changes to reset neighborhood
watch(selectedCity, () => {
    selectedNeighborhood.value = ''
})

const getEntryUrl = (entry) => {
    // Check if entry has category with parent
    if (entry.category && entry.category.parent_id) {
        const parentSlug = entry.category.parent?.slug || 'places'
        const childSlug = entry.category.slug
        return `/${parentSlug}/${childSlug}/${entry.slug}`
    }
    return `/places/entry/${entry.slug}`
}

const stripHtml = (html) => {
    if (!html) return ''
    // Create a temporary div element to parse HTML
    const temp = document.createElement('div')
    temp.innerHTML = html
    // Return text content which strips all HTML tags
    return temp.textContent || temp.innerText || ''
}

const handleSearch = () => {
    updateFilters()
}

const handleCategoryFilter = () => {
    updateFilters()
}

const handleSort = () => {
    updateFilters()
}

const handleLocationFilter = () => {
    updateFilters()
}

const updateFilters = () => {
    const filters = {}
    if (searchQuery.value) filters.search = searchQuery.value
    if (selectedCategory.value) filters.category = selectedCategory.value
    if (sortOption.value) filters.sort = sortOption.value
    if (selectedState.value) filters.state = selectedState.value
    if (selectedCity.value) filters.city = selectedCity.value
    if (selectedNeighborhood.value) filters.neighborhood = selectedNeighborhood.value
    
    router.get(route('places.index'), filters, { preserveState: true })
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>