<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Browse Public Lists</h3>
            <p class="text-sm text-gray-600">Discover lists created by other users in the community</p>
        </div>

        <!-- Search and Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input
                    v-model="filters.search"
                    @input="debouncedSearch"
                    type="text"
                    placeholder="Search public lists..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div>
                <select
                    v-model="filters.category"
                    @change="fetchLists"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Categories</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }} ({{ category.lists_count }})
                    </option>
                </select>
            </div>
            <div>
                <select
                    v-model="filters.user"
                    @change="fetchLists"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">All Users</option>
                    <option v-for="user in popularUsers" :key="user.id" :value="user.id">
                        {{ user.name }} ({{ user.lists_count }} lists)
                    </option>
                </select>
            </div>
            <div>
                <select
                    v-model="filters.sort_by"
                    @change="fetchLists"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="updated_at">Recently Updated</option>
                    <option value="created_at">Recently Created</option>
                    <option value="popularity">Most Popular</option>
                    <option value="items_count">Most Items</option>
                    <option value="title">Title (A-Z)</option>
                </select>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="mb-6 bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-blue-600">{{ stats.total_lists }}</div>
                    <div class="text-sm text-gray-600">Public Lists</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ stats.total_users }}</div>
                    <div class="text-sm text-gray-600">Contributors</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-purple-600">{{ stats.total_items }}</div>
                    <div class="text-sm text-gray-600">Total Items</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-orange-600">{{ stats.total_categories }}</div>
                    <div class="text-sm text-gray-600">Categories</div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600">Loading public lists...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="lists.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No lists found</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ filters.search ? 'Try adjusting your search terms or filters.' : 'No public lists are available yet.' }}
            </p>
        </div>

        <!-- Lists Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="list in lists"
                :key="list.id"
                class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
            >
                <div class="p-4">
                    <!-- List Header -->
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-lg font-medium text-gray-900 truncate">{{ list.title || list.name }}</h4>
                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>{{ list.views_count || 0 }}</span>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="flex items-center mb-3">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium mr-2">
                            {{ list.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <span class="text-sm text-gray-600">by {{ list.user.name }}</span>
                    </div>

                    <!-- List Stats -->
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-3">
                        <span>{{ list.items_count || 0 }} items</span>
                        <span>{{ formatDate(list.updated_at) }}</span>
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <span v-if="list.category" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ list.category.name }}
                        </span>
                        <span v-else class="text-xs text-gray-400">No category</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <Link
                            :href="route('lists.show', list.id)"
                            class="flex-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium py-2 px-4 rounded text-sm text-center"
                        >
                            View List
                        </router-link>
                        <button
                            @click="favoriteList(list.id)"
                            :class="[
                                'px-3 py-2 rounded text-sm font-medium transition-colors',
                                list.is_favorited 
                                    ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                            :title="list.is_favorited ? 'Remove from favorites' : 'Add to favorites'"
                        >
                            <svg class="w-4 h-4" :fill="list.is_favorited ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center">
            <nav class="flex items-center space-x-2">
                <button
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-3 py-1 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </button>
                
                <span v-for="page in visiblePages" :key="page">
                    <button
                        v-if="page !== '...'"
                        @click="goToPage(page)"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium',
                            page === pagination.current_page
                                ? 'bg-blue-500 text-white'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        {{ page }}
                    </button>
                    <span v-else class="px-3 py-1 text-gray-400">...</span>
                </span>
                
                <button
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-3 py-1 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const lists = ref([])
const categories = ref([])
const popularUsers = ref([])
const loading = ref(false)
const pagination = ref(null)

const stats = ref({
    total_lists: 0,
    total_users: 0,
    total_items: 0,
    total_categories: 0
})

const filters = ref({
    search: '',
    category: '',
    user: '',
    sort_by: 'updated_at',
    page: 1
})

let searchTimeout = null

const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        filters.value.page = 1
        fetchLists()
    }, 300)
}

const fetchLists = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.search) params.append('search', filters.value.search)
        if (filters.value.category) params.append('category', filters.value.category)
        if (filters.value.user) params.append('user', filters.value.user)
        if (filters.value.sort_by) params.append('sort_by', filters.value.sort_by)
        params.append('page', filters.value.page)

        const response = await axios.get(`/data/public-lists?${params}`)
        lists.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
        }
        
        // Update stats
        stats.value.total_lists = response.data.total
    } catch (error) {
        console.error('Error fetching public lists:', error)
    } finally {
        loading.value = false
    }
}

const fetchCategories = async () => {
    try {
        const response = await axios.get('/data/list-categories')
        categories.value = response.data
        stats.value.total_categories = response.data.length
    } catch (error) {
        console.error('Error fetching categories:', error)
    }
}

const fetchPopularUsers = async () => {
    try {
        const response = await axios.get('/data/popular-list-creators')
        popularUsers.value = response.data
    } catch (error) {
        console.error('Error fetching popular users:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/data/public-lists-stats')
        stats.value = { ...stats.value, ...response.data }
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const favoriteList = async (listId) => {
    try {
        const list = lists.value.find(l => l.id === listId)
        if (list.is_favorited) {
            await axios.delete(`/data/favorites/lists/${listId}`)
            list.is_favorited = false
        } else {
            await axios.post(`/data/favorites/lists/${listId}`)
            list.is_favorited = true
        }
    } catch (error) {
        console.error('Error toggling favorite:', error)
        alert('Error updating favorite status')
    }
}

const goToPage = (page) => {
    filters.value.page = page
    fetchLists()
}

const visiblePages = computed(() => {
    if (!pagination.value) return []
    
    const current = pagination.value.current_page
    const last = pagination.value.last_page
    const pages = []
    
    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i)
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) pages.push(i)
            pages.push('...')
            pages.push(last)
        } else if (current >= last - 3) {
            pages.push(1)
            pages.push('...')
            for (let i = last - 4; i <= last; i++) pages.push(i)
        } else {
            pages.push(1)
            pages.push('...')
            for (let i = current - 1; i <= current + 1; i++) pages.push(i)
            pages.push('...')
            pages.push(last)
        }
    }
    
    return pages
})

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now - date
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    
    if (days === 0) return 'Today'
    if (days === 1) return 'Yesterday'
    if (days < 7) return `${days} days ago`
    if (days < 30) return `${Math.floor(days / 7)} weeks ago`
    return date.toLocaleDateString()
}

onMounted(() => {
    fetchLists()
    fetchCategories()
    fetchPopularUsers()
    fetchStats()
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