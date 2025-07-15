<template>
    <div>
        <!-- Breadcrumb -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-3 text-sm">
                    <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                    <span class="text-gray-400">/</span>
                    <router-link to="/places" class="text-gray-500 hover:text-gray-700">Places</router-link>
                    <template v-if="parentCategory">
                        <span class="text-gray-400">/</span>
                        <router-link 
                            :to="`/places/${parentCategory.slug}`" 
                            class="text-gray-500 hover:text-gray-700"
                        >
                            {{ parentCategory.name }}
                        </router-link>
                    </template>
                    <template v-if="childCategory">
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-500">{{ childCategory.name }}</span>
                    </template>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">{{ entry?.title }}</span>
                </div>
            </div>
        </nav>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
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
                        <h3 class="text-sm font-medium text-red-800">Error loading place</h3>
                        <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else-if="entry" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Entry Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Cover Image -->
                        <div v-if="entry.cover_image_url" class="relative h-64 bg-gray-300">
                            <img
                                :src="entry.cover_image_url"
                                :alt="entry.title + ' cover image'"
                                class="w-full h-full object-cover"
                            />
                        </div>

                        <!-- Entry Header -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <!-- Logo -->
                                        <div v-if="entry.logo_url" class="flex-shrink-0">
                                            <img
                                                :src="entry.logo_url"
                                                :alt="entry.title + ' logo'"
                                                class="w-auto h-16 rounded-lg object-cover border-2 border-gray-200"
                                            />
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <router-link 
                                                :to="`/places/${entry.category.slug}`"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors cursor-pointer"
                                            >
                                                {{ entry.category.name }}
                                            </router-link>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                                                {{ formatType(entry.type) }}
                                            </span>
                                        </div>
                                    </div>
                                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ entry.title }}</h1>
                                    <div v-if="entry.description" class="entry-description text-gray-600 text-lg prose prose-lg max-w-none" v-html="entry.description"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Entry Details -->
                        <div class="p-6 space-y-6">
                            <!-- Contact Information -->
                            <div v-if="entry.email || entry.phone || entry.website" class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                                <div class="space-y-3">
                                    <div v-if="entry.email" class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <a :href="`mailto:${entry.email}`" class="text-blue-600 hover:text-blue-800">
                                            {{ entry.email }}
                                        </a>
                                    </div>
                                    <div v-if="entry.phone" class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <a :href="`tel:${entry.phone}`" class="text-blue-600 hover:text-blue-800">
                                            {{ entry.phone }}
                                        </a>
                                    </div>
                                    <div v-if="entry.website" class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        <a :href="entry.website" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800">
                                            {{ formatWebsite(entry.website) }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div v-if="entry.location" class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Location</h3>
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div>
                                        <div v-if="entry.location.address_line1" class="text-gray-900">{{ entry.location.address_line1 }}</div>
                                        <div v-if="entry.location.address_line2" class="text-gray-900">{{ entry.location.address_line2 }}</div>
                                        <div class="text-gray-600">
                                            <template v-if="entry.location.neighborhood">{{ entry.location.neighborhood }}, </template>
                                            <template v-if="entry.location.city">{{ entry.location.city }}</template>
                                            <template v-if="entry.location.city && entry.location.state">, </template>
                                            <template v-if="entry.location.state">{{ entry.location.state }}</template>
                                            <template v-if="entry.location.zip_code"> {{ entry.location.zip_code }}</template>
                                        </div>
                                        <div v-if="entry.location.cross_streets" class="text-gray-500 text-sm mt-1">
                                            Near {{ entry.location.cross_streets }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gallery -->
                            <div v-if="entry.gallery_images && entry.gallery_images.length > 0" class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Gallery</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div
                                        v-for="(image, index) in entry.gallery_images"
                                        :key="index"
                                        class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="openGallery(index)"
                                    >
                                        <img
                                            :src="image"
                                            :alt="`${entry.title} gallery image ${index + 1}`"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div v-if="entry.additional_info">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                                <div class="prose prose-sm max-w-none">
                                    {{ entry.additional_info }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <router-link
                                v-if="canEditEntry && entry.id"
                                :to="`/places/${entry.id}/edit`"
                                class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors text-center block"
                                @click="handleEditClick"
                            >
                                Edit Entry
                            </router-link>
                            <div v-else-if="authStore.user && !canEditEntry" class="text-sm text-gray-600 text-center">
                                <p>You don't have permission to edit this entry.</p>
                                <p class="mt-1">Required role: Admin, Manager, or Entry Owner</p>
                            </div>
                            <div v-else-if="!authStore.user" class="text-sm text-gray-600 text-center">
                                <router-link to="/login" class="text-blue-600 hover:text-blue-800">
                                    Login to edit this entry
                                </router-link>
                            </div>
                            <button
                                v-if="authStore.user"
                                @click="addToList"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                            >
                                Add to My List
                            </button>
                            <button
                                @click="shareEntry"
                                class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
                            >
                                Share
                            </button>
                        </div>
                    </div>

                    <!-- Related Entries -->
                    <div v-if="relatedEntries?.length > 0" class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Related {{ entry.category.name }}</h3>
                        <div class="space-y-4">
                            <router-link
                                v-for="related in relatedEntries"
                                :key="related.id"
                                :to="getRelatedEntryUrl(related)"
                                class="block hover:bg-gray-50 p-3 rounded-md transition-colors"
                            >
                                <h4 class="font-medium text-gray-900 mb-1">{{ related.title }}</h4>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ related.description }}</p>
                                <div v-if="related.location && (related.location.city || related.location.state)" class="text-xs text-gray-500 mt-2">
                                    <template v-if="related.location.neighborhood">{{ related.location.neighborhood }}, </template>
                                    <template v-if="related.location.city">{{ related.location.city }}</template>
                                    <template v-if="related.location.city && related.location.state">, </template>
                                    <template v-if="related.location.state">{{ related.location.state }}</template>
                                </div>
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props for different URL patterns
const props = defineProps({
    isCanonical: {
        type: Boolean,
        default: false
    },
    isShortUrl: {
        type: Boolean,
        default: false
    },
    id: {
        type: [String, Number],
        default: null
    }
})

// State
const loading = ref(true)
const error = ref(null)
const entry = ref(null)
const relatedEntries = ref([])
const parentCategory = ref(null)
const childCategory = ref(null)

// Computed
const canEditEntry = computed(() => {
    const user = authStore.user
    if (!user) return false
    
    // Admin and Manager can edit anything
    if (['admin', 'manager'].includes(user.role)) return true
    
    // Editor can edit published entries
    if (user.role === 'editor' && entry.value?.status === 'published') return true
    
    // Business owner can edit their own entries
    if (user.role === 'business_owner' && entry.value?.owner_user_id === user.id) return true
    
    // Original creator can edit draft entries
    if (entry.value?.created_by_user_id === user.id && entry.value?.status === 'draft') return true
    
    return false
})

// Methods
const formatWebsite = (url) => {
    return url.replace(/^https?:\/\//, '').replace(/\/$/, '')
}

const formatType = (type) => {
    return type.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}

const openGallery = (index) => {
    // Simple implementation - open image in new window
    window.open(entry.value.gallery_images[index], '_blank')
}

const getRelatedEntryUrl = (relatedEntry) => {
    // Use the same parent and child category as current entry if available
    if (parentCategory.value && childCategory.value) {
        return `/${parentCategory.value.slug}/${childCategory.value.slug}/${relatedEntry.slug}`
    }
    // Fallback to direct entry URL
    return `/places/entry/${relatedEntry.slug}`
}

const addToList = () => {
    // This would open a modal to select which list to add to
    alert('Add to list functionality would go here')
}

const shareEntry = () => {
    if (navigator.share) {
        navigator.share({
            title: entry.value.title,
            text: entry.value.description,
            url: window.location.href,
        })
    } else {
        // Fallback to copying URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!')
        })
    }
}

const handleEditClick = (e) => {
    // Log debugging info
    console.log('Edit button clicked', {
        entryId: entry.value?.id,
        user: authStore.user,
        canEdit: canEditEntry.value,
        entry: entry.value
    })
    
    // If no entry ID, prevent navigation
    if (!entry.value?.id) {
        e.preventDefault()
        alert('Unable to edit: Entry ID not found. Please refresh the page.')
    }
}

const fetchEntry = async () => {
    loading.value = true
    error.value = null

    try {
        // Determine the API endpoint based on route params
        let apiUrl = ''
        
        // Check if it's a canonical URL
        if (props.isCanonical && route.params.entry) {
            // Use the canonical API endpoint
            apiUrl = `/api/places/${route.params.state}/${route.params.city}/${route.params.category}/${route.params.entry}`
        } 
        // Check if it's a short URL
        else if (props.isShortUrl && props.id) {
            apiUrl = `/api/p/${props.id}`
        }
        // Legacy patterns
        else if (route.params.category && route.params.slug) {
            // Handle /places/:category/:slug pattern
            apiUrl = `/api/places/entry/${route.params.slug}`
        } else if (route.params.slug) {
            apiUrl = `/api/places/entry/${route.params.slug}`
        } else if (route.params.id) {
            apiUrl = `/api/places/${route.params.id}`
        }

        const response = await axios.get(apiUrl)
        
        console.log('Entry API response:', response.data)
        
        entry.value = response.data.entry
        relatedEntries.value = response.data.relatedEntries || []
        parentCategory.value = response.data.parentCategory || null
        childCategory.value = response.data.childCategory || null
        
        document.title = entry.value.title
        
        // Log entry details for debugging
        console.log('Entry loaded:', {
            id: entry.value?.id,
            title: entry.value?.title,
            status: entry.value?.status,
            owner_user_id: entry.value?.owner_user_id,
            created_by_user_id: entry.value?.created_by_user_id
        })
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load place'
        console.error('Error fetching entry:', err)
        
        // Redirect to places if entry not found
        if (err.response?.status === 404) {
            router.push('/places')
        }
    } finally {
        loading.value = false
    }
}

// Watch for route changes
watch(() => route.params, () => {
    fetchEntry()
})

// Initialize
onMounted(() => {
    fetchEntry()
})
</script>

<style scoped>
/* Ensure rich text content displays properly */
.entry-description :deep(p) {
    margin-bottom: 1rem;
}

.entry-description :deep(p:last-child) {
    margin-bottom: 0;
}

.entry-description :deep(ul),
.entry-description :deep(ol) {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.entry-description :deep(li) {
    margin-bottom: 0.25rem;
}

.entry-description :deep(h2) {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.entry-description :deep(strong) {
    font-weight: 600;
}

.entry-description :deep(em) {
    font-style: italic;
}

.entry-description :deep(hr) {
    margin: 1.5rem 0;
    border-top: 1px solid #e5e7eb;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>