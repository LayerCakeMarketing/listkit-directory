<template>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-8">Test Cloudflare Images Upload</h1>
                
                <!-- Upload Status -->
                <div v-if="uploadStatus" class="mb-6 p-4 rounded-md" :class="statusClasses">
                    {{ uploadStatus }}
                </div>
                
                <!-- Test Avatar Upload -->
                <div class="space-y-8">
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Test Avatar Upload</h2>
                        <CloudflareImageUpload
                            v-model="avatarImage"
                            label="Test Avatar"
                            upload-type="avatar"
                            :entity-id="1"
                            :max-size="14"
                            :async-threshold="3"
                            @upload-complete="handleUploadComplete"
                            @upload-error="handleUploadError"
                            show-variants
                        />
                    </div>
                    
                    <!-- Test Results -->
                    <div v-if="uploadedImage" class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Upload Results</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="(url, variant) in uploadedImage.urls" :key="variant" class="text-center">
                                    <div class="font-medium text-sm text-gray-700 mb-2">{{ variant }}</div>
                                    <img :src="url" :alt="variant" class="w-full h-24 object-cover rounded border" />
                                    <a :href="url" target="_blank" class="text-xs text-blue-600 hover:underline">View Full</a>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h4 class="font-medium mb-2">Image Details:</h4>
                                <pre class="text-xs text-gray-600">{{ JSON.stringify(uploadedImage, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Test Buttons -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">API Tests</h3>
                        <div class="flex space-x-4">
                            <button
                                @click="testConnection"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                                :disabled="testing"
                            >
                                {{ testing ? 'Testing...' : 'Test Connection' }}
                            </button>
                            
                            <button
                                @click="loadMyImages"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                                :disabled="loading"
                            >
                                {{ loading ? 'Loading...' : 'Load My Images' }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- My Images -->
                    <div v-if="myImages.length" class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">My Uploaded Images</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="image in myImages" :key="image.id" class="text-center">
                                <img 
                                    :src="image.urls.thumbnail" 
                                    :alt="image.original_name"
                                    class="w-full h-24 object-cover rounded border"
                                />
                                <div class="text-xs text-gray-600 mt-1">{{ image.type }}</div>
                                <div class="text-xs text-gray-500">{{ image.original_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import CloudflareImageUpload from '@/Components/ImageUpload/CloudflareImageUpload.vue'

// State
const avatarImage = ref(null)
const uploadedImage = ref(null)
const uploadStatus = ref('')
const statusType = ref('')
const myImages = ref([])
const testing = ref(false)
const loading = ref(false)

// Computed
const statusClasses = computed(() => {
    switch (statusType.value) {
        case 'success':
            return 'bg-green-50 text-green-700 border border-green-200'
        case 'error':
            return 'bg-red-50 text-red-700 border border-red-200'
        default:
            return 'bg-blue-50 text-blue-700 border border-blue-200'
    }
})

// Event handlers
const handleUploadComplete = (image) => {
    console.log('Upload completed:', image)
    uploadedImage.value = image
    uploadStatus.value = `✅ Upload successful! Image ID: ${image.cloudflare_id}`
    statusType.value = 'success'
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    uploadStatus.value = `❌ Upload failed: ${error.message}`
    statusType.value = 'error'
}

// API Tests
const testConnection = async () => {
    testing.value = true
    uploadStatus.value = 'Testing Cloudflare Images connection...'
    statusType.value = 'info'
    
    try {
        const response = await fetch('/api/images', {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (response.ok) {
            uploadStatus.value = '✅ Connection successful! API is working.'
            statusType.value = 'success'
        } else {
            throw new Error(`HTTP ${response.status}`)
        }
    } catch (error) {
        console.error('Connection test failed:', error)
        uploadStatus.value = `❌ Connection failed: ${error.message}`
        statusType.value = 'error'
    } finally {
        testing.value = false
    }
}

const loadMyImages = async () => {
    loading.value = true
    
    try {
        const response = await fetch('/api/images', {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            myImages.value = result.images.data || []
            uploadStatus.value = `✅ Loaded ${myImages.value.length} images`
            statusType.value = 'success'
        } else {
            throw new Error(result.message || 'Failed to load images')
        }
    } catch (error) {
        console.error('Failed to load images:', error)
        uploadStatus.value = `❌ Failed to load images: ${error.message}`
        statusType.value = 'error'
    } finally {
        loading.value = false
    }
}

// Using session-based authentication, no token needed
</script>