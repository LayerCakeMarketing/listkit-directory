<template>
  <div 
    class="post-media-uploader"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <!-- Drag & Drop Overlay -->
    <div
      v-if="isDragging"
      class="fixed inset-0 z-50 bg-blue-50 bg-opacity-90 flex items-center justify-center pointer-events-none"
    >
      <div class="text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="text-xl font-medium text-blue-600">Drop files here</p>
        <p class="text-sm text-blue-500 mt-1">Images and videos accepted</p>
      </div>
    </div>
    <!-- Upload triggers -->
    <div v-if="!hasMedia" class="flex items-center space-x-2">
      <!-- Image Upload -->
      <button
        type="button"
        @click="openImagePicker"
        class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
        title="Add images"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </button>

      <!-- Video Upload -->
      <button
        type="button"
        @click="openVideoPicker"
        class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
        title="Add video"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </button>
    </div>

    <!-- Hidden file inputs -->
    <input
      ref="imageInput"
      type="file"
      accept="image/*"
      multiple
      class="hidden"
      @change="handleImageSelect"
    />
    
    <input
      ref="videoInput"
      type="file"
      accept="video/*"
      class="hidden"
      @change="handleVideoSelect"
    />

    <!-- Media Preview -->
    <div v-if="hasMedia" class="mt-3">
      <!-- Multiple Images Preview -->
      <div v-if="mediaType === 'images' && images.length > 0" class="space-y-3">
        <div 
          class="grid gap-2"
          :class="{
            'grid-cols-1': images.length === 1,
            'grid-cols-2': images.length === 2,
            'grid-cols-2 sm:grid-cols-3': images.length >= 3
          }"
        >
          <div
            v-for="(image, index) in images"
            :key="index"
            class="relative group rounded-lg overflow-hidden bg-gray-100"
          >
            <img 
              :src="image.preview" 
              :alt="`Image ${index + 1}`"
              class="w-full h-full object-cover"
              :class="{
                'max-h-96': images.length === 1,
                'aspect-square': images.length > 1
              }"
            />
            
            <!-- Upload progress overlay -->
            <div 
              v-if="image.uploading"
              class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
            >
              <div class="text-white text-center">
                <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                <span class="text-sm">{{ image.progress }}%</span>
              </div>
            </div>

            <!-- Remove button -->
            <button
              v-if="!image.uploading"
              @click="removeImage(index)"
              class="absolute top-2 right-2 p-1 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-70 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Add more images button -->
        <button
          v-if="images.length < maxImages && !isUploading"
          @click="openImagePicker"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          + Add more images ({{ maxImages - images.length }} remaining)
        </button>
      </div>

      <!-- Video Preview -->
      <div v-else-if="mediaType === 'video' && video" class="relative rounded-lg overflow-hidden max-w-sm">
        <video 
          :src="video.preview" 
          controls 
          class="w-full h-auto"
        />
        
        <!-- Upload progress overlay -->
        <div 
          v-if="video.uploading"
          class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
        >
          <div class="text-white text-center">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span class="text-sm">{{ video.progress }}%</span>
          </div>
        </div>

        <!-- Remove button -->
        <button
          v-if="!video.uploading"
          @click="removeVideo"
          class="absolute top-2 right-2 p-1 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-70"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import axios from 'axios'
import imageCompression from 'browser-image-compression'

const props = defineProps({
  maxImages: {
    type: Number,
    default: 4
  },
  maxImageSize: {
    type: Number,
    default: 10 * 1024 * 1024 // 10MB
  },
  maxVideoSize: {
    type: Number,
    default: 100 * 1024 * 1024 // 100MB
  }
})

const emit = defineEmits(['media-uploaded', 'media-removed', 'error'])

// Refs
const imageInput = ref(null)
const videoInput = ref(null)

// State
const images = ref([])
const video = ref(null)
const mediaType = ref(null)
const error = ref('')
const isUploading = ref(false)
const isDragging = ref(false)
let dragCounter = 0

// Computed
const hasMedia = computed(() => images.value.length > 0 || video.value !== null)

// Methods
const openImagePicker = () => {
  imageInput.value.click()
}

const openVideoPicker = () => {
  videoInput.value.click()
}

const handleImageSelect = async (event) => {
  const files = Array.from(event.target.files)
  
  // Validate number of images
  const remainingSlots = props.maxImages - images.value.length
  if (files.length > remainingSlots) {
    error.value = `You can only add ${remainingSlots} more image(s)`
    setTimeout(() => error.value = '', 3000)
    return
  }

  // Process each file
  for (const file of files) {
    if (!validateImageFile(file)) continue
    
    // Compress image before upload
    let processedFile = file
    try {
      const options = {
        maxSizeMB: 2, // Maximum size in MB
        maxWidthOrHeight: 2048, // Maximum width or height
        useWebWorker: true,
        fileType: file.type,
      }
      
      console.log(`Compressing ${file.name}: ${(file.size / 1024 / 1024).toFixed(2)}MB`)
      processedFile = await imageCompression(file, options)
      console.log(`Compressed to: ${(processedFile.size / 1024 / 1024).toFixed(2)}MB`)
    } catch (compressionError) {
      console.error('Compression failed, using original:', compressionError)
      // Use original file if compression fails
    }
    
    const imageData = {
      file: processedFile,
      preview: URL.createObjectURL(processedFile),
      uploading: true,
      progress: 0,
      url: null,
      fileId: null,
      metadata: null
    }
    
    images.value.push(imageData)
    mediaType.value = 'images'
    
    // Upload to ImageKit
    await uploadToImageKit(imageData, images.value.length - 1)
  }
  
  // Reset input
  event.target.value = ''
}

const handleVideoSelect = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  if (!validateVideoFile(file)) {
    event.target.value = ''
    return
  }
  
  // Remove any existing media
  clearAllMedia()
  
  const videoData = {
    file,
    preview: URL.createObjectURL(file),
    uploading: true,
    progress: 0,
    url: null,
    fileId: null,
    metadata: null
  }
  
  video.value = videoData
  mediaType.value = 'video'
  
  // Upload to ImageKit
  await uploadToImageKit(videoData, -1, 'video')
  
  // Reset input
  event.target.value = ''
}

const validateImageFile = (file) => {
  // Check file type
  if (!file.type.startsWith('image/')) {
    error.value = 'Please select a valid image file'
    setTimeout(() => error.value = '', 3000)
    return false
  }
  
  // Check file size
  if (file.size > props.maxImageSize) {
    error.value = `Image size must be less than ${props.maxImageSize / 1024 / 1024}MB`
    setTimeout(() => error.value = '', 3000)
    return false
  }
  
  return true
}

const validateVideoFile = (file) => {
  // Check file type
  if (!file.type.startsWith('video/')) {
    error.value = 'Please select a valid video file'
    setTimeout(() => error.value = '', 3000)
    return false
  }
  
  // Check file size
  if (file.size > props.maxVideoSize) {
    error.value = `Video size must be less than ${props.maxVideoSize / 1024 / 1024}MB`
    setTimeout(() => error.value = '', 3000)
    return false
  }
  
  return true
}

const getImageKitAuth = async () => {
  // Always get fresh auth params for each upload - ImageKit requires unique tokens
  try {
    const response = await axios.get('/api/imagekit/auth')
    return response.data
  } catch (err) {
    console.error('Failed to get ImageKit auth:', err)
    throw new Error('Failed to authenticate upload')
  }
}

const uploadToImageKit = async (mediaData, index, type = 'image') => {
  try {
    isUploading.value = true
    
    // Get authentication
    const auth = await getImageKitAuth()
    console.log('ImageKit auth params:', auth)
    
    // Prepare form data
    const formData = new FormData()
    formData.append('file', mediaData.file)
    formData.append('publicKey', auth.publicKey)
    formData.append('signature', auth.signature)
    formData.append('expire', auth.expire)
    formData.append('token', auth.token)
    formData.append('fileName', mediaData.file.name)
    formData.append('folder', `/posts/${type}s`)
    
    console.log('Uploading file:', mediaData.file.name, 'Size:', mediaData.file.size)
    
    // Simulate progress since fetch doesn't support upload progress
    const progressInterval = setInterval(() => {
      if (type === 'image' && images.value[index]) {
        images.value[index].progress = Math.min(images.value[index].progress + 10, 90)
      } else if (type === 'video' && video.value) {
        video.value.progress = Math.min(video.value.progress + 10, 90)
      }
    }, 200)
    
    // Create a clean fetch request without any default headers
    const response = await fetch('https://upload.imagekit.io/api/v1/files/upload', {
      method: 'POST',
      body: formData
    })
    
    clearInterval(progressInterval)
    
    if (!response.ok) {
      let errorMessage = `Upload failed: ${response.status} ${response.statusText}`
      try {
        const errorData = await response.json()
        console.error('ImageKit error response:', errorData)
        if (errorData.message) {
          errorMessage = errorData.message
        }
      } catch (e) {
        // If response is not JSON, use default error message
      }
      throw new Error(errorMessage)
    }
    
    const responseData = await response.json()
    console.log('ImageKit upload success:', responseData)
    
    // Update media data with response
    const uploadedData = {
      url: responseData.url,
      fileId: responseData.fileId,
      metadata: {
        name: responseData.name,
        size: responseData.size,
        width: responseData.width,
        height: responseData.height,
        thumbnailUrl: responseData.thumbnailUrl
      }
    }
    
    if (type === 'image') {
      images.value[index] = {
        ...images.value[index],
        ...uploadedData,
        uploading: false,
        progress: 100
      }
    } else {
      video.value = {
        ...video.value,
        ...uploadedData,
        uploading: false,
        progress: 100
      }
    }
    
    // Emit upload event
    emitMediaData()
    
  } catch (err) {
    console.error('Upload error:', err)
    error.value = 'Failed to upload file. Please try again.'
    setTimeout(() => error.value = '', 3000)
    
    // Remove failed upload
    if (type === 'image') {
      images.value.splice(index, 1)
    } else {
      video.value = null
    }
  } finally {
    isUploading.value = false
  }
}

const removeImage = (index) => {
  // Revoke object URL
  URL.revokeObjectURL(images.value[index].preview)
  
  // Remove from array
  images.value.splice(index, 1)
  
  // Reset media type if no images left
  if (images.value.length === 0) {
    mediaType.value = null
  }
  
  // Emit update
  emitMediaData()
}

const removeVideo = () => {
  // Revoke object URL
  URL.revokeObjectURL(video.value.preview)
  
  // Clear video
  video.value = null
  mediaType.value = null
  
  // Emit update
  emitMediaData()
}

const clearAllMedia = () => {
  // Revoke all object URLs
  images.value.forEach(img => URL.revokeObjectURL(img.preview))
  if (video.value) URL.revokeObjectURL(video.value.preview)
  
  // Clear all media
  images.value = []
  video.value = null
  mediaType.value = null
  error.value = ''
}

const emitMediaData = () => {
  const mediaData = {
    type: mediaType.value,
    images: images.value.filter(img => !img.uploading).map(img => ({
      url: img.url,
      fileId: img.fileId,
      metadata: img.metadata
    })),
    video: video.value && !video.value.uploading ? {
      url: video.value.url,
      fileId: video.value.fileId,
      metadata: video.value.metadata
    } : null
  }
  
  emit('media-uploaded', mediaData)
}

// Drag and drop handlers
const onDragOver = (e) => {
  e.preventDefault()
  dragCounter++
  isDragging.value = true
}

const onDragLeave = (e) => {
  e.preventDefault()
  dragCounter--
  if (dragCounter === 0) {
    isDragging.value = false
  }
}

const onDrop = (e) => {
  e.preventDefault()
  dragCounter = 0
  isDragging.value = false
  
  const files = Array.from(e.dataTransfer.files)
  
  // Separate images and videos
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  const videoFiles = files.filter(file => file.type.startsWith('video/'))
  
  // Handle dropped files
  if (videoFiles.length > 0) {
    // If video is dropped, process only the first video
    const fakeEvent = {
      target: {
        files: [videoFiles[0]]
      }
    }
    handleVideoSelect(fakeEvent)
  } else if (imageFiles.length > 0) {
    // Process image files
    const fakeEvent = {
      target: {
        files: imageFiles
      }
    }
    handleImageSelect(fakeEvent)
  } else {
    error.value = 'Please drop only image or video files'
    setTimeout(() => error.value = '', 3000)
  }
}

// Cleanup on unmount
onUnmounted(() => {
  images.value.forEach(img => URL.revokeObjectURL(img.preview))
  if (video.value) URL.revokeObjectURL(video.value.preview)
})

// Expose methods for parent component
defineExpose({
  clearAllMedia
})
</script>