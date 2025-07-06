<template>
    <Head title="Discover Amazing Places" />
    
    <PublicLayout :can-register="canRegister">
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
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Join & Discover More
                    </Link>
                    <Link
                        href="#featured"
                        class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-colors"
                    >
                        Browse Featured Places
                    </Link>
                </div>
            </div>
        </section>

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
                    <Link 
                        v-for="category in topCategories.slice(0, 6)" 
                        :key="category.id"
                        :href="`/places/category/${category.slug}`"
                        class="bg-gray-50 rounded-lg p-6 text-center hover:shadow-lg hover:bg-gray-100 transition-all cursor-pointer group"
                    >
                        <div class="text-3xl mb-3">{{ category.icon || '📍' }}</div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-green-600 transition-colors">
                            {{ category.name }}
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ category.total_entries_count || category.directory_entries_count || 0 }} places
                        </p>
                    </Link>
                </div>

                <div class="text-center mt-8">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('places.index')"
                        class="inline-flex items-center text-green-600 hover:text-green-800 font-medium"
                    >
                        View All Categories
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
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
                                <Link 
                                    :href="getEntryUrl(entry)"
                                    class="text-green-600 hover:text-green-800 font-medium text-sm"
                                >
                                    Learn More →
                                </Link>
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
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Sign Up Free
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('places.index')"
                        class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Browse All Places
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('places.create')"
                        class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-colors"
                    >
                        Add a Place
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    featuredEntries: Array,
    topCategories: Array,
    canRegister: {
        type: Boolean,
        default: true
    }
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
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>