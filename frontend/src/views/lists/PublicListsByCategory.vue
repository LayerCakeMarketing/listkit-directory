<template>
    <div class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-red-600">{{ error }}</p>
            </div>

            <!-- Category Not Found -->
            <div v-else-if="!category" class="text-center py-12">
                <h2 class="text-2xl font-bold text-gray-900">Category not found</h2>
                <p class="mt-2 text-gray-600">The category you're looking for doesn't exist.</p>
                <router-link to="/lists" class="mt-4 inline-block text-blue-600 hover:text-blue-700">
                    Browse all lists
                </router-link>
            </div>

            <!-- Category Content -->
            <div v-else>
                <!-- Category Header -->
                <div class="mb-8">
                    <div 
                        v-if="category.cover_image_url_computed" 
                        class="relative h-48 rounded-lg overflow-hidden mb-6"
                    >
                        <img 
                            :src="category.cover_image_url_computed" 
                            :alt="category.name"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 p-6 text-white">
                            <div class="flex items-center space-x-3 mb-2">
                                <div 
                                    v-if="category.svg_icon" 
                                    class="w-8 h-8 text-white"
                                    v-html="category.svg_icon"
                                ></div>
                                <h1 class="text-3xl font-bold">{{ category.name }}</h1>
                            </div>
                            <p v-if="category.description" class="text-sm opacity-90">{{ category.description }}</p>
                        </div>
                    </div>

                    <div v-else>
                        <div class="flex items-center space-x-3 mb-4">
                            <div 
                                v-if="category.svg_icon" 
                                class="w-10 h-10 text-gray-700"
                                v-html="category.svg_icon"
                            ></div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
                        </div>
                        <p v-if="category.description" class="text-gray-600">{{ category.description }}</p>
                    </div>

                    <!-- Random Quote -->
                    <div v-if="randomQuote" class="bg-gray-50 rounded-lg p-6 mb-6">
                        <svg class="w-8 h-8 text-gray-400 mb-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>
                        <p class="text-gray-700 italic">{{ randomQuote }}</p>
                    </div>

                    <!-- Breadcrumbs -->
                    <nav class="flex items-center space-x-2 text-sm text-gray-500">
                        <router-link to="/lists" class="hover:text-gray-700">All Lists</router-link>
                        <span>/</span>
                        <span class="text-gray-900">{{ category.name }}</span>
                    </nav>
                </div>

                <!-- Lists Grid -->
                <div v-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="list in lists"
                        :key="list.id"
                        class="relative bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <!-- Absolute positioned link for ADA compliance -->
                        <a 
                            :href="`/up/@${list.user.custom_url || list.user.username}/${list.slug}`"
                            class="absolute inset-0 z-10"
                            :aria-label="`View ${list.name} by ${list.user.name}`"
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
                        </div>

                        <!-- List Info -->
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-1">{{ list.name }}</h3>
                            <p v-if="list.description" class="text-sm text-gray-600 line-clamp-2 mb-3">{{ list.description }}</p>
                            
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
                                <SmartAvatar 
                                    :user="list.user" 
                                    :size="6"
                                    class="mr-2"
                                />
                                <span class="text-sm text-gray-600">{{ list.user.name }}</span>
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
                        No public lists in {{ category.name }} category yet.
                    </p>
                    <router-link to="/lists" class="mt-4 inline-block text-blue-600 hover:text-blue-700">
                        Browse all lists
                    </router-link>
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
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import SmartAvatar from '@/components/ui/SmartAvatar.vue'
import Pagination from '@/components/Pagination.vue'

const route = useRoute()

// State
const loading = ref(true)
const error = ref(null)
const category = ref(null)
const lists = ref([])
const randomQuote = ref(null)

// Pagination
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0
})

// Methods
const fetchCategory = async () => {
    try {
        // First get all categories to find the one with matching slug
        const response = await axios.get('/api/list-categories/public')
        const matchingCategory = response.data.find(cat => cat.slug === route.params.categorySlug)
        
        if (!matchingCategory) {
            category.value = null
            return
        }

        // For now, just use the basic category info since we don't have a public category detail endpoint
        category.value = matchingCategory
    } catch (err) {
        console.error('Error fetching category:', err)
        error.value = 'Failed to load category'
    }
}

const fetchLists = async (page = 1) => {
    if (!category.value) return

    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            per_page: 12,
            category_id: category.value.id
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

// Watch for route changes
watch(() => route.params.categorySlug, async () => {
    loading.value = true
    await fetchCategory()
    if (category.value) {
        await fetchLists()
    }
    loading.value = false
})

// Initialize
onMounted(async () => {
    await fetchCategory()
    if (category.value) {
        document.title = `${category.value.name} Lists - ListKit`
        await fetchLists()
    } else {
        loading.value = false
    }
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