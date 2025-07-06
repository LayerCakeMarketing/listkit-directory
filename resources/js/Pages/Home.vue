<template>
    <Head title="Home" />

    <AuthenticatedLayout>
        <!-- Remove the default header slot to use full-width layout -->
        <div class="min-h-screen bg-gray-100 pt-16">
            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-6">
                <div class="flex gap-4">
                    <!-- Left Sidebar (Hidden on mobile) -->
                    <aside class="hidden lg:block w-64 sticky top-[6rem] h-[calc(100vh-6rem)] overflow-y-auto z-10">

                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Your Stats</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Following</span>
                                    <span class="font-medium">{{ $page.props.auth.user.following_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Followers</span>
                                    <span class="font-medium">{{ $page.props.auth.user.followers_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Lists</span>
                                    <span class="font-medium">{{ $page.props.auth.user.lists_count || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Categories</h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="category in categories"
                                    :key="category.id"
                                    :href="`/lists/category/${category.slug}`"
                                    class="block py-2 px-3 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition"
                                >
                                    <div class="flex justify-between items-center">
                                        <span>{{ category.name }}</span>
                                        <span class="text-xs text-gray-500">{{ category.lists_count }}</span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </aside>

                    <!-- Center Feed -->
                    <main class="flex-1 max-w-2xl">
                        <!-- Feed Header -->
                        <div class="bg-white rounded-lg shadow mb-4 p-4 mt-2">
                            <h2 class="text-xl font-bold text-gray-900">Your Feed</h2>
                            <p class="text-sm text-gray-600 mt-1">Latest from people you follow</p>
                        </div>

                        <!-- Unified Feed -->
                        <div v-if="feedItems.data.length > 0" class="space-y-4">
                            <div
                                v-for="item in feedItems.data"
                                :key="`${item.feed_type}-${item.id}`"
                                class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow"
                            >
                                <!-- List Item -->
                                <div v-if="item.feed_type === 'list'" class="p-6">
                                    <!-- List Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-medium">
                                                    List
                                                </span>
                                                <button
                                                    @click="openQuickView(item)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                                >
                                                    Quick View
                                                </button>
                                            </div>
                                            <Link 
                                                :href="`/${item.user?.username || item.user?.custom_url}/${item.slug}`"
                                                class="text-lg font-semibold text-gray-900 hover:text-blue-600"
                                            >
                                                {{ item.name }}
                                            </Link>
                                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                                                <Link 
                                                    :href="`/${item.user?.username || item.user?.custom_url}`"
                                                    class="hover:text-gray-900"
                                                >
                                                    by {{ item.user?.name }}
                                                </Link>
                                                <span>•</span>
                                                <span>{{ formatDate(item.created_at) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="getItemImage(item)" class="ml-4 flex-shrink-0">
                                            <img 
                                                :src="getItemImage(item)"
                                                :alt="item.name"
                                                class="w-24 h-24 rounded-lg object-cover"
                                            />
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <p v-if="item.description" class="text-gray-700 mb-3">
                                        {{ truncate(item.description, 200) }}
                                    </p>

                                    <!-- Meta Info -->
                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <div v-if="item.category" class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            {{ item.category.name }}
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            {{ item.items_count || 0 }} items
                                        </div>
                                        <div v-if="item.views_count > 0" class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            {{ item.views_count }} views
                                        </div>
                                    </div>

                                    <!-- Tags -->
                                    <div v-if="item.tags && item.tags.length > 0" class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in item.tags.slice(0, 5)"
                                            :key="tag.id"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                                            :style="{ backgroundColor: tag.color || '#6B7280' }"
                                        >
                                            {{ tag.name }}
                                        </span>
                                        <span v-if="item.tags.length > 5" class="text-xs text-gray-500">
                                            +{{ item.tags.length - 5 }} more
                                        </span>
                                    </div>
                                </div>

                                <!-- Place Item -->
                                <div v-else-if="item.feed_type === 'place'" class="p-6">
                                    <!-- Place Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium">
                                                    Place
                                                </span>
                                                <button
                                                    @click="openQuickView(item)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                                >
                                                    Quick View
                                                </button>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                                                <span v-if="item.category">
                                                    {{ item.category.parent?.name }} › {{ item.category.name }}
                                                </span>
                                                <span>•</span>
                                                <span>{{ formatDate(item.created_at) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="item.featured_image" class="ml-4 flex-shrink-0">
                                            <img 
                                                :src="item.featured_image"
                                                :alt="item.title"
                                                class="w-24 h-24 rounded-lg object-cover"
                                            />
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <p v-if="item.description" class="text-gray-700 mb-3">
                                        {{ truncate(item.description, 200) }}
                                    </p>

                                    <!-- Location -->
                                    <div v-if="item.location" class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ item.location.city }}, {{ item.location.state }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="bg-white rounded-lg shadow p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No lists yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Start following people to see their lists here.</p>
                            <div class="mt-6">
                                <Link
                                    href="/lists"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Explore Lists
                                </Link>
                            </div>
                        </div>

                        <!-- Load More -->
                        <div v-if="feedItems.next_page_url" class="mt-8 text-center">
                            <button
                                @click="loadMore"
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                            >
                                <span v-if="loading">Loading...</span>
                                <span v-else>Load More</span>
                            </button>
                        </div>
                    </main>

                    <!-- Right Sidebar (Hidden on mobile) -->
                    <aside class="hidden lg:block w-64 sticky top-[6rem] h-[calc(100vh-6rem)] overflow-y-auto z-10">

                        <!-- Trending Tags -->
                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Trending Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in trendingTags"
                                    :key="tag.id"
                                    :href="`/lists?tag=${tag.slug}`"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white hover:opacity-90 transition"
                                    :style="{ backgroundColor: tag.color || '#6B7280' }"
                                >
                                    {{ tag.name }}
                                    <span class="ml-1 text-xs opacity-75">({{ tag.lists_count }})</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Suggestions -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <Link
                                    href="/lists/create"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create New List
                                </Link>
                                <Link
                                    href="/places"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Browse Places
                                </Link>
                                <Link
                                    :href="`/${$page.props.auth.user.username}`"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    View Your Profile
                                </Link>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <!-- Quick View Modal -->
        <QuickViewModal 
            :show="showQuickView" 
            :item="quickViewItem" 
            @close="closeQuickView" 
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import QuickViewModal from '@/Components/QuickViewModal.vue'

const props = defineProps({
    feedItems: Object, // Unified feed containing both lists and places
    categories: Array,
    trendingTags: Array,
})

const loading = ref(false)
const showQuickView = ref(false)
const quickViewItem = ref(null)

const formatDate = (date) => {
    const d = new Date(date)
    const now = new Date()
    const diffInHours = Math.floor((now - d) / (1000 * 60 * 60))
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor((now - d) / (1000 * 60))
        return diffInMinutes <= 1 ? 'just now' : `${diffInMinutes}m ago`
    } else if (diffInHours < 24) {
        return `${diffInHours}h ago`
    } else if (diffInHours < 168) { // 7 days
        const diffInDays = Math.floor(diffInHours / 24)
        return `${diffInDays}d ago`
    } else {
        return d.toLocaleDateString()
    }
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const getListImage = (list) => {
    return list.featured_image_url || list.featured_image || null
}

const openQuickView = (item) => {
    quickViewItem.value = item
    showQuickView.value = true
}

const closeQuickView = () => {
    showQuickView.value = false
    quickViewItem.value = null
}

const getItemTypeLabel = (item) => {
    return item.feed_type === 'list' ? 'List' : 'Place'
}

const getItemImage = (item) => {
    if (item.feed_type === 'list') {
        return getListImage(item)
    } else if (item.feed_type === 'place') {
        return item.featured_image || null
    }
    return null
}

const loadMore = async () => {
    if (!props.feedItems.next_page_url || loading.value) return
    
    loading.value = true
    
    try {
        const response = await window.axios.get(props.feedItems.next_page_url)
        
        // Append new items to existing data
        router.reload({
            only: ['feedItems'],
            data: {
                feedItems: {
                    ...props.feedItems,
                    data: [...props.feedItems.data, ...response.data.data],
                    next_page_url: response.data.next_page_url,
                    current_page: response.data.current_page,
                }
            },
            preserveScroll: true,
            preserveState: true,
        })
    } catch (error) {
        console.error('Error loading more:', error)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
/* Custom scrollbar for sidebars */
aside::-webkit-scrollbar {
    width: 4px;
}

aside::-webkit-scrollbar-track {
    background: transparent;
}

aside::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 2px;
}

aside::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>