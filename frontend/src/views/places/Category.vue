<template>
    <div>
        <!-- Breadcrumb for authenticated users -->
        <nav v-if="authStore.user" class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-3 text-sm">
                    <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                    <span class="text-gray-400">/</span>
                    <router-link to="/places" class="text-gray-500 hover:text-gray-700">Places</router-link>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">{{ category?.name }}</span>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Location Banner -->
            <div v-if="locationData && locationData.current" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-blue-800 font-medium">
                                Showing {{ category?.name }} in {{ locationData.breadcrumb }}
                            </p>
                            <p class="text-xs text-blue-600">
                                {{ locationData.detected_via === 'manual' ? 'Location selected manually' : 'Location detected automatically' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <router-link 
                            :to="`/places/category/${route.params.categorySlug || route.params.slug}?all=true`"
                            class="text-sm text-gray-600 hover:text-gray-800 font-medium"
                        >
                            Browse all {{ category?.name }}
                        </router-link>
                        <LocationSwitcher 
                            :current-location="locationData.current"
                            :location-breadcrumb="locationData.breadcrumb"
                            @location-changed="handleLocationChange"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Fallback Message -->
            <div v-if="locationData?.fallback_message" class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-amber-800">{{ locationData.fallback_message }}</p>
                </div>
            </div>
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading category</h3>
                        <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <template v-else-if="category">
                <!-- Featured Places Section -->
                <div v-if="featuredEntries.length > 0" class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Featured {{ category.name }} in {{ locationData?.breadcrumb || 'Your Area' }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <router-link
                            v-for="entry in featuredEntries" 
                            :key="entry.id"
                            :to="getEntryUrl(entry)"
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer border-2 border-yellow-200"
                        >
                            <!-- Featured Badge -->
                            <div class="bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-medium">
                                ⭐ Featured
                            </div>
                            
                            <!-- Entry Image -->
                            <div v-if="entry.cover_image_url || entry.logo_url" class="h-48 bg-gray-200 relative">
                                <img
                                    :src="entry.cover_image_url || entry.logo_url"
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>

                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                                <div class="text-gray-600 text-sm mb-4 line-clamp-2" v-html="stripHtml(entry.description)"></div>
                                
                                <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ entry.location.city }}, {{ entry.location.state }}
                                </div>
                            </div>
                        </router-link>
                    </div>
                </div>
                <!-- Category Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <div class="text-5xl mr-4">{{ category.icon || '📁' }}</div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
                            <p v-if="category.description" class="text-gray-600 mt-2">{{ category.description }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <router-link
                            to="/places"
                            class="text-blue-600 hover:text-blue-800 font-medium"
                        >
                            ← Back to All Categories
                        </router-link>
                        <span class="text-gray-500">
                            {{ pagination.total }} {{ pagination.total === 1 ? 'entry' : 'entries' }} in this category
                        </span>
                    </div>
                </div>

                <!-- Directory Entries -->
                <div class="mb-8">
                    <div v-if="entries.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <router-link
                            v-for="entry in entries" 
                            :key="entry.id"
                            :to="getEntryUrl(entry)"
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                        >
                            <!-- Entry Image -->
                            <div v-if="entry.cover_image_url || entry.logo_url" class="h-48 bg-gray-200 relative">
                                <img
                                    :src="entry.cover_image_url || entry.logo_url"
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-if="entry.logo_url && entry.cover_image_url" class="absolute bottom-2 left-2">
                                    <img
                                        :src="entry.logo_url"
                                        :alt="entry.title + ' logo'"
                                        class="w-12 h-12 rounded-lg object-cover border-2 border-white"
                                    />
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ entry.category?.name || 'Uncategorized' }}
                                    </span>
                                    <span class="text-gray-500 text-sm">{{ entry.type }}</span>
                                </div>
                                
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                                <div class="text-gray-600 text-sm mb-4 line-clamp-2" v-html="stripHtml(entry.description)"></div>
                                
                                <div v-if="entry.location" class="flex items-center text-gray-500 text-sm mb-3">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ entry.location.city }}, {{ entry.location.state }}
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <a v-if="entry.website_url" 
                                           :href="entry.website_url" 
                                           target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 text-sm"
                                           @click.stop
                                        >
                                            Visit Website
                                        </a>
                                        <span v-if="entry.phone" class="text-gray-500 text-sm">{{ entry.phone }}</span>
                                    </div>
                                    <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ⭐ Featured
                                    </span>
                                </div>
                            </div>
                        </router-link>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="text-gray-500 text-lg">No entries found in {{ category.name }}.</div>
                        <p class="text-gray-400 mt-2">Be the first to add a business in this category!</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <button
                            @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-2 text-sm rounded-md transition-colors"
                            :class="pagination.current_page === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                        >
                            Previous
                        </button>
                        
                        <button
                            v-for="page in paginationRange"
                            :key="page"
                            @click="changePage(page)"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                page === pagination.current_page 
                                    ? 'bg-blue-600 text-white' 
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        >
                            {{ page }}
                        </button>
                        
                        <button
                            @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-2 text-sm rounded-md transition-colors"
                            :class="pagination.current_page === pagination.last_page ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                        >
                            Next
                        </button>
                    </nav>
                <!-- Editor's Choice / Curated Lists Section -->
                <div v-if="curatedLists.length > 0" class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Editor's Choice for {{ category.name }}
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="list in curatedLists" :key="list.id" 
                             class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ list.name }}</h3>
                            <p v-if="list.description" class="text-gray-600 text-sm mb-4">{{ list.description }}</p>
                            
                            <!-- Mini preview of places in the list -->
                            <div v-if="list.places && list.places.length > 0" class="space-y-2 mb-4">
                                <div v-for="(place, index) in list.places.slice(0, 3)" :key="place.id" 
                                     class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">{{ index + 1 }}.</span>
                                    <router-link :to="getEntryUrl(place)" 
                                                class="text-sm text-gray-700 hover:text-purple-600 font-medium">
                                        {{ place.title }}
                                    </router-link>
                                </div>
                                <div v-if="list.place_ids && list.place_ids.length > 3" 
                                     class="text-xs text-gray-500">
                                    +{{ list.place_ids.length - 3 }} more places
                                </div>
                            </div>
                            
                            <router-link :to="`/lists/${list.slug}`" 
                                        class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                                View full list →
                            </router-link>
                        </div>
                    </div>
                </div>

                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import LocationSwitcher from '@/components/LocationSwitcher.vue'
import { getPlaceUrl } from '@/utils/placeUrls'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const category = ref(null)
const entries = ref([])
const locationData = ref(null)
const featuredEntries = ref([])
const curatedLists = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})

// Computed
const paginationRange = computed(() => {
    const range = []
    const { current_page, last_page } = pagination.value
    const delta = 2

    for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
        range.push(i)
    }

    return range
})

// Methods
const getEntryUrl = (entry) => {
    return getPlaceUrl(entry)
}

const stripHtml = (html) => {
    if (!html) return ''
    const temp = document.createElement('div')
    temp.innerHTML = html
    return temp.textContent || temp.innerText || ''
}

const fetchCategoryData = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
        const params = { page }
        
        // Add 'all' param if in URL
        if (route.query.all === 'true') {
            params.all = true
        }
        
        const response = await axios.get(`/api/places/category/${route.params.categorySlug || route.params.slug}`, {
            params
        })
        
        category.value = response.data.category
        locationData.value = response.data.location
        featuredEntries.value = response.data.featured_places || []
        curatedLists.value = response.data.curated_lists || []
        entries.value = response.data.places?.data || response.data.entries?.data || []
        
        const placesData = response.data.places || response.data.entries
        pagination.value = {
            current_page: placesData.current_page,
            last_page: placesData.last_page,
            total: placesData.total
        }
        
        document.title = `${category.value.name} Places`
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load category'
        console.error('Error fetching category:', err)
        
        // Redirect to places if category not found
        if (err.response?.status === 404) {
            router.push('/places')
        }
    } finally {
        loading.value = false
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchCategoryData(page)
        window.scrollTo(0, 0)
    }
}

const handleLocationChange = (newLocation) => {
    // Refresh the page data with new location
    fetchCategoryData()
}

// Watch for route changes
watch(() => route.params.categorySlug || route.params.slug, (newSlug) => {
    if (newSlug) {
        fetchCategoryData()
    }
})

// Watch for query changes (like ?all=true)
watch(() => route.query, () => {
    fetchCategoryData()
})

// Initialize
onMounted(() => {
    fetchCategoryData()
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>