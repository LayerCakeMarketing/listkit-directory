<template>
    <div :class="compact ? '' : 'w-full'">
        <!-- Trigger Slot for Compact Mode -->
        <div v-if="compact && $slots.trigger" @click="openFileDialog" class="cursor-pointer">
            <slot name="trigger" />
            <input
                ref="fileInput"
                type="file"
                multiple
                :accept="acceptedTypes"
                @change="handleFileSelect"
                class="hidden"
            />
        </div>
        
        <!-- Upload Area -->
        <div
            v-if="!compact || !$slots.trigger"
            ref="dropZone"
            @dragover.prevent="handleDragOver"
            @dragleave.prevent="handleDragLeave"
            @drop.prevent="handleDrop"
            @click="openFileDialog"
            :class="[
                'relative border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200 cursor-pointer',
                isDragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
                isUploading ? 'pointer-events-none opacity-50' : ''
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                :accept="acceptedTypes"
                @change="handleFileSelect"
                class="hidden"
            />
            
            <div class="space-y-4">
                <!-- Upload Icon -->
                <div class="flex justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                
                <!-- Upload Text -->
                <div>
                    <p class="text-lg font-medium text-gray-900">
                        {{ isDragOver ? 'Drop files here to upload' : 'Drag and drop images here' }}
                    </p>
                    <p class="text-sm text-gray-500">
                        or click to select files (auto-upload enabled)
                    </p>
                    <p class="text-xs text-gray-400 mt-2">
                        Max {{ maxFiles }} files, {{ formatFileSize(maxFileSize) }} each
                    </p>
                </div>
                
                <!-- Media Gallery Button -->
                <div v-if="entityType && entityId" class="mt-4">
                    <div class="text-sm text-gray-500 mb-2">or</div>
                    <MediaGallery
                        :entity-type="mediaGalleryEntityType"
                        :entity-id="entityId"
                        button-text="Choose from Gallery"
                        @media-selected="handleMediaSelected"
                        @click.stop
                    />
                </div>
                
                <!-- Global Progress -->
                <div v-if="isUploading" class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${globalProgress}%` }"
                    ></div>
                </div>
            </div>
        </div>
        
        <!-- File Previews -->
        <div v-if="showPreview && files.length > 0" class="mt-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ files.length }} file{{ files.length > 1 ? 's' : '' }} selected
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="(file, index) in files"
                    :key="index"
                    class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm relative"
                >
                    <!-- Image Preview -->
                    <div class="aspect-square mb-3 bg-gray-100 rounded-lg overflow-hidden">
                        <img
                            v-if="file.preview"
                            :src="file.preview"
                            :alt="file.name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- File Info -->
                    <div class="space-y-2">
                        <h4 class="font-medium text-gray-900 text-sm truncate">{{ file.name }}</h4>
                        <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                        
                        <!-- Upload Status -->
                        <div class="flex items-center space-x-2">
                            <div v-if="file.status === 'pending'" class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                <span class="text-xs text-gray-500">Pending</span>
                            </div>
                            
                            <div v-else-if="file.status === 'uploading'" class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                <span class="text-xs text-blue-600">Uploading...</span>
                            </div>
                            
                            <div v-else-if="file.status === 'success'" class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-xs text-green-600">✓ Uploaded</span>
                            </div>
                            
                            <div v-else-if="file.status === 'error'" class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-xs text-red-600">✗ Error</span>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div v-if="file.status === 'uploading'" class="w-full bg-gray-200 rounded-full h-1">
                            <div
                                class="bg-blue-600 h-1 rounded-full transition-all duration-300"
                                :style="{ width: `${file.progress}%` }"
                            ></div>
                        </div>
                        
                        <!-- Error Message -->
                        <div v-if="file.status === 'error' && file.error" class="text-xs text-red-600">
                            {{ file.error }}
                        </div>
                        
                        <!-- Success Info -->
                        <div v-if="file.status === 'success' && file.result" class="text-xs text-gray-600">
                            <div>ID: {{ file.result.id }}</div>
                            <div class="truncate">URL: {{ file.result.url }}</div>
                        </div>
                    </div>
                    
                    <!-- Remove Button -->
                    <button
                        v-if="file.status !== 'uploading'"
                        @click="removeFile(index)"
                        class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-4">
                <button
                    @click="clearFiles"
                    :disabled="isUploading"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Clear All
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { compressImage } from '@/utils/imageCompression'
import MediaGallery from './MediaGallery.vue'

const props = defineProps({
    maxFiles: {
        type: Number,
        default: 10
    },
    maxFileSize: {
        type: Number,
        default: 14680064 // 14MB in bytes
    },
    acceptedTypes: {
        type: String,
        default: 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml'
    },
    context: {
        type: String,
        default: null,
        validator: (value) => !value || ['avatar', 'cover', 'gallery', 'logo', 'item', 'post', 'avatar_temp', 'banner_temp', 'banner', 'region_cover'].includes(value)
    },
    entityType: {
        type: String,
        default: null
    },
    entityId: {
        type: [Number, String],
        default: null
    },
    showPreview: {
        type: Boolean,
        default: true
    },
    compact: {
        type: Boolean,
        default: false
    },
    metadata: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['upload-success', 'upload-error', 'upload-progress', 'update:modelValue'])

const dropZone = ref(null)
const fileInput = ref(null)
const files = ref([])
const isDragOver = ref(false)
const isUploading = ref(false)

const globalProgress = computed(() => {
    if (files.value.length === 0) return 0
    const totalProgress = files.value.reduce((acc, file) => acc + (file.progress || 0), 0)
    return Math.round(totalProgress / files.value.length)
})

// Transform entity type for MediaGallery component
const mediaGalleryEntityType = computed(() => {
    if (props.entityType === 'App\\Models\\DirectoryEntry') {
        return 'DirectoryEntry'
    }
    return props.entityType
})

const handleDragOver = (e) => {
    isDragOver.value = true
}

const handleDragLeave = (e) => {
    isDragOver.value = false
}

const handleDrop = (e) => {
    isDragOver.value = false
    const droppedFiles = Array.from(e.dataTransfer.files)
    addFiles(droppedFiles)
}

const openFileDialog = () => {
    fileInput.value.click()
}

const handleFileSelect = (e) => {
    const selectedFiles = Array.from(e.target.files)
    addFiles(selectedFiles)
}

const addFiles = async (newFiles) => {
    const validFiles = newFiles.filter(file => {
        if (!file.type.startsWith('image/')) {
            emit('upload-error', { filename: file.name, message: 'Only image files are allowed' })
            return false
        }
        
        if (file.size > props.maxFileSize) {
            emit('upload-error', { filename: file.name, message: `File size exceeds ${formatFileSize(props.maxFileSize)}` })
            return false
        }
        
        return true
    })
    
    const totalFiles = files.value.length + validFiles.length
    if (totalFiles > props.maxFiles) {
        emit('upload-error', { filename: 'Multiple files', message: `Maximum ${props.maxFiles} files allowed` })
        return
    }
    
    // Process each file with compression
    for (const file of validFiles) {
        // Compress the image if it's not SVG
        let processedFile = file
        if (file.type !== 'image/svg+xml') {
            try {
                processedFile = await compressImage(file, {
                    maxWidth: 2048,
                    maxHeight: 2048,
                    quality: 0.85,
                    outputType: file.type  // Preserve original file type
                })
                console.log(`Compressed ${file.name}: ${file.size} -> ${processedFile.size} bytes`)
            } catch (err) {
                console.warn('Failed to compress image, using original:', err)
            }
        }
        
        const fileObj = {
            file: processedFile,
            name: processedFile.name,
            size: processedFile.size,
            originalSize: file.size,
            preview: null,
            status: 'pending',
            progress: 0,
            error: null,
            result: null
        }
        
        // Generate preview
        const reader = new FileReader()
        reader.onload = (e) => {
            fileObj.preview = e.target.result
        }
        reader.readAsDataURL(processedFile)
        
        files.value.push(fileObj)
    }
    
    // Auto-upload the new files
    if (validFiles.length > 0) {
        await uploadFiles()
    }
}

const removeFile = (index) => {
    files.value.splice(index, 1)
    
    // Update v-model value after removing file
    if (props.maxFiles === 1) {
        emit('update:modelValue', null)
    } else {
        const urls = files.value
            .filter(f => f.status === 'success' && f.result?.url)
            .map(f => f.result.url)
        emit('update:modelValue', urls)
    }
}

const clearFiles = () => {
    files.value = []
}

const uploadFiles = async () => {
    const pendingFiles = files.value.filter(f => f.status === 'pending')
    if (pendingFiles.length === 0) return
    
    isUploading.value = true
    
    try {
        // Get upload URL from backend
        const requestBody = {}
        if (props.context) requestBody.context = props.context
        if (props.entityType) requestBody.entity_type = props.entityType
        if (props.entityId) requestBody.entity_id = props.entityId
        if (props.metadata && Object.keys(props.metadata).length > 0) {
            requestBody.metadata = props.metadata
        }

        // Use axios instead of fetch to leverage our configured interceptors
        const axios = (await import('axios')).default
        const response = await axios.post('/api/cloudflare/upload-url', requestBody)
        
        console.log('Upload URL response:', response.data)
        
        const responseData = response.data
        
        console.log('Upload URL response:', responseData)
        
        const { uploadURL } = responseData
        console.log('Using upload URL:', uploadURL)
        
        // Upload each file
        const uploadPromises = pendingFiles.map(fileObj => uploadFile(fileObj, uploadURL))
        await Promise.all(uploadPromises)
        
    } catch (error) {
        console.error('Upload error:', error)
        pendingFiles.forEach(fileObj => {
            fileObj.status = 'error'
            fileObj.error = error.message
            emit('upload-error', { filename: fileObj.name, message: error.message })
        })
    } finally {
        isUploading.value = false
    }
}

const uploadFile = async (fileObj, uploadURL) => {
    try {
        fileObj.status = 'uploading'
        fileObj.progress = 0
        
        // Debug file info
        console.log('Uploading file:', {
            name: fileObj.file.name,
            type: fileObj.file.type,
            size: fileObj.file.size
        })
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']
        let fileType = fileObj.file.type
        
        // If browser didn't detect MIME type, try to infer from extension
        if (!fileType || fileType === '') {
            const ext = fileObj.file.name.split('.').pop().toLowerCase()
            const typeMap = {
                'jpg': 'image/jpeg',
                'jpeg': 'image/jpeg',
                'png': 'image/png',
                'webp': 'image/webp',
                'gif': 'image/gif',
                'svg': 'image/svg+xml'
            }
            fileType = typeMap[ext] || ''
        }
        
        if (!allowedTypes.includes(fileType)) {
            throw new Error(`Invalid file type: ${fileType || 'unknown'}. Allowed types: JPEG, PNG, WebP, GIF, SVG`)
        }
        
        // For Cloudflare direct upload, use FormData
        const xhr = new XMLHttpRequest()
        const formData = new FormData()
        formData.append('file', fileObj.file)
        
        // Add requireSignedURLs parameter as suggested in the reference
        formData.append('requireSignedURLs', 'false')
        
        // Debug logging
        console.log('FormData being sent to Cloudflare:')
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}: File { name: "${value.name}", type: "${value.type}", size: ${value.size} }`)
            } else {
                console.log(`${key}: ${value}`)
            }
        }
        
        return new Promise((resolve, reject) => {
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    fileObj.progress = Math.round((e.loaded / e.total) * 100)
                    emit('upload-progress', {
                        filename: fileObj.name,
                        progress: fileObj.progress
                    })
                }
            })
            
            xhr.addEventListener('load', () => {
                console.log('Cloudflare response status:', xhr.status)
                console.log('Cloudflare response:', xhr.responseText)
                
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText)
                        console.log('Parsed Cloudflare response:', response)
                        
                        // Check if upload was successful
                        if (!response.success || !response.result) {
                            throw new Error(response.errors?.[0]?.message || 'Upload failed')
                        }
                        
                        fileObj.status = 'success'
                        fileObj.progress = 100
                        
                        // Extract ID from the response
                        const imageId = response.result?.id || response.imageId
                        
                        fileObj.result = {
                            id: imageId,
                            url: response.result?.variants?.[0] || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${imageId}/public`,
                            filename: fileObj.name
                        }

                        // Confirm upload with database tracking
                        confirmUpload(fileObj.result)
                        
                        emit('upload-success', fileObj.result)
                        
                        // Emit v-model update for single file uploads
                        if (props.maxFiles === 1) {
                            const valueToEmit = props.modelValue === null || typeof props.modelValue === 'string' && props.modelValue.includes('http') 
                                ? fileObj.result.url 
                                : fileObj.result.id
                            emit('update:modelValue', valueToEmit)
                        } else {
                            // For multiple files, emit an array of URLs
                            const urls = files.value
                                .filter(f => f.status === 'success' && f.result?.url)
                                .map(f => f.result.url)
                            emit('update:modelValue', urls)
                        }
                        
                        resolve(response)
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError)
                        reject(new Error('Invalid response from Cloudflare'))
                    }
                } else {
                    console.error('Upload failed:', xhr.status, xhr.responseText)
                    
                    try {
                        const errorResponse = JSON.parse(xhr.responseText)
                        reject(new Error(errorResponse.errors?.[0]?.message || `Upload failed with status ${xhr.status}`))
                    } catch {
                        reject(new Error(`Upload failed with status ${xhr.status}: ${xhr.responseText}`))
                    }
                }
            })
            
            xhr.addEventListener('error', () => {
                reject(new Error('Network error during upload'))
            })
            
            xhr.open('POST', uploadURL)
            // Important: Do NOT set any headers. Let the browser set them automatically for FormData
            // This ensures the correct Content-Type with boundary is set
            xhr.send(formData)
        })
        
    } catch (error) {
        fileObj.status = 'error'
        fileObj.error = error.message
        emit('upload-error', { filename: fileObj.name, message: error.message })
        throw error
    }
}

const confirmUpload = async (uploadResult) => {
    try {
        const requestBody = {
            cloudflare_id: uploadResult.id,
            filename: uploadResult.filename,
        }
        
        if (props.context) requestBody.context = props.context
        if (props.entityType) requestBody.entity_type = props.entityType
        if (props.entityId) requestBody.entity_id = props.entityId
        if (props.metadata && Object.keys(props.metadata).length > 0) {
            requestBody.metadata = props.metadata
        }

        const axios = (await import('axios')).default
        const response = await axios.post('/api/cloudflare/confirm-upload', requestBody)
        
        console.log('Upload confirmed in database:', response.data)
    } catch (error) {
        console.warn('Error confirming upload:', error)
        // Don't fail the upload for database tracking errors
    }
}

const handleMediaSelected = (mediaItem) => {
    // Create a file-like object from the selected media
    const selectedFile = {
        name: mediaItem.filename,
        size: mediaItem.file_size,
        preview: `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${mediaItem.cloudflare_id}/public`,
        status: 'success',
        progress: 100,
        result: {
            id: mediaItem.cloudflare_id,
            url: `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${mediaItem.cloudflare_id}/public`,
            filename: mediaItem.filename
        }
    }

    // Add to files if not at max capacity
    if (files.value.length < props.maxFiles) {
        files.value.push(selectedFile)
    } else {
        // Replace the last file if at capacity
        files.value[files.value.length - 1] = selectedFile
    }

    // Emit the upload success event
    emit('upload-success', selectedFile.result)

    // Emit v-model update for single file uploads
    if (props.maxFiles === 1) {
        const valueToEmit = props.modelValue === null || (typeof props.modelValue === 'string' && props.modelValue.includes('http'))
            ? selectedFile.result.url 
            : selectedFile.result.id
        emit('update:modelValue', valueToEmit)
    } else {
        // For multiple files, emit an array of URLs
        const urls = files.value
            .filter(f => f.status === 'success' && f.result?.url)
            .map(f => f.result.url)
        emit('update:modelValue', urls)
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

onMounted(() => {
    // Prevent default drag behaviors on the document
    document.addEventListener('dragover', (e) => e.preventDefault())
    document.addEventListener('drop', (e) => e.preventDefault())
})
</script>

<style scoped>
.aspect-square {
    aspect-ratio: 1 / 1;
}
</style>