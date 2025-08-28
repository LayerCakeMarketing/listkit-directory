<template>
  <div class="cloudflare-uploader">
    <!-- Drop Zone -->
    <div
      ref="dropZone"
      class="upload-zone"
      :class="{
        'drag-over': isDragOver,
        'uploading': isUploading,
        'compact': compact
      }"
      @drop.prevent="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @click="openFileDialog"
    >
      <!-- Upload Icon and Text -->
      <div v-if="!isUploading && files.length === 0" class="upload-prompt">
        <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        <p class="upload-text">
          <span class="font-semibold">Click to upload</span> or drag and drop
        </p>
        <p class="upload-hint">
          {{ acceptedFormats }} (max {{ formatFileSize(maxFileSize) }} per file)
        </p>
        <p v-if="multiple" class="upload-limit">
          Up to {{ maxFiles }} files
        </p>
      </div>

      <!-- Progress Indicator -->
      <div v-if="isUploading" class="upload-progress">
        <div class="progress-spinner"></div>
        <p class="progress-text">Uploading {{ uploadingCount }} file(s)...</p>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: globalProgress + '%' }"></div>
        </div>
        <p class="progress-percent">{{ globalProgress }}%</p>
      </div>

      <!-- File List -->
      <div v-if="files.length > 0 && !compact" class="file-list">
        <div v-for="(file, index) in files" :key="index" class="file-item">
          <img v-if="file.preview" :src="file.preview" :alt="file.name" class="file-preview">
          <div class="file-info">
            <p class="file-name">{{ file.name }}</p>
            <p class="file-size">{{ formatFileSize(file.size) }}</p>
          </div>
          <div class="file-status">
            <template v-if="file.status === 'uploading'">
              <div class="status-spinner"></div>
              <span class="status-text">{{ file.progress }}%</span>
            </template>
            <template v-else-if="file.status === 'success'">
              <svg class="status-icon success" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
            </template>
            <template v-else-if="file.status === 'error'">
              <svg class="status-icon error" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <span class="error-text">{{ file.error }}</span>
            </template>
          </div>
          <button
            @click.stop="removeFile(index)"
            class="remove-button"
            :disabled="file.status === 'uploading'"
          >
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Hidden File Input -->
    <input
      ref="fileInput"
      type="file"
      :accept="accept"
      :multiple="multiple"
      @change="handleFileSelect"
      class="hidden"
    >

    <!-- Actions -->
    <div v-if="files.length > 0 && !autoUpload" class="upload-actions">
      <button
        @click="uploadAll"
        :disabled="isUploading || !hasFilesToUpload"
        class="upload-button"
      >
        Upload All
      </button>
      <button
        @click="clearAll"
        :disabled="isUploading"
        class="clear-button"
      >
        Clear All
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { compressImage } from '@/utils/imageCompression'

const props = defineProps({
  // Upload configuration
  multiple: {
    type: Boolean,
    default: false
  },
  maxFiles: {
    type: Number,
    default: 10
  },
  maxFileSize: {
    type: Number,
    default: 10 * 1024 * 1024 // 10MB
  },
  accept: {
    type: String,
    default: 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml'
  },
  autoUpload: {
    type: Boolean,
    default: true
  },
  
  // Entity association
  context: {
    type: String,
    default: null
  },
  entityType: {
    type: String,
    default: null
  },
  entityId: {
    type: [Number, String],
    default: null
  },
  
  // UI options
  compact: {
    type: Boolean,
    default: false
  },
  showPreview: {
    type: Boolean,
    default: true
  },
  
  // Additional metadata
  metadata: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits([
  'upload-success',
  'upload-error',
  'upload-progress',
  'files-added',
  'file-removed',
  'all-complete'
])

// Reactive state
const dropZone = ref(null)
const fileInput = ref(null)
const files = ref([])
const isDragOver = ref(false)
const isUploading = ref(false)

// Computed properties
const acceptedFormats = computed(() => {
  const formats = props.accept.split(',').map(f => f.split('/')[1]?.toUpperCase())
  return formats.join(', ')
})

const uploadingCount = computed(() => {
  return files.value.filter(f => f.status === 'uploading').length
})

const hasFilesToUpload = computed(() => {
  return files.value.some(f => !f.status || f.status === 'pending')
})

const globalProgress = computed(() => {
  if (files.value.length === 0) return 0
  const totalProgress = files.value.reduce((acc, file) => acc + (file.progress || 0), 0)
  return Math.round(totalProgress / files.value.length)
})

// File handling methods
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
  if (!isUploading.value) {
    fileInput.value.click()
  }
}

const handleFileSelect = (e) => {
  const selectedFiles = Array.from(e.target.files)
  addFiles(selectedFiles)
  // Reset input to allow selecting the same file again
  e.target.value = ''
}

const addFiles = async (newFiles) => {
  // Validate file types
  const validFiles = newFiles.filter(file => {
    if (!file.type.startsWith('image/')) {
      emit('upload-error', {
        filename: file.name,
        message: 'Only image files are allowed'
      })
      return false
    }
    
    if (file.size > props.maxFileSize) {
      emit('upload-error', {
        filename: file.name,
        message: `File size exceeds ${formatFileSize(props.maxFileSize)}`
      })
      return false
    }
    
    return true
  })
  
  // Check file count limit
  if (!props.multiple && files.value.length > 0) {
    emit('upload-error', {
      message: 'Only one file allowed'
    })
    return
  }
  
  const totalFiles = files.value.length + validFiles.length
  if (totalFiles > props.maxFiles) {
    emit('upload-error', {
      message: `Maximum ${props.maxFiles} files allowed`
    })
    return
  }
  
  // Process each file
  for (const file of validFiles) {
    // Compress if not SVG
    let processedFile = file
    if (file.type !== 'image/svg+xml') {
      try {
        processedFile = await compressImage(file, {
          maxWidth: 2048,
          maxHeight: 2048,
          quality: 0.9
        })
      } catch (error) {
        console.error('Compression failed, using original:', error)
      }
    }
    
    // Create file object
    const fileObj = {
      file: processedFile,
      name: file.name,
      size: processedFile.size,
      type: processedFile.type,
      status: 'pending',
      progress: 0,
      preview: null,
      result: null,
      error: null
    }
    
    // Generate preview if enabled
    if (props.showPreview && file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        fileObj.preview = e.target.result
      }
      reader.readAsDataURL(processedFile)
    }
    
    files.value.push(fileObj)
  }
  
  emit('files-added', validFiles.length)
  
  // Auto upload if enabled
  if (props.autoUpload && validFiles.length > 0) {
    uploadAll()
  }
}

const removeFile = (index) => {
  const removed = files.value.splice(index, 1)[0]
  emit('file-removed', removed.name)
}

const clearAll = () => {
  files.value = []
}

// Upload methods
const uploadAll = async () => {
  const pendingFiles = files.value.filter(f => !f.status || f.status === 'pending')
  if (pendingFiles.length === 0) return
  
  isUploading.value = true
  
  try {
    // Process in batches of 3 to avoid overwhelming the server
    const batchSize = 3
    for (let i = 0; i < pendingFiles.length; i += batchSize) {
      const batch = pendingFiles.slice(i, i + batchSize)
      await Promise.all(batch.map(fileObj => uploadFile(fileObj)))
    }
    
    emit('all-complete', files.value)
  } catch (error) {
    console.error('Upload batch error:', error)
  } finally {
    isUploading.value = false
  }
}

const uploadFile = async (fileObj) => {
  fileObj.status = 'uploading'
  fileObj.progress = 0
  
  try {
    // Step 1: Get upload URL from backend
    const axios = (await import('axios')).default
    const uploadUrlResponse = await axios.post('/api/cloudflare/upload-url', {
      context: props.context,
      entity_type: props.entityType,
      entity_id: props.entityId
    })
    
    if (!uploadUrlResponse.data.success) {
      throw new Error(uploadUrlResponse.data.message || 'Failed to get upload URL')
    }
    
    const { uploadURL, imageId } = uploadUrlResponse.data
    fileObj.progress = 20
    
    // Step 2: Upload to Cloudflare
    const formData = new FormData()
    formData.append('file', fileObj.file)
    
    const uploadResponse = await axios.post(uploadURL, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 60) / progressEvent.total) + 20
        fileObj.progress = Math.min(percentCompleted, 80)
        emit('upload-progress', {
          filename: fileObj.name,
          progress: fileObj.progress
        })
      }
    })
    
    fileObj.progress = 90
    
    // Step 3: Confirm upload with backend
    const confirmResponse = await axios.post('/api/cloudflare/confirm-upload', {
      cloudflare_id: imageId,
      filename: fileObj.name,
      context: props.context,
      entity_type: props.entityType,
      entity_id: props.entityId,
      metadata: props.metadata
    })
    
    fileObj.progress = 100
    fileObj.status = 'success'
    fileObj.result = {
      cloudflare_id: imageId,
      url: uploadResponse.data.result?.variants?.[0] || 
           `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${imageId}/public`,
      media: confirmResponse.data.media
    }
    
    emit('upload-success', {
      filename: fileObj.name,
      cloudflare_id: imageId,
      url: fileObj.result.url,
      media: fileObj.result.media
    })
    
  } catch (error) {
    console.error('Upload error:', error)
    fileObj.status = 'error'
    fileObj.error = error.response?.data?.message || error.message || 'Upload failed'
    fileObj.progress = 0
    
    emit('upload-error', {
      filename: fileObj.name,
      message: fileObj.error
    })
  }
}

// Utility functions
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Public methods for parent components
defineExpose({
  uploadAll,
  clearAll,
  files
})
</script>

<style scoped>
.cloudflare-uploader {
  @apply w-full;
}

.upload-zone {
  @apply relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer transition-all;
  @apply hover:border-gray-400 hover:bg-gray-50;
}

.upload-zone.compact {
  @apply p-4;
}

.upload-zone.drag-over {
  @apply border-blue-500 bg-blue-50;
}

.upload-zone.uploading {
  @apply cursor-not-allowed opacity-75;
}

.upload-prompt {
  @apply space-y-2;
}

.upload-icon {
  @apply mx-auto h-12 w-12 text-gray-400;
}

.upload-text {
  @apply text-sm text-gray-600;
}

.upload-hint {
  @apply text-xs text-gray-500;
}

.upload-limit {
  @apply text-xs text-gray-400;
}

.upload-progress {
  @apply space-y-4;
}

.progress-spinner {
  @apply mx-auto h-12 w-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin;
}

.progress-text {
  @apply text-sm font-medium text-gray-700;
}

.progress-bar {
  @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.progress-percent {
  @apply text-xs text-gray-500;
}

.file-list {
  @apply mt-6 space-y-3;
}

.file-item {
  @apply flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg;
}

.file-preview {
  @apply w-12 h-12 object-cover rounded;
}

.file-info {
  @apply flex-1 text-left;
}

.file-name {
  @apply text-sm font-medium text-gray-900 truncate;
}

.file-size {
  @apply text-xs text-gray-500;
}

.file-status {
  @apply flex items-center gap-2;
}

.status-spinner {
  @apply h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin;
}

.status-text {
  @apply text-xs text-gray-600;
}

.status-icon {
  @apply h-5 w-5;
}

.status-icon.success {
  @apply text-green-500;
}

.status-icon.error {
  @apply text-red-500;
}

.error-text {
  @apply text-xs text-red-600;
}

.remove-button {
  @apply p-1 text-gray-400 hover:text-red-500 transition-colors;
}

.remove-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.remove-button svg {
  @apply h-4 w-4;
}

.upload-actions {
  @apply mt-4 flex gap-3;
}

.upload-button {
  @apply px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.clear-button {
  @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>