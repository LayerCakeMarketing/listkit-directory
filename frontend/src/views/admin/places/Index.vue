<template>
    <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Places Management</h2>
                            <p class="text-sm text-gray-600 mt-1">Manage directory entries and their canonical URLs</p>
                        </div>
                        <div class="flex gap-2">
                            <router-link
                                :to="{ name: 'AdminPlacesBulkImportExport' }"
                                class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition-colors inline-flex items-center"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3-3m0 0l3 3m-3-3v-6" />
                                </svg>
                                Import/Export
                            </router-link>
                            <button
                                @click="updateCanonicalUrls"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Update URLs
                            </button>
                            <button
                                @click="showCreateModal = true"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add New Place
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 mt-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                            <div class="text-sm text-blue-600 font-medium">Total Places</div>
                            <div class="text-2xl font-bold text-blue-900">{{ stats.total || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                            <div class="text-sm text-green-600 font-medium">Published</div>
                            <div class="text-2xl font-bold text-green-900">{{ stats.published || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-lg">
                            <div class="text-sm text-yellow-600 font-medium">Draft</div>
                            <div class="text-2xl font-bold text-yellow-900">{{ stats.draft || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
                            <div class="text-sm text-orange-600 font-medium">Pending Review</div>
                            <div class="text-2xl font-bold text-orange-900">{{ stats.pending_review || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                            <div class="text-sm text-purple-600 font-medium">Featured</div>
                            <div class="text-2xl font-bold text-purple-900">{{ stats.featured || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
                            <div class="text-sm text-indigo-600 font-medium">Verified</div>
                            <div class="text-2xl font-bold text-indigo-900">{{ stats.verified || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-4 rounded-lg">
                            <div class="text-sm text-teal-600 font-medium">With URLs</div>
                            <div class="text-2xl font-bold text-teal-900">{{ stats.with_regions || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-lg">
                            <div class="text-sm text-red-600 font-medium">Missing URLs</div>
                            <div class="text-2xl font-bold text-red-900">{{ stats.missing_regions || 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                v-model="filters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Title, description, location..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select
                                v-model="filters.category_id"
                                @change="fetchPlaces"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filters.status"
                                @change="fetchPlaces"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="pending_review">Pending Review</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select
                                v-model="filters.type"
                                @change="fetchPlaces"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Types</option>
                                <option value="business_b2b">Business B2B</option>
                                <option value="business_b2c">Business B2C</option>
                                <option value="religious_org">Religious Organization</option>
                                <option value="point_of_interest">Point of Interest</option>
                                <option value="area_of_interest">Area of Interest</option>
                                <option value="service">Service</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchPlaces"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="title">Title</option>
                                <option value="state">State</option>
                                <option value="city">City</option>
                                <option value="neighborhood">Neighborhood</option>
                                <option value="view_count">Views</option>
                                <option value="list_count">List Adds</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Filters -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button
                            @click="searchMissingPlace()"
                            class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-medium transition-colors hover:bg-red-700"
                        >
                            Search Missing Place
                        </button>
                        <button
                            @click="setQuickFilter('featured')"
                            :class="quickFilter === 'featured' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Featured Only
                        </button>
                        <button
                            @click="setQuickFilter('verified')"
                            :class="quickFilter === 'verified' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Verified Only
                        </button>
                        <button
                            @click="setQuickFilter('needs_review')"
                            :class="quickFilter === 'needs_review' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Needs Review
                        </button>
                        <button
                            @click="setQuickFilter('missing_urls')"
                            :class="quickFilter === 'missing_urls' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Missing URLs
                        </button>
                        <button
                            v-if="quickFilter"
                            @click="clearQuickFilter"
                            class="px-3 py-1 bg-gray-300 text-gray-700 rounded-full text-sm font-medium transition-colors"
                        >
                            Clear Filter
                        </button>
                    </div>
                </div>

                <!-- Places Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedPlaces.length > 0" class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700 font-medium">
                                    {{ selectedPlaces.length }} places selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkUpdateStatus"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                                    >
                                        Change Status
                                    </button>
                                    <button
                                        @click="bulkToggleFeatured"
                                        class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors"
                                    >
                                        Toggle Featured
                                    </button>
                                    <button
                                        @click="bulkUpdateRegions"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors"
                                    >
                                        Update URLs
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors"
                                    >
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="places.data && places.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="selectedPlaces.length === places.data.length"
                                                class="rounded border-gray-300"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Place
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Category
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            URL Status
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="place in places.data" :key="place.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :value="place.id"
                                                v-model="selectedPlaces"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img
                                                        v-if="place.logo_url"
                                                        class="h-10 w-10 rounded-lg object-cover"
                                                        :src="place.logo_url"
                                                        :alt="place.title"
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-lg bg-gray-300 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ place.title }}</div>
                                                    <div class="text-sm text-gray-500">ID: {{ place.id }}</div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span v-if="place.is_featured" class="text-purple-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </span>
                                                        <span v-if="place.is_verified" class="text-blue-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm">
                                                <div v-if="place.state_region || place.city_region" class="text-gray-900 font-medium">
                                                    <span v-if="place.city_region">{{ place.city_region.name }}</span>
                                                    <span v-if="place.city_region && place.state_region">, </span>
                                                    <span v-if="place.state_region">{{ place.state_region.name }}</span>
                                                </div>
                                                <div v-else-if="place.location" class="text-gray-900">
                                                    {{ place.location.city }}, {{ place.location.state }}
                                                </div>
                                                <div v-if="place.location?.address_line1" class="text-gray-500 text-xs">
                                                    {{ place.location.address_line1 }}
                                                </div>
                                                <div v-if="place.location?.neighborhood" class="text-gray-500 text-xs">
                                                    {{ place.location.neighborhood }}
                                                </div>
                                                <div v-if="!place.location && !place.state_region && !place.city_region" class="text-gray-400">
                                                    No location
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="place.category" class="text-sm text-gray-900">
                                                {{ place.category.name }}
                                            </div>
                                            <div v-else class="text-sm text-gray-400">
                                                Uncategorized
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ getTypeLabel(place.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusBadgeClass(place.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ place.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="place.has_regions" class="text-sm">
                                                <a 
                                                    :href="place.canonical_url" 
                                                    target="_blank"
                                                    class="text-blue-600 hover:text-blue-900 flex items-center"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                    View
                                                </a>
                                                <div class="text-xs text-gray-500 mt-1">{{ place.canonical_url }}</div>
                                            </div>
                                            <div v-else>
                                                <span class="text-xs text-red-600 font-medium">Missing regions</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                @click="editPlace(place)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <a
                                                v-if="place.canonical_url"
                                                :href="place.canonical_url"
                                                target="_blank"
                                                class="text-green-600 hover:text-green-900 mr-3"
                                            >
                                                View
                                            </a>
                                            <button
                                                @click="deletePlace(place)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No places found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="places.last_page > 1" class="mt-6">
                            <Pagination 
                                :current-page="places.current_page"
                                :last-page="places.last_page"
                                :total="places.total"
                                :from="places.from"
                                :to="places.to"
                                :per-page="places.per_page"
                                @change-page="fetchPlaces"
                            />
                        </div>
                    </div>
                </div>
            </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import axios from 'axios'
import Pagination from '@/components/Pagination.vue'
import { debounce } from 'lodash'

// State
const loading = ref(false)
const places = ref({ 
    data: [], 
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
    per_page: 50,
    links: []
})
const categories = ref([])
const stats = ref({})
const selectedPlaces = ref([])
const showCreateModal = ref(false)
const processing = ref(false)
const quickFilter = ref('')

// Filters
const filters = reactive({
    search: '',
    category_id: '',
    status: '',
    type: '',
    sort_by: 'created_at',
    sort_order: 'desc'
})

// Methods
const fetchPlaces = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            ...filters,
            per_page: 50  // Use per_page instead of limit
        }

        // Apply quick filters
        if (quickFilter.value === 'featured') {
            params.is_featured = true
        } else if (quickFilter.value === 'verified') {
            params.is_verified = true
        } else if (quickFilter.value === 'needs_review') {
            params.status = 'pending_review'
        } else if (quickFilter.value === 'missing_urls') {
            params.missing_regions = true
        }

        console.log('Fetching places with params:', params)
        const response = await axios.get('/api/admin/places', { params })
        console.log('Places response:', response.data)
        
        // Ensure we have all pagination properties
        places.value = {
            data: response.data.data || [],
            current_page: response.data.current_page || 1,
            last_page: response.data.last_page || 1,
            total: response.data.total || 0,
            from: response.data.from || 0,
            to: response.data.to || 0,
            per_page: response.data.per_page || 50,
            links: response.data.links || []
        }
    } catch (error) {
        console.error('Error fetching places:', error)
    } finally {
        loading.value = false
    }
}

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/admin/categories')
        console.log('Categories response:', response.data)
        
        // Handle different response formats
        if (response.data.data) {
            categories.value = response.data.data
        } else if (response.data.categories) {
            categories.value = response.data.categories
        } else if (Array.isArray(response.data)) {
            categories.value = response.data
        } else {
            categories.value = []
        }
    } catch (error) {
        console.error('Error fetching categories:', error)
        categories.value = []
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/places/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchPlaces()
}, 300)

const setQuickFilter = (filter) => {
    quickFilter.value = quickFilter.value === filter ? '' : filter
    fetchPlaces()
}

const clearQuickFilter = () => {
    quickFilter.value = ''
    fetchPlaces()
}

const searchMissingPlace = () => {
    const searchTerm = prompt('Enter the name of the missing place (e.g., "TechHyb Electronics"):')
    if (searchTerm) {
        // Reset all filters
        filters.search = searchTerm
        filters.category_id = ''
        filters.status = ''
        filters.type = ''
        quickFilter.value = ''
        
        // Search with increased limit
        fetchPlaces()
    }
}

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedPlaces.value = places.value.data.map(place => place.id)
    } else {
        selectedPlaces.value = []
    }
}

const editPlace = (place) => {
    // Navigate to edit page
    window.location.href = `/places/${place.id}/edit`
}

const deletePlace = async (place) => {
    if (!confirm(`Are you sure you want to delete "${place.title}"? This action cannot be undone.`)) return

    try {
        await axios.delete(`/api/admin/places/${place.id}`)
        fetchPlaces()
        fetchStats()
    } catch (error) {
        alert('Error deleting place: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

const bulkUpdateStatus = async () => {
    const status = prompt('Enter status (published, draft, pending_review, archived):')
    if (!status || !['published', 'draft', 'pending_review', 'archived'].includes(status)) return

    try {
        await axios.post('/api/admin/places/bulk-update', {
            entry_ids: selectedPlaces.value,
            action: status === 'published' ? 'publish' : status === 'archived' ? 'archive' : 'update_status',
            status: status
        })
        selectedPlaces.value = []
        fetchPlaces()
        fetchStats()
    } catch (error) {
        alert('Error updating status')
    }
}

const bulkToggleFeatured = async () => {
    try {
        await axios.post('/api/admin/places/bulk-update', {
            entry_ids: selectedPlaces.value,
            action: 'toggle_featured'
        })
        selectedPlaces.value = []
        fetchPlaces()
        fetchStats()
    } catch (error) {
        alert('Error toggling featured status')
    }
}

const bulkUpdateRegions = async () => {
    if (!confirm('This will update canonical URLs for selected places based on their location data. Continue?')) return
    
    processing.value = true
    try {
        await axios.post('/api/admin/places/bulk-update', {
            entry_ids: selectedPlaces.value,
            action: 'update_regions'
        })
        selectedPlaces.value = []
        fetchPlaces()
        fetchStats()
        alert('Successfully updated canonical URLs')
    } catch (error) {
        alert('Error updating URLs: ' + (error.response?.data?.message || 'Unknown error'))
    } finally {
        processing.value = false
    }
}

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedPlaces.value.length} places?`)) return

    try {
        await axios.post('/api/admin/places/bulk-update', {
            entry_ids: selectedPlaces.value,
            action: 'delete'
        })
        selectedPlaces.value = []
        fetchPlaces()
        fetchStats()
    } catch (error) {
        alert('Error deleting places')
    }
}

const updateCanonicalUrls = async () => {
    if (!confirm('This will update all places with missing canonical URLs. Continue?')) return
    
    processing.value = true
    try {
        const response = await axios.post('/api/admin/places/update-canonical-urls')
        alert(`Successfully updated ${response.data.updated} places`)
        fetchPlaces()
        fetchStats()
    } catch (error) {
        alert('Error updating URLs: ' + (error.response?.data?.message || 'Unknown error'))
    } finally {
        processing.value = false
    }
}

const getTypeLabel = (type) => {
    const labels = {
        'business_b2b': 'B2B',
        'business_b2c': 'B2C',
        'religious_org': 'Religious',
        'point_of_interest': 'POI',
        'area_of_interest': 'AOI',
        'service': 'Service',
        'online': 'Online'
    }
    return labels[type] || type
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'published': 'bg-green-100 text-green-800',
        'draft': 'bg-yellow-100 text-yellow-800',
        'pending_review': 'bg-orange-100 text-orange-800',
        'archived': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

// Initialize
onMounted(() => {
    fetchPlaces()
    fetchCategories()
    fetchStats()
})
</script>