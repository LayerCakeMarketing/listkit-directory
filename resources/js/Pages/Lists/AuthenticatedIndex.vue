<template>
    <Head title="Browse Lists" />
    
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-32">
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Browse Lists</h1>
                    <p class="text-gray-600 mt-2">Discover community lists and collections</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link
                        href="/user/lists"
                        class="bg-purple-100 text-purple-700 px-4 py-2 rounded-md hover:bg-purple-200 transition-colors"
                    >
                        My Lists
                    </Link>
                    <Link
                        :href="route('lists.create')"
                        class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors"
                    >
                        + Create List
                    </Link>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Lists</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by title or description..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select
                            v-model="selectedCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            @change="handleCategoryFilter"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.slug">
                                {{ category.name }} ({{ category.lists_count || 0 }})
                            </option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select
                            v-model="sortOption"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            @change="handleSort"
                        >
                            <option value="latest">Latest Added</option>
                            <option value="views">Most Viewed</option>
                            <option value="items">Most Items</option>
                            <option value="name">Name (A-Z)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Browse by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <Link
                        v-for="category in categories.slice(0, 12)"
                        :key="category.id"
                        :href="`/lists/category/${category.slug}`"
                        class="bg-white rounded-lg p-4 text-center border hover:shadow-md hover:border-purple-300 transition-all cursor-pointer group"
                    >
                        <div class="w-8 h-8 mx-auto mb-2 rounded-full flex items-center justify-center"
                             :style="{ backgroundColor: category.color + '20' }">
                            <span class="text-lg" :style="{ color: category.color }">●</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 group-hover:text-purple-600 transition-colors">
                            {{ category.name }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ category.lists_count || 0 }}
                        </p>
                    </Link>
                </div>
            </div>

            <!-- Lists Grid -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Lists
                        <span class="text-lg text-gray-500 font-normal">({{ lists.total }} total)</span>
                    </h2>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>View:</span>
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'text-purple-600' : 'text-gray-400'"
                            class="p-1 hover:text-purple-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'text-purple-600' : 'text-gray-400'"
                            class="p-1 hover:text-purple-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid' && lists.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="list in lists.data" 
                        :key="list.id"
                        :href="getListUrl(list)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
                    >
                        <!-- Featured Image -->
                        <div v-if="getListImage(list)" class="h-40 bg-gray-200 overflow-hidden">
                            <img 
                                :src="getListImage(list)" 
                                :alt="list.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>
                        <div v-else class="h-40 bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center">
                            <span class="text-4xl text-white opacity-80">{{ list.name.charAt(0).toUpperCase() }}</span>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      :style="{ 
                                          backgroundColor: list.category?.color + '20', 
                                          color: list.category?.color || '#8B5CF6' 
                                      }">
                                    {{ list.category?.name || 'Uncategorized' }}
                                </span>
                                <span class="text-gray-500 text-sm">{{ list.items_count }} items</span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">{{ list.name }}</h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ truncateText(list.description) }}</p>
                            
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
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium">
                                            {{ list.user?.name?.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <span>{{ list.user?.name }}</span>
                                </div>
                                <span>{{ formatDate(list.updated_at) }}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- List View -->
                <div v-if="viewMode === 'list' && lists.data.length > 0" class="space-y-4">
                    <Link
                        v-for="list in lists.data" 
                        :key="list.id"
                        :href="getListUrl(list)"
                        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer block group"
                    >
                        <div class="flex items-start space-x-4">
                            <div v-if="getListImage(list)" class="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    :src="getListImage(list)" 
                                    :alt="list.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div v-else class="flex-shrink-0 w-24 h-24 bg-gradient-to-br from-purple-400 to-blue-500 rounded-lg flex items-center justify-center">
                                <span class="text-2xl text-white opacity-80">{{ list.name.charAt(0).toUpperCase() }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :style="{ 
                                                  backgroundColor: list.category?.color + '20', 
                                                  color: list.category?.color || '#8B5CF6' 
                                              }">
                                            {{ list.category?.name || 'Uncategorized' }}
                                        </span>
                                        <span class="text-gray-500 text-sm">{{ list.items_count }} items</span>
                                    </div>
                                </div>
                                
                                <h3 class="text-xl font-semibold text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">{{ list.name }}</h3>
                                <p class="text-gray-600 mb-3 line-clamp-2">{{ list.description }}</p>
                                
                                <!-- Tags -->
                                <div v-if="list.tags && list.tags.length > 0" class="flex flex-wrap gap-1 mb-3">
                                    <span
                                        v-for="tag in list.tags.slice(0, 5)"
                                        :key="tag.id"
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                        :style="{ 
                                            backgroundColor: tag.color + '20', 
                                            color: tag.color 
                                        }"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    <span v-if="list.tags.length > 5" class="text-xs text-gray-500">
                                        +{{ list.tags.length - 5 }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium">
                                                {{ list.user?.name?.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <span>by {{ list.user?.name }}</span>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ formatDate(list.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="lists.data.length === 0" class="text-center py-12">
                    <div class="text-gray-500 text-lg">No lists found.</div>
                    <p class="text-gray-400 mt-2">Try adjusting your search or filters.</p>
                    <Link
                        :href="route('lists.create')"
                        class="mt-4 inline-flex items-center text-purple-600 hover:text-purple-800 font-medium"
                    >
                        Create the first list →
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="lists.links && lists.links.length > 3" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <template v-for="(link, index) in lists.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-purple-600 text-white' 
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-purple-600 text-white' 
                                    : 'text-gray-400 cursor-not-allowed'
                            ]"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    lists: Object,
    categories: Array,
    filters: Object
})

const searchQuery = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || '')
const sortOption = ref(props.filters?.sort || 'latest')
const viewMode = ref('grid')

const getListImage = (list) => {
    return list.featured_image_url || list.featured_image || null
}

const getListUrl = (list) => {
    return `/${list.user?.custom_url || list.user?.username}/${list.slug}`
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const truncateText = (text, length = 100) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const handleSearch = () => {
    updateFilters()
}

const handleCategoryFilter = () => {
    updateFilters()
}

const handleSort = () => {
    updateFilters()
}

const updateFilters = () => {
    const filters = {}
    if (searchQuery.value) filters.search = searchQuery.value
    if (selectedCategory.value) filters.category = selectedCategory.value
    if (sortOption.value) filters.sort = sortOption.value
    
    router.get(route('lists.public.index'), filters, { preserveState: true })
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