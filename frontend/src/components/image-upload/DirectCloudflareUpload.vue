<template>
    <div class="space-y-4">
        <label v-if="label" class="block text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        
        <!-- Upload Area -->
        <div 
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
            :class="{ 
                'border-blue-500 bg-blue-50': isDragging,
                'border-red-500 bg-red-50': hasError && !isUploading,
                'border-green-500 bg-green-50': isCompleted
            }"
            @dragover.prevent="handleDragOver"
            @dragleave.prevent="handleDragLeave"
            @drop.prevent="handleDrop"
        >
            <!-- Preview -->
            <div v-if="previewUrl || currentImageUrl" class="mb-4">
                <img 
                    :src="previewUrl || currentImageUrl" 
                    :alt="label || 'Image preview'"
                    class="mx-auto max-w-full max-h-48 rounded-lg object-contain"
                />
                <button
                    v-if="!isUploading && !isCompleted"
                    @click="removeImage"
                    type="button"
                    class="mt-2 text-red-600 hover:text-red-800 text-sm"
                >
                    Remove Image
                </button>
            </div>

            <!-- Upload UI -->
            <div v-if="!previewUrl && !currentImageUrl && !isCompleted">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                    <button
                        @click="triggerFileInput"
                        type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                        :disabled="isUploading"
                    >
                        {{ isUploading ? 'Uploading...' : 'Choose Image' }}
                    </button>
                    <p class="mt-2 text-sm text-gray-500">or drag and drop</p>
                </div>
            </div>

            <!-- Change Image Button -->
            <div v-else-if="!isUploading && (previewUrl || currentImageUrl)" class="mt-4">
                <button
                    @click="triggerFileInput"
                    type="button"
                    class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors"
                >
                    Change Image
                </button>
            </div>

            <!-- Upload Complete -->
            <div v-if="isCompleted" class="mt-4">
                <div class="flex items-center justify-center text-green-600">
                    <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="font-medium">Upload Complete!</span>
                </div>
            </div>
        </div>

        <!-- File Input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*,.heic,.avif"
            @change="handleFileSelect"
            class="hidden"
        />

        <!-- Progress Bar -->
        <div v-if="isUploading" class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ uploadStatus }}</span>
                <span class="text-gray-600">{{ Math.round(uploadProgress) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: uploadProgress + '%' }"
                ></div>
            </div>
            <div v-if="uploadSpeed" class="text-xs text-gray-500 text-center">
                {{ formatFileSize(uploadSpeed) }}/s
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
                <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <p class="text-sm text-red-600">{{ errorMessage }}</p>
            </div>
        </div>

        <!-- Helper Text -->
        <p class="text-xs text-gray-500">
            Supports JPEG, PNG, GIF, WebP, HEIC, AVIF. Max {{ maxSizeMB }}MB. 
            <br>
            
        </p>

        <!-- Upload Info (Development) -->
        <div v-if="uploadedImage && showDetails" class="mt-4 p-3 bg-gray-50 rounded-md">
            <h4 class="text-sm font-medium mb-2">Upload Details:</h4>
            <div class="space-y-1 text-xs text-gray-600">
                <div><strong>Cloudflare ID:</strong> {{ uploadedImage.cloudflare_id }}</div>
                <div><strong>Filename:</strong> {{ uploadedImage.filename }}</div>
                <div><strong>URL:</strong> <a :href="finalImageUrl" target="_blank" class="text-blue-600 hover:underline">View Image</a></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: [String, Object],
    label: String,
    uploadType: {
        type: String,
        required: true,
        validator: value => ['avatar', 'cover', 'page_logo', 'list_image', 'entry_logo'].includes(value)
    },
    entityId: [Number, String],
    currentImageUrl: String,
    maxSizeMB: {
        type: Number,
        default: 14
    },
    showDetails: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'upload-complete', 'upload-error', 'upload-start', 'remove'])

// Reactive state
const fileInput = ref(null)
const previewUrl = ref(null)
const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadStatus = ref('')
const uploadSpeed = ref(0)
const errorMessage = ref('')
const hasError = ref(false)
const isDragging = ref(false)
const isCompleted = ref(false)
const uploadedImage = ref(null)
const finalImageUrl = ref('')

// Computed
const maxSizeBytes = computed(() => props.maxSizeMB * 1024 * 1024)

// Methods
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        processFile(file)
    }
}

const handleDragOver = (event) => {
    event.preventDefault()
    isDragging.value = true
}

const handleDragLeave = (event) => {
    event.preventDefault()
    isDragging.value = false
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file && file.type.startsWith('image/')) {
        processFile(file)
    }
}

const validateFile = (file) => {
    // Reset error state
    errorMessage.value = ''
    hasError.value = false

    // Check file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif', 'image/avif']
    const isValidType = allowedTypes.includes(file.type.toLowerCase()) || 
                       file.name.toLowerCase().endsWith('.heic') || 
                       file.name.toLowerCase().endsWith('.heif') ||
                       file.name.toLowerCase().endsWith('.avif')
    
    if (!isValidType) {
        errorMessage.value = 'Please select a valid image file (JPEG, PNG, GIF, WebP, HEIC, or AVIF)'
        hasError.value = true
        return false
    }

    // Check file size
    if (file.size > maxSizeBytes.value) {
        errorMessage.value = `File size must be less than ${props.maxSizeMB}MB (current: ${formatFileSize(file.size)})`
        hasError.value = true
        return false
    }

    return true
}

const createPreview = (file) => {
    // Don't create preview for HEIC/AVIF files as browsers may not display them
    if (file.type === 'image/heic' || file.type === 'image/heif' || file.type === 'image/avif' ||
        file.name.toLowerCase().endsWith('.heic') || file.name.toLowerCase().endsWith('.heif') ||
        file.name.toLowerCase().endsWith('.avif')) {
        previewUrl.value = null
        return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
        previewUrl.value = e.target.result
    }
    reader.readAsDataURL(file)
}

const processFile = async (file) => {
    try {
        // Reset state
        isCompleted.value = false
        uploadedImage.value = null
        finalImageUrl.value = ''

        // Validate file
        if (!validateFile(file)) {
            return
        }

        // Create preview
        createPreview(file)

        // Upload directly to Cloudflare
        await uploadToCloudflare(file)

    } catch (error) {
        handleUploadError(error)
    }
}

const uploadToCloudflare = async (file) => {
    isUploading.value = true
    uploadProgress.value = 0
    uploadStatus.value = 'Getting upload URL...'

    // Emit upload start event
    emit('upload-start', { filename: file.name, size: file.size })

    let startTime = Date.now()
    let lastLoaded = 0

    try {
        // Step 1: Get signed upload URL from our Laravel backend
        const urlResponse = await fetch('/api/images/generate-upload-url', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                type: props.uploadType,
                entity_id: props.entityId,
                filename: file.name
            })
        })

        if (!urlResponse.ok) {
            throw new Error(`Failed to get upload URL: ${urlResponse.status}`)
        }

        const urlData = await urlResponse.json()
        if (!urlData.success) {
            throw new Error(urlData.message || 'Failed to generate upload URL')
        }

        const { uploadURL, imageId } = urlData.data
        uploadStatus.value = 'Uploading to Cloudflare...'

        // Step 2: Upload directly to Cloudflare using XMLHttpRequest for progress tracking
        const actualImageId = await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest()
            const formData = new FormData()
            formData.append('file', file)

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    uploadProgress.value = (e.loaded / e.total) * 100
                    
                    // Calculate upload speed
                    const elapsed = Date.now() - startTime
                    if (elapsed > 1000) { // Update speed every second
                        const bytesUploaded = e.loaded - lastLoaded
                        uploadSpeed.value = (bytesUploaded / (elapsed / 1000))
                        lastLoaded = e.loaded
                        startTime = Date.now()
                    }
                }
            })

            xhr.addEventListener('load', () => {
                console.log('Cloudflare upload response status:', xhr.status)
                console.log('Cloudflare upload response:', xhr.responseText)
                
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const cloudflareResponse = JSON.parse(xhr.responseText)
                        console.log('Parsed Cloudflare response:', cloudflareResponse)
                        
                        if (cloudflareResponse.success && cloudflareResponse.result) {
                            uploadStatus.value = 'Upload complete!'
                            resolve(cloudflareResponse.result.id)
                        } else {
                            reject(new Error('Cloudflare upload failed: ' + (cloudflareResponse.errors ? JSON.stringify(cloudflareResponse.errors) : 'Unknown error')))
                        }
                    } catch (parseError) {
                        console.error('Failed to parse Cloudflare response:', parseError)
                        reject(new Error('Invalid response from Cloudflare'))
                    }
                } else {
                    reject(new Error(`Upload failed with status ${xhr.status}: ${xhr.responseText}`))
                }
            })

            xhr.addEventListener('error', () => {
                reject(new Error('Network error during upload'))
            })

            xhr.open('POST', uploadURL)
            xhr.send(formData)
        })

        // Step 3: Confirm upload with our Laravel backend
        uploadStatus.value = 'Finalizing...'
        const confirmResponse = await fetch('/api/images/confirm-upload', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                cloudflare_id: actualImageId,
                type: props.uploadType,
                entity_id: props.entityId,
                filename: file.name,
                file_size: file.size
            })
        })

        console.log('Confirm response status:', confirmResponse.status)
        console.log('Confirm response headers:', Object.fromEntries(confirmResponse.headers.entries()))

        if (!confirmResponse.ok) {
            throw new Error(`Failed to confirm upload: ${confirmResponse.status}`)
        }

        const confirmData = await confirmResponse.json()
        if (!confirmData.success) {
            throw new Error(confirmData.message || 'Failed to confirm upload')
        }

        // Success!
        uploadedImage.value = confirmData.image
        finalImageUrl.value = confirmData.image.urls.original
        isCompleted.value = true
        uploadStatus.value = 'Complete!'

        emit('update:modelValue', confirmData.image)
        emit('upload-complete', confirmData.image)

        // Auto-hide success state after 3 seconds
        setTimeout(() => {
            uploadStatus.value = ''
        }, 3000)

    } catch (error) {
        throw error
    } finally {
        isUploading.value = false
        uploadSpeed.value = 0
    }
}

const removeImage = () => {
    previewUrl.value = null
    uploadedImage.value = null
    finalImageUrl.value = ''
    isCompleted.value = false
    errorMessage.value = ''
    hasError.value = false
    
    if (fileInput.value) {
        fileInput.value.value = ''
    }
    
    emit('update:modelValue', null)
    emit('remove')
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    errorMessage.value = error.message || 'Upload failed. Please try again.'
    hasError.value = true
    uploadStatus.value = ''
    emit('upload-error', error)
}

// Watch for external image changes
watch(() => props.currentImageUrl, (newUrl) => {
    if (newUrl && !previewUrl.value && !uploadedImage.value) {
        // External image set, clear any local preview
        previewUrl.value = null
        isCompleted.value = false
    }
})
</script>