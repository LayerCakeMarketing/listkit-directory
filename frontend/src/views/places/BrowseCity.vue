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
                    <li class="text-gray-900 font-medium">{{ cityData?.name || city }}</li>
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
                        {{ cityData?.name || city }}, {{ stateData?.name || state }}
                    </h1>
                    <p v-if="cityData?.intro_text" class="text-gray-600">{{ cityData.intro_text }}</p>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <span>{{ totalEntries }} places</span>
                        <span v-if="neighborhoods.length > 0" class="mx-2">•</span>
                        <span v-if="neighborhoods.length > 0">{{ neighborhoods.length }} neighborhoods</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <div class="flex flex-wrap gap-4">
                        <select
                            v-model="selectedCategory"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.slug">
                                {{ cat.name }} ({{ cat.entries_count }})
                            </option>
                        </select>
                        
                        <select
                            v-model="selectedNeighborhood"
                            v-if="neighborhoods.length > 0"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Neighborhoods</option>
                            <option v-for="n in neighborhoods" :key="n.id" :value="n.slug">
                                {{ n.name }} ({{ n.cached_place_count }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Entries Grid -->
                <div v-if="filteredEntries.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="entry in filteredEntries"
                        :key="entry.id"
                        class="bg-white rounded-lg shadow hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <router-link :to="entry.canonical_url || `/places/entry/${entry.slug}`">
                            <div v-if="entry.cover_image_url" class="h-48 overflow-hidden">
                                <img
                                    :src="entry.cover_image_url"
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-1">{{ entry.title }}</h3>
                                <p v-if="entry.neighborhood_region" class="text-sm text-gray-600 mb-1">
                                    {{ entry.neighborhood_region.name }}
                                </p>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ entry.location?.address_line1 }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ entry.category?.name }}</span>
                                    <span v-if="entry.rating" class="text-xs text-yellow-600">
                                        ★ {{ entry.rating }}
                                    </span>
                                </div>
                            </div>
                        </router-link>
                    </div>
                </div>

                <!-- No Results -->
                <div v-else class="bg-white rounded-lg shadow-md p-8 text-center">
                    <p class="text-gray-600">No places found matching your criteria.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
    }
})

// State
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cityData = ref(null)
const neighborhoods = ref([])
const categories = ref([])
const entries = ref([])
const selectedCategory = ref('')
const selectedNeighborhood = ref('')

// Computed
const totalEntries = computed(() => entries.value.length)

const filteredEntries = computed(() => {
    let filtered = entries.value

    if (selectedCategory.value) {
        filtered = filtered.filter(e => e.category?.slug === selectedCategory.value)
    }

    if (selectedNeighborhood.value) {
        filtered = filtered.filter(e => e.neighborhood_region?.slug === selectedNeighborhood.value)
    }

    return filtered
})

// Methods
const fetchData = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get(`/api/places/${props.state}/${props.city}`)
        
        stateData.value = response.data.state
        cityData.value = response.data.city
        neighborhoods.value = response.data.neighborhoods || []
        categories.value = response.data.categories || []
        entries.value = response.data.entries || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load city data'
        console.error('Error fetching city data:', err)
    } finally {
        loading.value = false
    }
}

// Watchers
watch([selectedCategory, selectedNeighborhood], () => {
    // Could refetch with filters or just filter client-side
})

// Lifecycle
onMounted(() => {
    fetchData()
})
</script>