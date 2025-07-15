<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li>
                        <router-link to="/places" class="text-gray-500 hover:text-gray-700">Places</router-link>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li>
                        <router-link :to="`/places/${state}`" class="text-gray-500 hover:text-gray-700">
                            {{ stateData?.name || state }}
                        </router-link>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li>
                        <router-link :to="`/places/${state}/${city}`" class="text-gray-500 hover:text-gray-700">
                            {{ cityData?.name || city }}
                        </router-link>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">{{ categoryData?.name || category }}</li>
                </ol>
            </nav>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-red-800">{{ error }}</p>
            </div>

            <!-- Content -->
            <div v-else>
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ categoryData?.name || category }} in {{ cityData?.name || city }}, {{ stateData?.name || state }}
                    </h1>
                    <p v-if="categoryData?.description" class="text-gray-600">{{ categoryData.description }}</p>
                    <div class="mt-4 text-sm text-gray-500">
                        {{ entries.length }} {{ entries.length === 1 ? 'place' : 'places' }} found
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Sort by:</span>
                        <select
                            v-model="sortBy"
                            class="ml-4 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name">Name (A-Z)</option>
                            <option value="rating">Highest Rated</option>
                        </select>
                    </div>
                </div>

                <!-- Entries List -->
                <div v-if="sortedEntries.length > 0" class="space-y-4">
                    <div
                        v-for="entry in sortedEntries"
                        :key="entry.id"
                        class="bg-white rounded-lg shadow hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <router-link 
                            :to="entry.canonical_url || `/places/entry/${entry.slug}`"
                            class="flex"
                        >
                            <div v-if="entry.cover_image_url" class="w-48 h-32 flex-shrink-0">
                                <img
                                    :src="entry.cover_image_url"
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-lg mb-1">{{ entry.title }}</h3>
                                        <p v-if="entry.location?.address_line1" class="text-sm text-gray-600 mb-1">
                                            {{ entry.location.address_line1 }}
                                            <span v-if="entry.neighborhood_region">
                                                • {{ entry.neighborhood_region.name }}
                                            </span>
                                        </p>
                                        <p v-if="entry.description" class="text-sm text-gray-600 line-clamp-2">
                                            {{ stripHtml(entry.description) }}
                                        </p>
                                    </div>
                                    <div v-if="entry.rating" class="ml-4 text-sm text-yellow-600">
                                        ★ {{ entry.rating }}
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <span>{{ formatType(entry.type) }}</span>
                                    <span v-if="entry.phone" class="mx-2">•</span>
                                    <span v-if="entry.phone">{{ entry.phone }}</span>
                                    <span v-if="entry.website_url" class="mx-2">•</span>
                                    <span v-if="entry.website_url">Website</span>
                                </div>
                            </div>
                        </router-link>
                    </div>
                </div>

                <!-- No Results -->
                <div v-else class="bg-white rounded-lg shadow-md p-8 text-center">
                    <p class="text-gray-600">No {{ categoryData?.name || 'places' }} found in {{ cityData?.name || city }}.</p>
                    <router-link
                        :to="`/places/${state}/${city}`"
                        class="mt-4 inline-block text-blue-600 hover:text-blue-800"
                    >
                        View all places in {{ cityData?.name || city }}
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const props = defineProps({
    state: {
        type: String,
        required: true
    },
    city: {
        type: String,
        required: true
    },
    category: {
        type: String,
        required: true
    }
})

// State
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cityData = ref(null)
const categoryData = ref(null)
const entries = ref([])
const sortBy = ref('newest')

// Computed
const sortedEntries = computed(() => {
    const sorted = [...entries.value]
    
    switch (sortBy.value) {
        case 'newest':
            return sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        case 'oldest':
            return sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
        case 'name':
            return sorted.sort((a, b) => a.title.localeCompare(b.title))
        case 'rating':
            return sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0))
        default:
            return sorted
    }
})

// Methods
const stripHtml = (html) => {
    const temp = document.createElement('div')
    temp.innerHTML = html
    return temp.textContent || temp.innerText || ''
}

const formatType = (type) => {
    return type?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Place'
}

const fetchData = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get(`/api/places/${props.state}/${props.city}/${props.category}`)
        
        stateData.value = response.data.state
        cityData.value = response.data.city
        categoryData.value = response.data.category
        entries.value = response.data.entries || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load category data'
        console.error('Error fetching category data:', err)
    } finally {
        loading.value = false
    }
}

// Lifecycle
onMounted(() => {
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