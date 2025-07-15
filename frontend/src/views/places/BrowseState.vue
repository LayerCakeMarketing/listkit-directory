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
                    <li class="text-gray-900 font-medium">{{ stateData?.name || state }}</li>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ stateData?.name || state }}</h1>
                    <p v-if="stateData?.intro_text" class="text-gray-600">{{ stateData.intro_text }}</p>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <span>{{ totalEntries }} places</span>
                        <span class="mx-2">•</span>
                        <span>{{ cities.length }} cities</span>
                    </div>
                </div>

                <!-- Cities Grid -->
                <div v-if="cities.length > 0" class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Cities in {{ stateData?.name || state }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <router-link
                            v-for="city in cities"
                            :key="city.id"
                            :to="`/places/${state}/${city.slug}`"
                            class="bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4"
                        >
                            <h3 class="font-semibold text-gray-900">{{ city.name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ city.cached_place_count || 0 }} places</p>
                        </router-link>
                    </div>
                </div>

                <!-- Categories -->
                <div v-if="categories.length > 0" class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Browse by Category</h2>
                    <div class="flex flex-wrap gap-2">
                        <router-link
                            v-for="category in categories"
                            :key="category.id"
                            :to="`/places/${category.slug}`"
                            class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors"
                        >
                            {{ category.name }} ({{ category.entries_count }})
                        </router-link>
                    </div>
                </div>

                <!-- Recent Entries -->
                <div v-if="recentEntries.length > 0">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Places</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="entry in recentEntries"
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
                                    <p class="text-sm text-gray-600 mb-2">{{ entry.city_region?.name }}, {{ entry.state_region?.name }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">{{ entry.category?.name }}</span>
                                    </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const props = defineProps({
    state: {
        type: String,
        required: true
    }
})

// State
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cities = ref([])
const categories = ref([])
const recentEntries = ref([])

// Computed
const totalEntries = computed(() => {
    return cities.value.reduce((sum, city) => sum + (city.cached_place_count || 0), 0)
})

// Methods
const fetchData = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get(`/api/places/${props.state}`)
        
        stateData.value = response.data.region
        cities.value = response.data.cities || []
        categories.value = response.data.categories || []
        recentEntries.value = response.data.recent_entries || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load state data'
        console.error('Error fetching state data:', err)
    } finally {
        loading.value = false
    }
}

// Lifecycle
onMounted(() => {
    fetchData()
})
</script>