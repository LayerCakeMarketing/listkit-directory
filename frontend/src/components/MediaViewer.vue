<template>
  <div>
    <!-- Trigger Button -->
    <button
      @click="showModal = true"
      :class="buttonClass"
      class="inline-flex items-center"
    >
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      {{ buttonText }}
      <span v-if="imageCount !== null" class="ml-2 text-xs bg-gray-200 px-2 py-0.5 rounded-full">
        {{ imageCount }}
      </span>
    </button>

    <!-- Modal -->
    <Modal :show="showModal" @close="closeModal" max-width="6xl">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ title || `${entityType} Media` }}
          </h3>
          <div class="flex items-center space-x-2">
            <button
              @click="fetchMedia"
              :disabled="loading"
              class="p-2 text-gray-400 hover:text-gray-600"
              title="Refresh"
            >
              <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
            <button
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && images.length === 0" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="mt-2 text-sm text-red-600">{{ error }}</p>
          <button
            @click="fetchMedia"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Try Again
          </button>
        </div>

        <!-- Images Grid -->
        <div v-else-if="images.length > 0" class="space-y-4">
          <div class="text-sm text-gray-500">
            {{ images.length }} image{{ images.length !== 1 ? 's' : '' }} found
          </div>
          
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="image in images"
              :key="image.id"
              class="relative group cursor-pointer"
              @click="openLightbox(image)"
            >
              <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                <img
                  :src="getImageUrl(image, 'thumbnail')"
                  :alt="image.filename"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                  loading="lazy"
                />
              </div>
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-opacity rounded-lg flex items-center justify-center">
                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                </svg>
              </div>
              <div class="mt-2">
                <p class="text-sm text-gray-900 truncate">{{ image.filename }}</p>
                <p class="text-xs text-gray-500">
                  {{ formatDate(image.uploaded_at) }}
                  <span v-if="image.context" class="ml-1 px-1 py-0.5 bg-gray-100 rounded">{{ image.context }}</span>
                </p>
                <p v-if="!image.tracked_in_db" class="text-xs text-yellow-600 mt-1">Not tracked in DB</p>
              </div>
            </div>
          </div>

          <!-- Upload Button -->
          <div v-if="allowUpload" class="mt-6">
            <CloudflareDragDropUploader
              :max-files="10"
              :context="uploadContext || 'gallery'"
              :entity-type="getEntityType()"
              :entity-id="entityId"
              :metadata="{
                entity_id: entityId,
                entity_type: entityType
              }"
              @upload-success="handleUploadSuccess"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="mt-2 text-sm text-gray-900">No media found</p>
          <p class="text-xs text-gray-500">Images uploaded for this {{ entityType }} will appear here</p>
          
          <!-- Upload Button for Empty State -->
          <div v-if="allowUpload" class="mt-6">
            <CloudflareDragDropUploader
              :max-files="10"
              :context="uploadContext || 'gallery'"
              :entity-type="getEntityType()"
              :entity-id="entityId"
              :metadata="{
                entity_id: entityId,
                entity_type: entityType
              }"
              @upload-success="handleUploadSuccess"
            />
          </div>
        </div>
      </div>
    </Modal>

    <!-- Lightbox -->
    <div
      v-if="lightboxImage"
      @click="closeLightbox"
      class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4"
    >
      <div class="relative max-w-6xl max-h-full">
        <img
          :src="getImageUrl(lightboxImage, 'public')"
          :alt="lightboxImage.filename"
          class="max-w-full max-h-[90vh] object-contain"
          @click.stop
        />
        <button
          @click="closeLightbox"
          class="absolute top-4 right-4 text-white hover:text-gray-300"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <div class="absolute bottom-4 left-4 text-white">
          <p class="text-lg font-medium">{{ lightboxImage.filename }}</p>
          <p class="text-sm opacity-75">{{ formatDate(lightboxImage.uploaded_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'

const props = defineProps({
  entityType: {
    type: String,
    required: true,
    validator: (value) => ['user', 'list', 'place', 'region'].includes(value.toLowerCase())
  },
  entityId: {
    type: [String, Number],
    required: true
  },
  buttonText: {
    type: String,
    default: 'View Media'
  },
  buttonClass: {
    type: String,
    default: 'px-3 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm'
  },
  title: {
    type: String,
    default: null
  },
  allowUpload: {
    type: Boolean,
    default: false
  },
  uploadContext: {
    type: String,
    default: null
  },
  autoLoad: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['upload-success', 'media-loaded'])

// State
const showModal = ref(false)
const loading = ref(false)
const error = ref(null)
const images = ref([])
const lightboxImage = ref(null)
const imageCount = ref(null)

// Computed
const getEntityType = () => {
  const typeMap = {
    'user': 'App\\Models\\User',
    'list': 'App\\Models\\UserList',
    'place': 'App\\Models\\Place',
    'region': 'App\\Models\\Region'
  }
  return typeMap[props.entityType.toLowerCase()]
}

// Methods
const fetchMedia = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get(`/api/entities/${props.entityType}/${props.entityId}/media`)
    images.value = response.data.images || []
    imageCount.value = response.data.total || 0
    emit('media-loaded', images.value)
  } catch (err) {
    console.error('Error fetching media:', err)
    error.value = err.response?.data?.message || 'Failed to load media'
  } finally {
    loading.value = false
  }
}

const getImageUrl = (image, variant = 'public') => {
  if (image.variants && image.variants.length > 0) {
    const url = image.variants[0]
    if (variant === 'thumbnail') {
      return url.replace('/public', '/w=400,h=400,fit=cover')
    }
    return url
  }
  
  if (image.url) {
    return image.url
  }
  
  // Fallback
  const baseUrl = 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A'
  return `${baseUrl}/${image.id}/${variant}`
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const openLightbox = (image) => {
  lightboxImage.value = image
}

const closeLightbox = () => {
  lightboxImage.value = null
}

const closeModal = () => {
  showModal.value = false
}

const handleUploadSuccess = (result) => {
  // Refresh media list
  fetchMedia()
  emit('upload-success', result)
}

// Watchers
watch(showModal, (newVal) => {
  if (newVal && images.value.length === 0) {
    fetchMedia()
  }
})

// Lifecycle
onMounted(() => {
  if (props.autoLoad) {
    fetchMedia()
  }
})
</script>