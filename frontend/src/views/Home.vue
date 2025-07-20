<template>
    <AuthenticatedLayout>
        <!-- Remove the default header slot to use full-width layout -->
        <div class="min-h-screen bg-gray-100 pt-16">
            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-6">
                <div class="flex gap-4">
                    <!-- Left Sidebar (Hidden on mobile) -->
                    <aside class="hidden lg:block w-64 sticky top-[6rem] h-[calc(100vh-6rem)] overflow-y-auto z-10">

                        <div v-if="user" class="bg-white rounded-lg shadow p-4 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Your Stats</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Following</span>
                                    <span class="font-medium">{{ user.following_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Followers</span>
                                    <span class="font-medium">{{ user.followers_count || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Lists</span>
                                    <span class="font-medium">{{ user.lists_count || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Categories</h3>
                            <div class="space-y-2">
                                <router-link
                                    v-for="category in categories"
                                    :key="category.id"
                                    :to="`/lists/category/${category.slug}`"
                                    class="block py-2 px-3 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition"
                                >
                                    <div class="flex justify-between items-center">
                                        <span>{{ category.name }}</span>
                                        <span class="text-xs text-gray-500">{{ category.lists_count }}</span>
                                    </div>
                                </router-link>
                            </div>
                        </div>
                    </aside>

                    <!-- Center Feed -->
                    <main class="flex-1 max-w-2xl">
                        <!-- Feed Header with Refresh -->
                        <div class="bg-white rounded-lg shadow mb-4 p-4 mt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Your Feed</h2>
                                    <p class="text-sm text-gray-600 mt-1">Latest from people you follow</p>
                                </div>
                                <button
                                    @click="refreshFeed"
                                    :disabled="loading"
                                    class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                                    title="Refresh feed"
                                >
                                    <svg 
                                        class="w-5 h-5" 
                                        :class="{ 'animate-spin': loading }"
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Post Composer -->
                        <PostComposer class="mb-4" />

                        <!-- Loading State -->
                        <div v-if="loading && feedItems.length === 0" class="space-y-4">
                            <FeedItemSkeleton v-for="i in 3" :key="i" />
                        </div>

                        <!-- Unified Feed -->
                        <div v-else-if="feedItems.length > 0" class="space-y-4">
                            <div
                                v-for="item in feedItems"
                                :key="`${item.feed_type}-${item.id}`"
                            >
                                <!-- Post Item -->
                                <PostItem 
                                    v-if="item.feed_type === 'post'"
                                    :post="item"
                                    @updated="handlePostUpdated"
                                    @deleted="handlePostDeleted"
                                />
                                
                                <!-- List Item -->
                                <div v-else-if="item.feed_type === 'list'" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
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
                                            <router-link 
                                                :to="`/up/@${item.user?.custom_url || item.user?.username}/${item.slug}`"
                                                class="text-lg font-semibold text-gray-900 hover:text-blue-600"
                                            >
                                                {{ item.name }}
                                            </router-link>
                                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                                                <router-link 
                                                    :to="`/up/@${item.user?.custom_url || item.user?.username}`"
                                                    class="hover:text-gray-900"
                                                >
                                                    by {{ item.user?.name }}
                                                </router-link>
                                                <span>•</span>
                                                <span>{{ formatDate(item.created_at) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="getItemImage(item)" class="ml-4 flex-shrink-0">
                                            <ProgressiveImage
                                                :src="getItemImage(item)"
                                                :alt="item.name"
                                                container-class="w-24 h-24 rounded-lg"
                                                image-class="w-full h-full object-cover rounded-lg"
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
                                <div v-else-if="item.feed_type === 'place'" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
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
                                            <ProgressiveImage
                                                :src="item.featured_image"
                                                :alt="item.title"
                                                container-class="w-24 h-24 rounded-lg"
                                                image-class="w-full h-full object-cover rounded-lg"
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
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Inconceivable - nothing here?</h3>
                            <p class="mt-1 text-sm text-gray-500">Start following people, places and channels to see their lists and posts here.</p>
                            <div class="mt-6">
                                <router-link
                                    to="/lists"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Explore
                                </router-link>
                            </div>
                        </div>

                        <!-- Infinite Scroll Target -->
                        <div 
                            ref="infiniteScrollTarget" 
                            class="mt-8 py-4"
                            v-if="hasMore"
                        >
                            <div v-if="loadingMore" class="flex justify-center">
                                <div class="inline-flex items-center px-4 py-2 text-sm text-gray-700">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading more...
                                </div>
                            </div>
                        </div>
                        
                        <!-- End of Feed Message -->
                        <div v-else-if="feedItems.length > 0" class="mt-8 py-8 text-center text-gray-500">
                            <p>You've reached the end of your feed</p>
                        </div>
                    </main>

                    <!-- Right Sidebar (Hidden on mobile) -->
                    <aside class="hidden lg:block w-64 sticky top-[6rem] h-[calc(100vh-6rem)] overflow-y-auto z-10">

                        <!-- Trending Tags -->
                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Trending Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <router-link
                                    v-for="tag in trendingTags"
                                    :key="tag.id"
                                    :to="`/lists?tag=${tag.slug}`"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white hover:opacity-90 transition"
                                    :style="{ backgroundColor: tag.color || '#6B7280' }"
                                >
                                    {{ tag.name }}
                                    <span class="ml-1 text-xs opacity-75">({{ tag.lists_count }})</span>
                                </router-link>
                            </div>
                        </div>

                        <!-- Suggestions -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <router-link
                                    to="/lists/create"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create New List
                                </router-link>
                                <router-link
                                    to="/places"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Browse Places
                                </router-link>
                                <router-link
                                    :to="`/up/@${user.custom_url || user.username}`"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-800"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    View Your Profile
                                </router-link>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useFeedStore } from '@/stores/feed'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import AuthenticatedLayout from '@/components/layouts/AuthenticatedLayout.vue'
import QuickViewModal from '@/components/QuickViewModal.vue'
import FeedItemSkeleton from '@/components/FeedItemSkeleton.vue'
import ProgressiveImage from '@/components/ProgressiveImage.vue'
import PostComposer from '@/components/PostComposer.vue'
import PostItem from '@/components/PostItem.vue'
import axios from 'axios'

const authStore = useAuthStore()
const feedStore = useFeedStore()
const user = computed(() => authStore.user)

// Set page title using document
onMounted(() => {
  document.title = 'Home - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})

// Use feed store for state management
const loading = computed(() => feedStore.isLoading)
const loadingMore = computed(() => feedStore.isLoadingMore)
const feedItems = computed(() => feedStore.sortedPosts)
const hasMore = computed(() => feedStore.hasMore)
const categories = ref([])
const trendingTags = ref([])
const showQuickView = ref(false)
const quickViewItem = ref(null)

// Infinite scroll setup
const { target: infiniteScrollTarget } = useInfiniteScroll(async () => {
  if (!loadingMore.value && hasMore.value) {
    await feedStore.loadMore()
  }
}, {
  threshold: 0.1,
  rootMargin: '200px'
})

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

const loadInitialData = async () => {
    try {
        // Load feed from store (uses cache if available)
        await feedStore.loadFeed()
        
        // Load categories and trending tags separately
        const response = await axios.get('/api/home/metadata')
        categories.value = response.data.categories || []
        trendingTags.value = response.data.trendingTags || []
    } catch (error) {
        console.error('Error loading initial data:', error)
    }
}

// Pull to refresh functionality
const refreshFeed = async () => {
    await feedStore.loadFeed(true) // Force refresh
}

// Watch for feed errors
watch(() => feedStore.error, (error) => {
    if (error) {
        console.error('Feed error:', error)
        // You could show a toast notification here
    }
})

// Handle post updated
const handlePostUpdated = (updatedPost) => {
    feedStore.updatePost(updatedPost.id, updatedPost)
}

// Handle post deleted
const handlePostDeleted = () => {
    // Post already removed by PostItem component
}

onMounted(() => {
    loadInitialData()
})
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