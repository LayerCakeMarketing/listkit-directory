<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-32">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Page Not Found</h1>
                <p class="text-gray-600 mb-8">The page you're looking for doesn't exist or has been removed.</p>
                <router-link to="/" class="text-blue-600 hover:text-blue-800">
                    Return to Home
                </router-link>
            </div>
        </div>

        <!-- Page Content -->
        <div v-else-if="page" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <article class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-8">
                    <!-- Page Title -->
                    <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ page.title }}</h1>

                    <!-- Page Content -->
                    <div 
                        v-html="page.content" 
                        class="prose prose-lg max-w-none"
                    ></div>

                    <!-- Last Updated -->
                    <div class="mt-12 pt-8 border-t border-gray-200">
                        <p class="text-sm text-gray-500">
                            Last updated: {{ formatDate(page.updated_at) }}
                        </p>
                    </div>
                </div>
            </article>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">About Us</h3>
                        <p class="text-gray-400">Your trusted directory for finding the best places in your area.</p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li>
                                <router-link to="/about-us" class="text-gray-400 hover:text-white">
                                    About Us
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/privacy-policy" class="text-gray-400 hover:text-white">
                                    Privacy Policy
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/terms-of-service" class="text-gray-400 hover:text-white">
                                    Terms of Service
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/contact-us" class="text-gray-400 hover:text-white">
                                    Contact Us
                                </router-link>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contact</h3>
                        <p class="text-gray-400">Email: info@example.com</p>
                        <p class="text-gray-400">Phone: (555) 123-4567</p>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                    <p>&copy; {{ new Date().getFullYear() }} All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

// State
const page = ref(null)
const loading = ref(true)
const error = ref(false)

// Computed
const slug = computed(() => route.params.slug)

// Watch for route changes
watch(() => route.params.slug, () => {
    fetchPage()
})

// Methods
const fetchPage = async () => {
    loading.value = true
    error.value = false
    
    console.log('Fetching page with slug:', slug.value)
    
    try {
        const response = await axios.get(`/api/${slug.value}`)
        console.log('Page response:', response.data)
        page.value = response.data.data
    } catch (err) {
        console.error('Error fetching page:', err)
        console.error('Error response:', err.response?.data)
        error.value = true
    } finally {
        loading.value = false
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date)
}

// Lifecycle
onMounted(() => {
    fetchPage()
})
</script>

<style scoped>
/* Prose styles for content */
:deep(.prose) {
    max-width: none;
}

:deep(.prose h1) {
    @apply text-3xl font-bold text-gray-900 mb-6;
}

:deep(.prose h2) {
    @apply text-2xl font-bold text-gray-900 mb-4 mt-8;
}

:deep(.prose h3) {
    @apply text-xl font-bold text-gray-900 mb-3 mt-6;
}

:deep(.prose p) {
    @apply text-gray-700 mb-4 leading-relaxed;
}

:deep(.prose ul) {
    @apply list-disc list-inside mb-4 ml-4;
}

:deep(.prose ol) {
    @apply list-decimal list-inside mb-4 ml-4;
}

:deep(.prose li) {
    @apply text-gray-700 mb-2;
}

:deep(.prose a) {
    @apply text-blue-600 hover:text-blue-800 underline;
}

:deep(.prose blockquote) {
    @apply border-l-4 border-gray-300 pl-4 italic my-4;
}

:deep(.prose strong) {
    @apply font-bold;
}

:deep(.prose em) {
    @apply italic;
}
</style>