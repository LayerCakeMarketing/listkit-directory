<template>
    <Head title="Discover Amazing Lists" />
    
    <PublicLayout :can-register="canRegister">
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    Discover Curated Lists
                </h1>
                <p class="text-xl mb-8 max-w-3xl mx-auto">
                    Explore hand-crafted lists of restaurants, activities, and local gems. 
                    Get inspired by the community's favorites and create your own collections.
                </p>
                <div class="space-x-4">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Start Creating Lists
                    </Link>
                    <Link
                        href="#featured"
                        class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors"
                    >
                        Browse Featured Lists
                    </Link>
                </div>
            </div>
        </section>

        <!-- Top Categories -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Popular Categories</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Discover lists organized by interest and category
                    </p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6" v-if="topCategories?.length > 0">
                    <Link 
                        v-for="category in topCategories.slice(0, 6)" 
                        :key="category.id"
                        :href="`/lists/category/${category.slug}`"
                        class="bg-gray-50 rounded-lg p-6 text-center hover:shadow-lg hover:bg-gray-100 transition-all cursor-pointer group"
                    >
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center"
                             :style="{ backgroundColor: category.color + '20' }">
                            <span class="text-2xl" :style="{ color: category.color }">●</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">
                            {{ category.name }}
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ category.lists_count || 0 }} lists
                        </p>
                    </Link>
                </div>

                <div class="text-center mt-8">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('lists.public.index')"
                        class="inline-flex items-center text-purple-600 hover:text-purple-800 font-medium"
                    >
                        View All Categories
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Featured Lists -->
        <section id="featured" class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Featured Lists</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Community favorites and editor's picks
                    </p>
                </div>
                
                <div v-if="featuredLists?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="list in featuredLists" 
                        :key="list.id"
                        @click="handleListClick(list)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow cursor-pointer group"
                    >
                        <!-- Featured Image -->
                        <div v-if="getListImage(list)" class="h-48 bg-gray-200 overflow-hidden">
                            <img 
                                :src="getListImage(list)" 
                                :alt="list.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>
                        <div v-else class="h-48 bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center">
                            <span class="text-6xl text-white opacity-80">{{ list.name.charAt(0).toUpperCase() }}</span>
                        </div>

                        <div class="p-6">
                            <!-- Category and Stats -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                      :style="{ 
                                          backgroundColor: list.category?.color + '20', 
                                          color: list.category?.color || '#8B5CF6' 
                                      }">
                                    {{ list.category?.name || 'Uncategorized' }}
                                </span>
                                <div class="flex items-center text-gray-500 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    {{ list.items_count }} items
                                </div>
                            </div>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">{{ list.name }}</h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ truncateText(list.description) }}</p>
                            
                            <!-- Tags -->
                            <div v-if="list.tags && list.tags.length > 0" class="flex flex-wrap gap-1 mb-4">
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

                            <!-- Creator -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-600">
                                    <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-xs font-medium text-purple-600">
                                            {{ list.user?.name?.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <span>by {{ list.user?.name }}</span>
                                </div>
                                <span class="text-xs text-gray-500">{{ formatDate(list.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <div class="text-gray-500 text-lg">No featured lists yet.</div>
                    <p class="text-gray-400 mt-2">Check back soon for community highlights!</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-purple-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h3 class="text-3xl font-bold mb-4">Ready to Create Your Own Lists?</h3>
                <p class="text-purple-100 max-w-2xl mx-auto mb-8">
                    Join thousands of list makers sharing their favorite places, products, and experiences.
                </p>
                
                <div class="space-x-4">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Sign Up Free
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('lists.public.index')"
                        class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                    >
                        Browse All Lists
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('lists.create')"
                        class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors"
                    >
                        Create a List
                    </Link>
                </div>
            </div>
        </section>

        <!-- Login Prompt for Non-Authenticated Users -->
        <div v-if="!$page.props.auth?.user" class="fixed bottom-4 right-4 bg-purple-600 text-white px-6 py-3 rounded-lg shadow-lg max-w-sm">
            <div class="flex items-center space-x-3">
                <div class="flex-1">
                    <div class="font-medium text-sm">Sign up to view full lists!</div>
                    <div class="text-xs text-purple-100">Create and save your favorites</div>
                </div>
                <Link
                    :href="route('register')"
                    class="bg-white text-purple-600 px-3 py-1 rounded text-sm font-medium hover:bg-gray-100 transition-colors"
                >
                    Join
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    featuredLists: Array,
    topCategories: Array,
    canRegister: {
        type: Boolean,
        default: true
    }
})

const page = usePage()

const getListImage = (list) => {
    return list.featured_image_url || list.featured_image || null
}

const getListUrl = (list) => {
    return `/${list.user?.custom_url || list.user?.username}/${list.slug}`
}

const handleListClick = (list) => {
    // Check if user is authenticated
    if (!page.props.auth || !page.props.auth.user) {
        // Redirect to login with message
        router.visit('/login', {
            data: {
                message: 'Sign up to view full lists and save your favorites.',
                redirect_to: getListUrl(list)
            }
        })
        return
    }
    
    // User is authenticated, navigate to list
    router.visit(getListUrl(list))
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const truncateText = (text, length = 120) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
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