<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ pageTitle }} Page Settings</h2>
                        <p class="mt-1 text-sm text-gray-600">Customize the marketing content for the {{ pageTitle.toLowerCase() }} page</p>
                    </div>
                    <router-link
                        :to="`/${pageKey}`"
                        target="_blank"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        View Page
                    </router-link>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form @submit.prevent="saveSettings" class="p-6 space-y-6">
                    <!-- Cover Image -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                        <div class="space-y-4">
                            <div v-if="form.cover_image_id || page.image_url" class="relative inline-block">
                                <img 
                                    :src="currentImageUrl" 
                                    alt="Cover Image" 
                                    class="w-full max-w-2xl h-64 object-cover rounded-lg shadow-md"
                                />
                                <button
                                    type="button"
                                    @click="removeCoverImage"
                                    class="absolute top-2 right-2 bg-red-600 text-white p-1.5 rounded-full hover:bg-red-700 shadow-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Cloudflare Upload Component -->
                            <div v-if="!form.cover_image_id && !page.image_url">
                                <CloudflareDragDropUploader
                                    :max-files="1"
                                    :max-file-size="10485760"
                                    context="marketing"
                                    entity-type="marketing_page"
                                    :entity-id="pageKey"
                                    accepted-types="image/jpeg,image/png,image/webp"
                                    @upload-success="handleImageUploaded"
                                    @upload-error="handleUploadError"
                                />
                                <p class="text-xs text-gray-500 mt-2">Recommended: 1920x1080px or wider for best results</p>
                            </div>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
                        <input
                            v-model="form.heading"
                            type="text"
                            maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :placeholder="`e.g., ${getDefaultHeading()}`"
                        />
                        <p class="text-xs text-gray-500 mt-1">{{ form.heading?.length || 0 }}/255 characters</p>
                    </div>

                    <!-- Paragraph -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Paragraph</label>
                        <textarea
                            v-model="form.paragraph"
                            rows="4"
                            maxlength="1000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :placeholder="`e.g., ${getDefaultParagraph()}`"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">{{ form.paragraph?.length || 0 }}/1000 characters</p>
                    </div>

                    <!-- Live Preview -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Live Preview</h3>
                        <div class="border rounded-lg overflow-hidden shadow-lg bg-gray-50">
                            <div class="relative">
                                <!-- Cover Image Preview -->
                                <div v-if="currentImageUrl" class="relative h-80 bg-gray-200">
                                    <img 
                                        :src="currentImageUrl" 
                                        alt="Cover" 
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                </div>
                                <div v-else class="h-80 bg-gradient-to-br from-blue-500 to-purple-600"></div>
                                
                                <!-- Content Overlay -->
                                <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                                    <h1 class="text-4xl font-bold mb-3">
                                        {{ form.heading || getDefaultHeading() }}
                                    </h1>
                                    <p class="text-lg max-w-3xl">
                                        {{ form.paragraph || getDefaultParagraph() }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Page Content Preview -->
                            <div class="p-8 bg-white">
                                <p class="text-gray-500 text-center">
                                    [{{ pageTitle }} page content would appear here]
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <router-link
                            to="/admin/marketing-pages"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </router-link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import { useNotification } from '@/composables/useNotification'

const route = useRoute()
const { showSuccess, showError } = useNotification()

// State
const loading = ref(false)
const processing = ref(false)
const page = ref({})
const pageKey = computed(() => route.params.page)

// Form
const form = reactive({
    heading: '',
    paragraph: '',
    cover_image_id: null,
    remove_cover_image: false
})

// Page title mapping
const pageTitle = computed(() => {
    const titles = {
        places: 'Places',
        lists: 'Lists',
        register: 'Register'
    }
    return titles[pageKey.value] || 'Marketing'
})

// Computed
const currentImageUrl = computed(() => {
    if (form.cover_image_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${form.cover_image_id}/lgformat`
    }
    return page.value?.image_url || null
})

// Default content
function getDefaultHeading() {
    const defaults = {
        places: 'Discover Amazing Places',
        lists: 'Create and Share Lists',
        register: 'Join Our Community'
    }
    return defaults[pageKey.value] || 'Welcome'
}

function getDefaultParagraph() {
    const defaults = {
        places: 'Explore the best restaurants, shops, and attractions in your area.',
        lists: 'Organize your favorite places into beautiful, shareable lists.',
        register: 'Sign up to save your favorite places and create lists.'
    }
    return defaults[pageKey.value] || 'Get started today.'
}

// Methods
const fetchPage = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/api/admin/marketing-pages/key/${pageKey.value}`)
        page.value = response.data
        
        // Update form with page data
        form.heading = page.value.heading || ''
        form.paragraph = page.value.paragraph || ''
        form.cover_image_id = page.value.cover_image_id || null
        form.remove_cover_image = false
    } catch (error) {
        console.error('Error fetching page:', error)
        showError('Failed to load page settings')
    } finally {
        loading.value = false
    }
}

const handleImageUploaded = (uploadResult) => {
    if (uploadResult && uploadResult.id) {
        form.cover_image_id = uploadResult.id
    }
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    showError('Failed to upload image. Please try again.')
}

const removeCoverImage = () => {
    form.cover_image_id = null
    form.remove_cover_image = true
    if (page.value) {
        page.value.image_url = null
        page.value.cover_image_id = null
    }
}

const saveSettings = async () => {
    processing.value = true

    const payload = {
        heading: form.heading,
        paragraph: form.paragraph,
        cover_image_id: form.cover_image_id,
        remove_cover_image: form.remove_cover_image
    }

    try {
        const response = await axios.put(`/api/admin/marketing-pages/key/${pageKey.value}`, payload)
        
        page.value = response.data.data
        form.remove_cover_image = false
        
        showSuccess('Settings saved successfully!')
    } catch (error) {
        console.error('Error saving settings:', error)
        showError('Failed to save settings')
    } finally {
        processing.value = false
    }
}

// Initialize
onMounted(() => {
    if (!['places', 'lists', 'register'].includes(pageKey.value)) {
        showError('Invalid page')
        return
    }
    fetchPage()
})
</script>