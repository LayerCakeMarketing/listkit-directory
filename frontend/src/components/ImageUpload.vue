<template>
    <div class="relative">
        <!-- Image Preview -->
        <div v-if="currentImage" class="relative mb-4">
            <img
                :src="currentImage"
                :alt="`${type} preview`"
                class="max-w-full h-auto rounded-lg shadow-md"
                :class="getImageClasses()"
            />
            <button
                type="button"
                @click="removeImage"
                class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Upload Area -->
        <div
            v-if="!currentImage"
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="handleDrop"
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors"
            :class="{ 'border-blue-500 bg-blue-50': isDragging }"
        >
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span>
                    or drag and drop
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ getConstraintsText() }}</p>
            </div>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            @change="handleFileSelect"
            class="hidden"
        />

        <!-- Upload Progress -->
        <div v-if="uploading" class="mt-4">
            <div class="bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
            </div>
            <p class="text-sm text-gray-600 mt-1">Uploading... {{ uploadProgress }}%</p>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-2 text-sm text-red-600">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['logo', 'cover', 'gallery'].includes(value)
    },
    currentImage: String,
})

const emit = defineEmits(['uploaded', 'removed'])

const fileInput = ref(null)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const isDragging = ref(false)

const constraints = {
    logo: {
        maxWidth: 400,
        maxHeight: 400,
        maxSize: 2048, // 2MB in KB
        aspectRatio: '1:1'
    },
    cover: {
        maxWidth: 1920,
        maxHeight: 1080,
        maxSize: 5120, // 5MB in KB
        aspectRatio: '16:9'
    },
    gallery: {
        maxWidth: 1200,
        maxHeight: 800,
        maxSize: 3072, // 3MB in KB
        aspectRatio: 'any'
    }
}

const getImageClasses = () => {
    switch (props.type) {
        case 'logo':
            return 'max-w-xs max-h-xs'
        case 'cover':
            return 'max-w-2xl max-h-96'
        case 'gallery':
            return 'max-w-lg max-h-64'
        default:
            return ''
    }
}

const getConstraintsText = () => {
    const constraint = constraints[props.type]
    return `${constraint.maxWidth}x${constraint.maxHeight}px max, ${Math.round(constraint.maxSize / 1024)}MB`
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        uploadFile(file)
    }
}

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file) {
        uploadFile(file)
    }
}

const validateFile = (file) => {
    const constraint = constraints[props.type]
    
    // Check file type
    if (!file.type.startsWith('image/')) {
        return 'Please select an image file'
    }
    
    // Check file size
    const fileSizeKB = file.size / 1024
    if (fileSizeKB > constraint.maxSize) {
        return `File size must be less than ${Math.round(constraint.maxSize / 1024)}MB`
    }
    
    return null
}

const uploadFile = async (file) => {
    error.value = ''
    
    // Validate file
    const validationError = validateFile(file)
    if (validationError) {
        error.value = validationError
        return
    }
    
    uploading.value = true
    uploadProgress.value = 0
    
    const formData = new FormData()
    formData.append('image', file)
    formData.append('type', props.type)
    
    try {
        const response = await axios.post('/api/entries/upload-image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                )
            }
        })
        
        if (response.data.success) {
            emit('uploaded', response.data)
        } else {
            error.value = response.data.message || 'Upload failed'
        }
    } catch (err) {
        console.error('Upload error:', err)
        error.value = err.response?.data?.message || 'Upload failed'
    } finally {
        uploading.value = false
        uploadProgress.value = 0
        
        // Reset file input
        if (fileInput.value) {
            fileInput.value.value = ''
        }
    }
}

const removeImage = async () => {
    if (!props.currentImage) return
    
    try {
        let path = ''
        
        // Handle both full URLs and relative paths
        if (props.currentImage.startsWith('http')) {
            // Full URL - extract path
            const url = new URL(props.currentImage)
            path = url.pathname.replace('/storage/', '')
        } else if (props.currentImage.startsWith('/storage/')) {
            // Storage path - remove /storage/ prefix
            path = props.currentImage.replace('/storage/', '')
        } else {
            // Assume it's a storage path without prefix
            path = props.currentImage
        }
        
        if (path) {
            await axios.delete('/api/entries/delete-image', {
                data: { path }
            })
        }
        
        emit('removed', props.type)
    } catch (err) {
        console.error('Delete error:', err)
        error.value = 'Failed to delete image'
    }
}
</script>