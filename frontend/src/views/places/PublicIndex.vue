<template>
    <div>
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-green-600 to-blue-600 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    Discover Amazing Places
                </h1>
                <p class="text-xl mb-8 max-w-3xl mx-auto">
                    Explore carefully curated local businesses, restaurants, and attractions. 
                    Find your next favorite spot or share your discoveries with the community.
                </p>
                <div class="space-x-4">
                    <router-link
                        v-if="!authStore.user"
                        to="/register"
                        class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                    >
                        Join & Discover More
                    </router-link>
                    <a
                        href="#featured"
                        class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-colors inline-block"
                    >
                        Browse Featured Places
                    </a>
                </div>
            </div>
        </section>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading content</h3>
                        <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <template v-else>
            <!-- Top Categories (Curated) -->
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Popular Categories</h2>
                        <p class="text-gray-600 max-w-2xl mx-auto">
                            Browse places by category to find exactly what you're looking for
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6" v-if="topCategories?.length > 0">
                        <router-link 
                            v-for="category in topCategories.slice(0, 6)" 
                            :key="category.id"
                            :to="`/places/${category.slug}`"
                            class="bg-gray-50 rounded-lg p-6 text-center hover:shadow-lg hover:bg-gray-100 transition-all cursor-pointer group"
                        >
                            <div class="text-3xl mb-3">{{ category.icon || '📍' }}</div>
                            <h3 class="font-semibold text-gray-900 group-hover:text-green-600 transition-colors">
                                {{ category.name }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ category.total_entries_count || category.directory_entries_count || 0 }} places
                            </p>
                        </router-link>
                    </div>

                    <div class="text-center mt-8">
                        <router-link
                            v-if="authStore.user"
                            to="/places"
                            class="inline-flex items-center text-green-600 hover:text-green-800 font-medium"
                        >
                            View All Categories
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>
                    </div>
                </div>
            </section>

            <!-- Featured Places (Admin Curated) -->
            <section id="featured" class="py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Featured Places</h2>
                        <p class="text-gray-600 max-w-2xl mx-auto">
                            Hand-picked locations that showcase the best of what's available
                        </p>
                    </div>
                    
                    <div v-if="featuredEntries?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div
                            v-for="entry in featuredEntries" 
                            :key="entry.id"
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow"
                        >
                            <!-- Featured Image -->
                            <div v-if="entry.cover_image_url || entry.logo_url" class="h-48 bg-gray-200 overflow-hidden relative">
                                <img 
                                    :src="entry.cover_image_url || entry.logo_url" 
                                    :alt="entry.title"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
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
                            <div v-else class="h-48 bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <span class="text-6xl opacity-80">{{ entry.category?.icon || '🏢' }}</span>
                            </div>

                            <div class="p-6">
                                <!-- Category Badge -->
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ entry.category?.name || 'Featured' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ⭐ Featured
                                    </span>
                                </div>
                                
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ stripHtml(entry.description) }}</p>
                                
                                <!-- Location -->
                                <div v-if="entry.location" class="flex items-center text-gray-500 text-sm mb-4">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ entry.location.city }}, {{ entry.location.state }}
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between">
                                    <router-link 
                                        :to="getEntryUrl(entry)"
                                        class="text-green-600 hover:text-green-800 font-medium text-sm"
                                    >
                                        Learn More →
                                    </router-link>
                                    <a v-if="entry.website_url" 
                                       :href="entry.website_url" 
                                       target="_blank" 
                                       class="text-gray-500 hover:text-gray-700 text-sm"
                                    >
                                        Visit Website
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="text-gray-500 text-lg">No featured places yet.</div>
                        <p class="text-gray-400 mt-2">Check back soon for curated recommendations!</p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-16 bg-green-600 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h3 class="text-3xl font-bold mb-4">Ready to Explore More?</h3>
                    <p class="text-green-100 max-w-2xl mx-auto mb-8">
                        Join our community to access the full directory, create lists, and discover hidden gems.
                    </p>
                    
                    <div class="space-x-4">
                        <router-link
                            v-if="!authStore.user"
                            to="/register"
                            class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            Sign Up Free
                        </router-link>
                        <router-link
                            v-if="authStore.user"
                            to="/places"
                            class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            Browse All Places
                        </router-link>
                        <router-link
                            v-if="authStore.user"
                            to="/places/create"
                            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-colors inline-block"
                        >
                            Add a Place
                        </router-link>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const featuredEntries = ref([])
const topCategories = ref([])

// Methods
const getEntryUrl = (entry) => {
    // Use canonical URL structure: /places/state/city/category/entry-slug-id
    if (entry.state_region && entry.city_region && entry.category) {
        const state = entry.state_region.slug
        const city = entry.city_region.slug
        const category = entry.category.slug
        const entrySlug = `${entry.slug}-${entry.id}`
        return `/places/${state}/${city}/${category}/${entrySlug}`
    }
    // Fallback for entries without complete regional data
    return `/places/entry/${entry.id}`
}

const stripHtml = (html) => {
    if (!html) return ''
    const temp = document.createElement('div')
    temp.innerHTML = html
    return temp.textContent || temp.innerText || ''
}

const fetchData = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get('/api/places/public')
        featuredEntries.value = response.data.featuredEntries || []
        topCategories.value = response.data.topCategories || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load places'
        console.error('Error fetching public places:', err)
    } finally {
        loading.value = false
    }
}

// Initialize
onMounted(() => {
    document.title = 'Discover Amazing Places'
    fetchData()
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