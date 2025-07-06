<template>
    <div class="space-y-4">
        <label v-if="label" class="block text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        
        <!-- Upload Area -->
        <div 
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
            :class="{ 'border-blue-500 bg-blue-50': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
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
                    v-if="previewUrl || (currentImageUrl && !isRequired)"
                    @click="removeImage"
                    type="button"
                    class="mt-2 text-red-600 hover:text-red-800 text-sm"
                >
                    Remove Image
                </button>
            </div>

            <!-- Upload UI -->
            <div v-if="!previewUrl && !currentImageUrl">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                    <button
                        @click="triggerFileInput"
                        type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        :disabled="isUploading"
                    >
                        {{ isUploading ? 'Uploading...' : 'Choose Image' }}
                    </button>
                    <p class="mt-2 text-sm text-gray-500">or drag and drop</p>
                </div>
            </div>

            <!-- Change Image Button -->
            <div v-else class="mt-4">
                <button
                    @click="triggerFileInput"
                    type="button"
                    class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors"
                    :disabled="isUploading"
                >
                    {{ isUploading ? 'Uploading...' : 'Change Image' }}
                </button>
            </div>
        </div>

        <!-- File Input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            @change="handleFileSelect"
            class="hidden"
        />

        <!-- Progress Bar -->
        <div v-if="isUploading" class="w-full bg-gray-200 rounded-full h-2">
            <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: uploadProgress + '%' }"
            ></div>
        </div>

        <!-- Async Upload Status -->
        <div v-if="asyncUploadId" class="p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center">
                <svg v-if="asyncStatus === 'processing'" class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else-if="asyncStatus === 'completed'" class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg v-else-if="asyncStatus === 'failed'" class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                
                <span class="text-sm">
                    {{ asyncStatusMessage }}
                </span>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>

        <!-- Helper Text -->
        <p class="text-xs text-gray-500">
            Supports JPEG, PNG, GIF, WebP. Max {{ maxSize }}MB. 
            <span v-if="asyncThreshold">Files over {{ asyncThreshold }}MB will be processed in the background.</span>
            <br>
            <span class="text-blue-600">Optimized for high-quality images with automatic compression and CDN delivery.</span>
        </p>

        <!-- Image Variants (for development/preview) -->
        <div v-if="imageVariants && showVariants" class="mt-4 p-3 bg-gray-50 rounded-md">
            <h4 class="text-sm font-medium mb-2">Available Sizes:</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                <div v-for="(url, size) in imageVariants" :key="size" class="space-y-1">
                    <div class="font-medium">{{ size }}</div>
                    <img :src="url" :alt="size" class="w-full h-16 object-cover rounded border" />
                </div>
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
        validator: value => ['avatar', 'cover', 'list_image', 'entry_logo'].includes(value)
    },
    entityId: [Number, String],
    currentImageUrl: String,
    maxSize: {
        type: Number,
        default: 14 // MB
    },
    asyncThreshold: {
        type: Number,
        default: 3 // MB - files larger than this go to async processing
    },
    isRequired: {
        type: Boolean,
        default: false
    },
    showVariants: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'upload-complete', 'upload-error', 'remove'])

// Reactive state
const fileInput = ref(null)
const previewUrl = ref(null)
const isUploading = ref(false)
const uploadProgress = ref(0)
const errorMessage = ref('')
const isDragging = ref(false)
const asyncUploadId = ref(null)
const asyncStatus = ref(null)
const imageVariants = ref(null)

// Computed
const asyncStatusMessage = computed(() => {
    switch (asyncStatus.value) {
        case 'processing':
            return 'Processing image in background...'
        case 'completed':
            return 'Image processed successfully!'
        case 'failed':
            return 'Image processing failed. Please try again.'
        default:
            return ''
    }
})

// Methods
const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        processFile(file)
    }
}

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file && file.type.startsWith('image/')) {
        processFile(file)
    }
}

const processFile = async (file) => {
    try {
        // Reset state
        errorMessage.value = ''
        asyncUploadId.value = null
        asyncStatus.value = null
        
        // Validate file
        if (!validateFile(file)) {
            return
        }

        // Create preview
        createPreview(file)

        // Decide upload method based on file size
        const fileSizeMB = file.size / (1024 * 1024)
        
        if (fileSizeMB > props.asyncThreshold) {
            await uploadAsync(file)
        } else {
            await uploadImmediate(file)
        }

    } catch (error) {
        handleUploadError(error)
    }
}

const validateFile = (file) => {
    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
        errorMessage.value = 'Please select a valid image file (JPEG, PNG, GIF, or WebP)'
        return false
    }

    // Check file size
    const maxSizeBytes = props.maxSize * 1024 * 1024
    if (file.size > maxSizeBytes) {
        errorMessage.value = `File size must be less than ${props.maxSize}MB`
        return false
    }

    return true
}

const createPreview = (file) => {
    const reader = new FileReader()
    reader.onload = (e) => {
        previewUrl.value = e.target.result
    }
    reader.readAsDataURL(file)
}

const uploadImmediate = async (file) => {
    isUploading.value = true
    uploadProgress.value = 0

    const formData = new FormData()
    formData.append('image', file)
    formData.append('type', props.uploadType)
    if (props.entityId) {
        formData.append('entity_id', props.entityId)
    }

    try {
        const response = await fetch('/api/images/upload', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })

        uploadProgress.value = 100

        if (!response.ok) {
            if (response.status === 413) {
                throw new Error('File too large. Please ensure your server supports files up to 14MB.')
            } else if (response.status === 422) {
                const errorData = await response.json()
                throw new Error(errorData.message || 'File validation failed')
            } else {
                throw new Error(`Upload failed with status ${response.status}`)
            }
        }

        const result = await response.json()

        if (result.success) {
            imageVariants.value = result.image.urls
            emit('update:modelValue', result.image)
            emit('upload-complete', result.image)
        } else {
            throw new Error(result.message || 'Upload failed')
        }

    } catch (error) {
        throw error
    } finally {
        isUploading.value = false
        uploadProgress.value = 0
    }
}

const uploadAsync = async (file) => {
    isUploading.value = true

    const formData = new FormData()
    formData.append('image', file)
    formData.append('type', props.uploadType)
    if (props.entityId) {
        formData.append('entity_id', props.entityId)
    }

    try {
        const response = await fetch('/api/images/upload-async', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })

        const result = await response.json()

        if (result.success) {
            asyncUploadId.value = result.upload_id
            asyncStatus.value = 'processing'
            pollUploadStatus()
        } else {
            throw new Error(result.message || 'Upload failed')
        }

    } catch (error) {
        throw error
    } finally {
        isUploading.value = false
    }
}

const pollUploadStatus = async () => {
    if (!asyncUploadId.value) return

    try {
        const response = await fetch(`/api/images/status/${asyncUploadId.value}`, {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })

        const result = await response.json()

        if (result.success) {
            asyncStatus.value = result.status

            if (result.status === 'completed' && result.image) {
                imageVariants.value = result.image.urls
                emit('update:modelValue', result.image)
                emit('upload-complete', result.image)
                asyncUploadId.value = null
            } else if (result.status === 'failed') {
                throw new Error(result.message || 'Processing failed')
            } else {
                // Still processing, poll again in 2 seconds
                setTimeout(pollUploadStatus, 2000)
            }
        }

    } catch (error) {
        asyncStatus.value = 'failed'
        handleUploadError(error)
    }
}

const removeImage = () => {
    previewUrl.value = null
    imageVariants.value = null
    asyncUploadId.value = null
    asyncStatus.value = null
    errorMessage.value = ''
    
    if (fileInput.value) {
        fileInput.value.value = ''
    }
    
    emit('update:modelValue', null)
    emit('remove')
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    errorMessage.value = error.message || 'Upload failed. Please try again.'
    emit('upload-error', error)
}

// Using session-based authentication, no token needed

// Watch for external image changes
watch(() => props.currentImageUrl, (newUrl) => {
    if (newUrl && !previewUrl.value) {
        // External image set, clear any local preview
        previewUrl.value = null
    }
})
</script>