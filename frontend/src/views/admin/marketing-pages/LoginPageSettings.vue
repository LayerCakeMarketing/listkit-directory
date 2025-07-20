<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Login Page Settings</h2>
                <p class="mt-1 text-sm text-gray-600">Customize the appearance and behavior of the login page</p>
                <div class="mt-4 flex space-x-3">
                    <a 
                        href="/login" 
                        target="_blank"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        View Login Page
                    </a>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form @submit.prevent="saveSettings" class="p-6 space-y-6">
                    <!-- Background Image -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>
                        <div class="space-y-4">
                            <div v-if="form.background_image_id || settings.background_image_id || settings.background_image_url" class="relative inline-block">
                                <img 
                                    :src="backgroundImageUrl" 
                                    alt="Background" 
                                    class="w-full max-w-md h-48 object-cover rounded-lg shadow-md"
                                    @error="(e) => console.error('Image load error:', e, 'URL:', backgroundImageUrl)"
                                />
                                <button
                                    type="button"
                                    @click="removeBackgroundImage"
                                    class="absolute top-2 right-2 bg-red-600 text-white p-1.5 rounded-full hover:bg-red-700 shadow-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Cloudflare Upload Component -->
                            <div v-if="!form.background_image_id && !settings.background_image_id && !settings.background_image_url">
                                <CloudflareDragDropUploader
                                    :max-files="1"
                                    :max-file-size="10485760"
                                    context="cover"
                                    entity-type="login_page"
                                    :entity-id="1"
                                    accepted-types="image/jpeg,image/png,image/webp"
                                    @upload-success="handleImagesUploaded"
                                    @upload-error="handleUploadError"
                                />
                                <p class="text-xs text-gray-500 mt-2">Recommended: 1920x859px (16:9 ratio)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Welcome Message</label>
                        <textarea
                            v-model="form.welcome_message"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Welcome back! Please sign in to continue."
                        ></textarea>
                    </div>

                    <!-- Social Login -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Social Login Options</label>
                        <div class="flex items-center">
                            <input
                                v-model="form.social_login_enabled"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <label class="ml-2 text-sm text-gray-700">Enable social login buttons</label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Show login options for Google, Facebook, etc.</p>
                    </div>

                    <!-- Custom CSS -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custom CSS</label>
                        <textarea
                            v-model="form.custom_css"
                            rows="10"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                            placeholder=".login-container {&#10;  background-color: #f5f5f5;&#10;}"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Add custom CSS to override default styles</p>
                    </div>

                    <!-- Live Preview -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Live Preview</h3>
                        <div class="border rounded-lg overflow-hidden shadow-lg">
                            <div ref="previewRef" class="login-preview relative bg-gray-100" style="min-height: 400px;">
                                <!-- Background -->
                                <div 
                                    v-if="backgroundImageUrl" 
                                    class="absolute inset-0"
                                    :style="{ backgroundImage: `url(${backgroundImageUrl})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
                                ></div>
                                
                                <!-- Login Form Preview -->
                                <div class="relative flex items-center justify-center p-8" style="min-height: 400px;">
                                    <div class="bg-white/95 backdrop-blur p-8 rounded-lg shadow-xl max-w-sm w-full">
                                        <div class="text-center mb-6">
                                            <img src="/images/listerino_logo.svg" alt="Logo" class="h-10 w-auto mx-auto mb-4" />
                                            <div v-if="form.welcome_message" class="text-gray-700" v-html="form.welcome_message"></div>
                                            <div v-else class="text-gray-700">Sign in to your account</div>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <input type="email" placeholder="Email address" class="w-full px-3 py-2 border border-gray-300 rounded-md" disabled />
                                            </div>
                                            <div>
                                                <input type="password" placeholder="Password" class="w-full px-3 py-2 border border-gray-300 rounded-md" disabled />
                                            </div>
                                            <button class="w-full bg-indigo-600 text-white py-2 rounded-md" disabled>Sign in</button>
                                        </div>
                                        
                                        <!-- Social Login Preview -->
                                        <div v-if="form.social_login_enabled" class="mt-6 pt-6 border-t">
                                            <p class="text-center text-sm text-gray-600 mb-3">Or continue with</p>
                                            <div class="flex space-x-3">
                                                <button class="flex-1 py-2 px-3 border border-gray-300 rounded-md text-sm" disabled>Google</button>
                                                <button class="flex-1 py-2 px-3 border border-gray-300 rounded-md text-sm" disabled>Facebook</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </button>
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
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import { useNotification } from '@/composables/useNotification'

// State
const loading = ref(false)
const processing = ref(false)
const settings = ref({})
const backgroundImageFile = ref(null)

const { showSuccess, showError } = useNotification()

// Form
const form = reactive({
    welcome_message: '',
    custom_css: '',
    social_login_enabled: true,
    background_image_id: null,
    remove_background_image: false
})

// Computed
const backgroundImageUrl = computed(() => {
    console.log('Computing backgroundImageUrl - form.background_image_id:', form.background_image_id, 'settings:', settings.value)
    if (form.background_image_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${form.background_image_id}/lgformat`
    }
    if (settings.value?.background_image_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${settings.value.background_image_id}/lgformat`
    }
    return settings.value?.background_image_url || null
})

// Create a style element for the preview
const previewRef = ref(null)
const updatePreviewStyles = () => {
    if (!previewRef.value || !form.custom_css) return
    
    // Find or create a style element for the preview
    let styleEl = previewRef.value.querySelector('style[data-preview-css]')
    if (!styleEl) {
        styleEl = document.createElement('style')
        styleEl.setAttribute('data-preview-css', 'true')
        previewRef.value.appendChild(styleEl)
    }
    
    // Scope the CSS to the preview container
    const scopedCss = form.custom_css.replace(/([^{]+){/g, '.login-preview $1{')
    styleEl.textContent = scopedCss
}

// Methods
const fetchSettings = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/marketing-pages/special/login')
        settings.value = response.data.data
        
        // Update form with settings
        form.welcome_message = settings.value.welcome_message || ''
        form.custom_css = settings.value.custom_css || ''
        form.social_login_enabled = settings.value.social_login_enabled !== false
        form.background_image_id = settings.value.background_image_id || null
        form.remove_background_image = false
    } catch (error) {
        console.error('Error fetching settings:', error)
    } finally {
        loading.value = false
    }
}

const handleImagesUploaded = (uploadResult) => {
    console.log('Cloudflare image uploaded:', uploadResult)
    if (uploadResult && uploadResult.id) {
        form.background_image_id = uploadResult.id
        console.log('Set form.background_image_id to:', form.background_image_id)
        console.log('Current form state:', JSON.parse(JSON.stringify(form)))
    }
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    showError('Upload Error', 'Failed to upload image. Please try again.')
}

const removeBackgroundImage = () => {
    form.background_image_id = null
    form.remove_background_image = true
    if (settings.value) {
        settings.value.background_image_url = null
        settings.value.background_image_id = null
    }
}

const saveSettings = async () => {
    processing.value = true

    const payload = {
        welcome_message: form.welcome_message,
        custom_css: form.custom_css,
        social_login_enabled: form.social_login_enabled,
        background_image_id: form.background_image_id,
        remove_background_image: form.remove_background_image
    }
    
    console.log('Saving settings with payload:', payload)

    try {
        const response = await axios.post('/api/admin/marketing-pages/special/login', payload)
        
        settings.value = response.data.data
        form.remove_background_image = false
        backgroundImageFile.value = null
        
        showSuccess('Saved', 'Settings saved successfully!')
    } catch (error) {
        console.error('Error saving settings:', error)
        showError('Error', 'Failed to save settings')
    } finally {
        processing.value = false
    }
}

const resetForm = () => {
    fetchSettings()
    backgroundImageFile.value = null
    form.remove_background_image = false
}

// Watch for custom CSS changes
watch(() => form.custom_css, () => {
    nextTick(() => {
        updatePreviewStyles()
    })
})

// Initialize
onMounted(() => {
    fetchSettings()
})
</script>