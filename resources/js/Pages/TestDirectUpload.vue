<template>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-8">Test Direct Cloudflare Upload</h1>
                
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <h2 class="text-lg font-semibold text-blue-900 mb-2">Progressive Upload Features</h2>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>✅ Direct upload to Cloudflare (bypasses Laravel server)</li>
                        <li>✅ Real-time progress tracking with upload speed</li>
                        <li>✅ Client-side validation (14MB limit)</li>
                        <li>✅ Support for HEIC, JPEG, PNG, WebP, GIF</li>
                        <li>✅ Drag & drop interface</li>
                        <li>✅ Error handling and UX feedback</li>
                    </ul>
                </div>

                <!-- Authentication Notice -->
                <div v-if="!$page.props.auth.user" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm text-yellow-800">
                                <strong>Authentication Required:</strong> 
                                <a href="/login" class="underline">Please log in</a> to test the upload functionality.
                                The upload component needs API access to generate signed URLs.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Status -->
                <div v-if="uploadStatus" class="mb-6 p-4 rounded-md" :class="statusClasses">
                    {{ uploadStatus }}
                </div>
                
                <!-- Test Avatar Upload -->
                <div class="space-y-8">
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Test Progressive Image Upload</h2>
                        <DirectCloudflareUpload
                            v-model="avatarImage"
                            label="Test Large Image Upload (up to 14MB)"
                            upload-type="avatar"
                            :entity-id="1"
                            :max-size-m-b="14"
                            @upload-complete="handleUploadComplete"
                            @upload-error="handleUploadError"
                            @upload-start="handleUploadStart"
                            show-details
                        />
                    </div>
                    
                    <!-- Performance Stats -->
                    <div v-if="performanceStats" class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Upload Performance</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="text-sm text-gray-600">File Size</div>
                                <div class="font-semibold">{{ formatFileSize(performanceStats.fileSize) }}</div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="text-sm text-gray-600">Upload Time</div>
                                <div class="font-semibold">{{ performanceStats.uploadTime }}s</div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="text-sm text-gray-600">Average Speed</div>
                                <div class="font-semibold">{{ formatFileSize(performanceStats.avgSpeed) }}/s</div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-md">
                                <div class="text-sm text-gray-600">Method</div>
                                <div class="font-semibold text-green-600">Direct to CF</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Results -->
                    <div v-if="uploadedImage" class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Upload Results</h3>
                        <div class="space-y-4">
                            <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                                <img 
                                    :src="uploadedImage.urls.original" 
                                    :alt="uploadedImage.filename"
                                    class="w-full h-full object-contain"
                                />
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium mb-2">Image Details:</h4>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <div><strong>Cloudflare ID:</strong> {{ uploadedImage.cloudflare_id }}</div>
                                        <div><strong>Filename:</strong> {{ uploadedImage.filename }}</div>
                                        <div><strong>CDN URL:</strong> 
                                            <a :href="uploadedImage.urls.original" target="_blank" class="text-blue-600 hover:underline break-all">
                                                {{ uploadedImage.urls.original }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium mb-2">Technical Benefits:</h4>
                                    <ul class="text-sm text-gray-600 space-y-1">
                                        <li>• Zero server load</li>
                                        <li>• No PHP upload limits</li>
                                        <li>• Automatic optimization</li>
                                        <li>• Global CDN delivery</li>
                                        <li>• Real-time progress</li>
                                    </ul>
                                </div>
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
                                {{ testing ? 'Testing...' : 'Test Upload URL Generation' }}
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
                                    :src="image.urls.original" 
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
import DirectCloudflareUpload from '@/Components/ImageUpload/DirectCloudflareUpload.vue'

// State
const avatarImage = ref(null)
const uploadedImage = ref(null)
const uploadStatus = ref('')
const statusType = ref('')
const myImages = ref([])
const testing = ref(false)
const loading = ref(false)
const performanceStats = ref(null)
const uploadStartTime = ref(null)
const currentFileSize = ref(0)

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

// Helper functions
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Event handlers
const handleUploadComplete = (image) => {
    console.log('Upload completed:', image)
    uploadedImage.value = image
    uploadStatus.value = `✅ Direct upload successful! Image ID: ${image.cloudflare_id}`
    statusType.value = 'success'
    
    // Calculate performance stats if we have start time
    if (uploadStartTime.value) {
        const uploadTime = (Date.now() - uploadStartTime.value) / 1000
        const fileSize = currentFileSize.value || image.file_size || 0
        const avgSpeed = fileSize > 0 ? fileSize / uploadTime : 0
        
        performanceStats.value = {
            fileSize,
            uploadTime: uploadTime.toFixed(1),
            avgSpeed
        }
        
        console.log('Performance stats calculated:', {
            fileSize,
            uploadTime: uploadTime.toFixed(1),
            avgSpeed,
            originalImageFileSize: image.file_size,
            currentFileSize: currentFileSize.value
        })
        
        // Reset values
        uploadStartTime.value = null
        currentFileSize.value = 0
    }
}

const handleUploadStart = (fileInfo) => {
    uploadStartTime.value = Date.now()
    currentFileSize.value = fileInfo.size || 0
    performanceStats.value = null
    uploadStatus.value = 'Upload started...'
    statusType.value = 'info'
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    uploadStatus.value = `❌ Direct upload failed: ${error.message}`
    statusType.value = 'error'
    uploadStartTime.value = null
    performanceStats.value = null
}

// API Tests
const testConnection = async () => {
    testing.value = true
    uploadStatus.value = 'Testing upload URL generation...'
    statusType.value = 'info'
    
    try {
        const response = await fetch('/api/images/generate-upload-url', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                type: 'avatar',
                entity_id: 1,
                filename: 'test.jpg'
            })
        })
        
        if (response.ok) {
            const data = await response.json()
            if (data.success) {
                uploadStatus.value = '✅ Upload URL generation successful! Ready for direct uploads.'
                statusType.value = 'success'
            } else {
                throw new Error(data.message)
            }
        } else {
            throw new Error(`HTTP ${response.status}`)
        }
    } catch (error) {
        console.error('Connection test failed:', error)
        uploadStatus.value = `❌ URL generation failed: ${error.message}`
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
</script>