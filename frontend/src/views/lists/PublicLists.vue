<template>
    <div class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Explore Public Lists</h1>
                <p class="mt-2 text-gray-600">Discover curated lists from our community</p>
            </div>

            <!-- Category Filter -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Browse by Category</h2>
                <div class="flex flex-wrap gap-2">
                    <router-link
                        v-for="category in categories"
                        :key="category.id"
                        :to="`/lists/${category.slug}`"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-all bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                    >
                        <div class="flex items-center space-x-2">
                            <div 
                                v-if="category.svg_icon" 
                                class="w-4 h-4 text-gray-600"
                                v-html="category.svg_icon"
                            ></div>
                            <span>{{ category.name }}</span>
                            <span class="text-xs opacity-75">({{ category.lists_count || 0 }})</span>
                        </div>
                    </router-link>
                </div>
            </div>


            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-red-600">{{ error }}</p>
            </div>

            <!-- Lists Grid -->
            <div v-else-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="list in lists"
                    :key="list.id"
                    class="relative bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
                >
                    <!-- Absolute positioned link for ADA compliance -->
                    <a 
                        :href="getListUrl(list)"
                        class="absolute inset-0 z-10"
                        :aria-label="`View ${list.name} by ${getListOwnerName(list)}`"
                    >
                        <span class="sr-only">View {{ list.name }}</span>
                    </a>
                    
                    <!-- Cover Image -->
                    <div class="aspect-video bg-gray-100 relative">
                        <img 
                            v-if="list.featured_image_url" 
                            :src="list.featured_image_url" 
                            :alt="list.name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        
                        <!-- Category Badge -->
                        <div 
                            v-if="list.category"
                            class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium text-white"
                            :style="{ backgroundColor: list.category.color || '#3B82F6' }"
                        >
                            {{ list.category.name }}
                        </div>
                    </div>

                    <!-- List Info -->
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-1">{{ list.name }}</h3>
                        
                        <!-- Meta Info -->
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <div class="flex items-center space-x-4">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    {{ list.items_count || 0 }} items
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ list.views_count || 0 }} views
                                </span>
                            </div>
                        </div>
                        
                        <!-- Author -->
                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center">
                            <template v-if="list.owner_type === 'App\\Models\\Channel' && (list.owner || list.channel_data)">
                                <img
                                    :src="(list.owner || list.channel_data).avatar_url || getDefaultChannelAvatar((list.owner || list.channel_data).name)"
                                    :alt="(list.owner || list.channel_data).name"
                                    class="h-6 w-6 rounded-full mr-2"
                                />
                                <span class="text-sm text-gray-600">{{ (list.owner || list.channel_data).name }}</span>
                            </template>
                            <template v-else>
                                <SmartAvatar 
                                    :user="list.user" 
                                    :size="6"
                                    class="mr-2"
                                />
                                <span class="text-sm text-gray-600">{{ list.user.name }}</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No lists found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    No public lists available yet.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="mt-8">
                <Pagination 
                    :pagination="pagination" 
                    @change="fetchLists"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import SmartAvatar from '@/components/ui/SmartAvatar.vue'
import Pagination from '@/components/Pagination.vue'

// Helper functions
const getListUrl = (list) => {
    if (list.owner_type === 'App\\Models\\Channel' && (list.owner || list.channel_data)) {
        const channel = list.owner || list.channel_data
        return `/${channel.slug}/${list.slug}`
    }
    return `/up/@${list.user.custom_url || list.user.username}/${list.slug}`
}

const getListOwnerName = (list) => {
    if (list.owner_type === 'App\\Models\\Channel' && (list.owner || list.channel_data)) {
        const channel = list.owner || list.channel_data
        return channel.name
    }
    return list.user.name
}

const getDefaultChannelAvatar = (name) => {
    const initials = name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <rect width="100" height="100" fill="#6366f1"/>
        <text x="50" y="50" font-family="Arial, sans-serif" font-size="40" fill="white" text-anchor="middle" dominant-baseline="central">${initials}</text>
    </svg>`
    
    return `data:image/svg+xml;base64,${btoa(svg)}`
}

// State
const loading = ref(true)
const error = ref(null)
const lists = ref([])
const categories = ref([])

// Pagination
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0
})


// Methods
const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/list-categories/public')
        // Categories now include lists_count from the API
        categories.value = response.data.filter(cat => cat.lists_count > 0)
    } catch (err) {
        console.error('Error fetching categories:', err)
    }
}

const fetchLists = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            per_page: 12
        }

        const response = await axios.get('/api/lists/public', { params })
        
        lists.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            from: response.data.from,
            to: response.data.to,
            total: response.data.total
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load lists'
        console.error('Error fetching lists:', err)
    } finally {
        loading.value = false
    }
}


// Initialize
onMounted(async () => {
    document.title = 'Public Lists - ListKit'
    // Fetch both in parallel for better performance
    await Promise.all([
        fetchCategories(),
        fetchLists()
    ])
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