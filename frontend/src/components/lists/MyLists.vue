<template>
    <div class="p-6">
        <!-- Header with Create Button -->
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">My Lists</h3>
            <router-link 
                :href="route('lists.create')"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create New List
            </router-link>
        </div>

        <!-- Search and Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <input
                    v-model="filters.search"
                    @input="fetchLists"
                    type="text"
                    placeholder="Search your lists..."
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
                        {{ category.name }}
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
                    <option value="title">Title (A-Z)</option>
                    <option value="items_count">Most Items</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600">Loading your lists...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="lists.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No lists found</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ filters.search ? 'Try adjusting your search terms.' : 'Get started by creating your first list.' }}
            </p>
            <div class="mt-6">
                <router-link 
                    :href="route('lists.create')"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Create New List
                </router-link>
            </div>
        </div>

        <!-- Lists Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="list in lists"
                :key="list.id"
                class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
            >
                <!-- Cover Image -->
                <div v-if="getListImage(list)" class="h-40 bg-gray-200 overflow-hidden">
                    <img 
                        :src="getListImage(list)" 
                        :alt="list.name"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div v-else class="h-40 bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center">
                    <span class="text-4xl text-white opacity-80">{{ list.name.charAt(0).toUpperCase() }}</span>
                </div>

                <div class="p-4">
                    <!-- List Header -->
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-lg font-medium text-gray-900 truncate">{{ list.name }}</h4>
                        <div class="flex space-x-1">
                            <Link
                                :href="getEditUrl(list)"
                                class="text-gray-400 hover:text-purple-600"
                                title="Edit"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </router-link>
                            <button
                                @click="deleteList(list.id)"
                                class="text-gray-400 hover:text-red-600"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Category and Privacy -->
                    <div class="flex justify-between items-center mb-3">
                        <span v-if="list.category" 
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                              :style="{ 
                                  backgroundColor: list.category.color + '20', 
                                  color: list.category.color || '#8B5CF6' 
                              }">
                            {{ list.category.name }}
                        </span>
                        <span v-else class="text-xs text-gray-400">No category</span>
                        
                        <span :class="[
                            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                            list.is_public ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                        ]">
                            {{ list.is_public ? 'Public' : 'Private' }}
                        </span>
                    </div>

                    <!-- Tags -->
                    <div v-if="list.tags && list.tags.length > 0" class="flex flex-wrap gap-1 mb-3">
                        <span
                            v-for="tag in list.tags.slice(0, 3)"
                            :key="tag.id"
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                            :style="{ 
                                backgroundColor: tag.color + '20', 
                                color: tag.color 
                            }"
                        >
                            {{ tag.name }}
                        </span>
                        <span v-if="list.tags.length > 3" class="text-xs text-gray-500">
                            +{{ list.tags.length - 3 }}
                        </span>
                    </div>

                    <!-- List Stats -->
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ list.items_count || 0 }} items
                            </span>
                            <span v-if="list.views_count > 0" class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ list.views_count }}
                            </span>
                        </div>
                        <span>{{ formatDate(list.updated_at) }}</span>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-4">
                        <Link
                            :href="getListUrl(list)"
                            class="w-full bg-purple-50 text-purple-700 hover:bg-purple-100 font-medium py-2 px-4 rounded text-sm text-center block transition-colors"
                        >
                            View List
                        </router-link>
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
import { Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage()

const lists = ref([])
const categories = ref([])
const loading = ref(false)
const pagination = ref(null)

const filters = ref({
    search: '',
    category: '',
    sort_by: 'updated_at',
    page: 1
})

const fetchLists = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.search) params.append('search', filters.value.search)
        if (filters.value.category) params.append('category', filters.value.category)
        if (filters.value.sort_by) params.append('sort_by', filters.value.sort_by)
        params.append('page', filters.value.page)

        const response = await axios.get(`/data/my-lists?${params}`)
        lists.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
        }
    } catch (error) {
        console.error('Error fetching lists:', error)
        lists.value = []
        pagination.value = null
    } finally {
        loading.value = false
    }
}

const fetchCategories = async () => {
    try {
        const response = await axios.get('/data/list-categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching categories:', error)
    }
}

const deleteList = async (listId) => {
    if (!confirm('Are you sure you want to delete this list?')) return
    
    try {
        await axios.delete(`/data/lists/${listId}`)
        await fetchLists()
    } catch (error) {
        console.error('Error deleting list:', error)
        alert('Error deleting list')
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

const getListImage = (list) => {
    return list.featured_image_url || list.featured_image || null
}

const getUserSlug = () => {
    const user = page.props.auth?.user
    return user?.custom_url || user?.username || user?.id
}

const getListUrl = (list) => {
    const userSlug = getUserSlug()
    if (userSlug && list.slug) {
        return route('user.list.show', { user: userSlug, list: list.slug })
    }
    return '#'
}

const getEditUrl = (list) => {
    const userSlug = getUserSlug()
    if (userSlug && list.slug) {
        return route('user.list.edit', { user: userSlug, list: list.slug })
    }
    return '#'
}

onMounted(() => {
    fetchLists()
    fetchCategories()
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