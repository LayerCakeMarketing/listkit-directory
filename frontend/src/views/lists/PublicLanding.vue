<template>
    <div>
        <!-- Hero Section -->
        <section class="relative">
            <!-- Background Image -->
            <div v-if="marketingContent.image_url" class="absolute inset-0">
                <img 
                    :src="marketingContent.image_url" 
                    alt="Lists hero background" 
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            </div>
            <div v-else class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600"></div>
            
            <!-- Content -->
            <div class="relative z-10 py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 text-white">
                        {{ marketingContent.heading || 'Create and Share Lists' }}
                    </h1>
                    <p class="text-xl mb-8 max-w-3xl mx-auto text-white/90">
                        {{ marketingContent.paragraph || 'Organize your favorite places into beautiful, shareable lists. Collaborate with friends and discover curated collections from experts.' }}
                    </p>
                    <div class="space-x-4">
                        <router-link
                            v-if="!authStore.user"
                            to="/register"
                            class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            Join & Create Lists
                        </router-link>
                        <router-link
                            v-if="authStore.user"
                            to="/mylists"
                            class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            My Lists
                        </router-link>
                        <a
                            href="#featured"
                            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-colors inline-block"
                        >
                            Browse Featured Lists
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-red-600">{{ error }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <template v-else>
            <!-- Featured Lists Section -->
            <section id="featured" class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Featured Lists</h2>
                    
                    <div v-if="featuredLists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <router-link
                            v-for="list in featuredLists"
                            :key="list.id"
                            :to="`/@${list.user.custom_url || list.user.username}/${list.slug}`"
                            class="block bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden"
                        >
                            <div class="h-48 bg-gray-200 overflow-hidden">
                                <img
                                    v-if="list.featured_image_url"
                                    :src="list.featured_image_url"
                                    :alt="list.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ list.name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">by {{ list.user.name }}</p>
                                <p v-if="list.description" class="text-sm text-gray-500 line-clamp-2 mb-3">
                                    {{ list.description }}
                                </p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span>{{ list.items_count }} items</span>
                                    <span>{{ list.view_count }} views</span>
                                </div>
                            </div>
                        </router-link>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="text-gray-500 text-lg">No featured lists yet.</div>
                        <p class="text-gray-400 mt-2">Check back soon for curated recommendations!</p>
                    </div>
                </div>
            </section>

            <!-- Browse by Category -->
            <section class="py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Browse by Category</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <router-link
                            v-for="category in categories"
                            :key="category.id"
                            :to="`/lists/category/${category.slug}`"
                            class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow text-center"
                        >
                            <div 
                                v-if="category.svg_icon" 
                                class="w-12 h-12 mx-auto mb-3 text-indigo-600"
                                v-html="category.svg_icon"
                            ></div>
                            <div v-else class="w-12 h-12 mx-auto mb-3 text-indigo-600">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-900">{{ category.name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ category.lists_count || 0 }} lists</p>
                        </router-link>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-16 bg-indigo-600 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h3 class="text-3xl font-bold mb-4">Ready to Create Your Own Lists?</h3>
                    <p class="text-indigo-100 max-w-2xl mx-auto mb-8">
                        Join our community to start organizing your favorite places and share your discoveries.
                    </p>
                    
                    <div class="space-x-4">
                        <router-link
                            v-if="!authStore.user"
                            to="/register"
                            class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            Sign Up Free
                        </router-link>
                        <router-link
                            v-if="authStore.user"
                            to="/mylists"
                            class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
                        >
                            Create a List
                        </router-link>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const featuredLists = ref([])
const categories = ref([])
const marketingContent = ref({
    heading: 'Create and Share Lists',
    paragraph: 'Organize your favorite places into beautiful, shareable lists. Collaborate with friends and discover curated collections from experts.',
    image_url: null
})

// Methods
const fetchMarketingContent = async () => {
    try {
        const response = await axios.get('/api/marketing-pages/lists')
        if (response.data) {
            marketingContent.value = {
                heading: response.data.heading || marketingContent.value.heading,
                paragraph: response.data.paragraph || marketingContent.value.paragraph,
                image_url: response.data.image_url || null
            }
        }
    } catch (err) {
        console.error('Error fetching marketing content:', err)
        // Use defaults if fetch fails
    }
}

const fetchData = async () => {
    loading.value = true
    error.value = null

    try {
        // Fetch marketing content and lists data in parallel
        const [listsResponse, categoriesResponse] = await Promise.all([
            axios.get('/api/lists/discover', { params: { limit: 6 } }),
            axios.get('/api/list-categories'),
            fetchMarketingContent()
        ])
        
        featuredLists.value = listsResponse.data.data || []
        categories.value = categoriesResponse.data || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load content'
        console.error('Error fetching data:', err)
    } finally {
        loading.value = false
    }
}

// Lifecycle
onMounted(() => {
    fetchData()
})
</script>