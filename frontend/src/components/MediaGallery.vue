<template>
  <div>
    <!-- Media Gallery Button -->
    <button
      @click="openGallery"
      type="button"
      class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
    >
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      {{ buttonText || 'Media Gallery' }}
    </button>

    <!-- Media Gallery Modal -->
    <div v-if="showGallery" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeGallery"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Media Gallery
              </h3>
              <button
                @click="closeGallery"
                class="text-gray-400 hover:text-gray-500"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
              <p class="text-red-600">{{ error }}</p>
            </div>

            <!-- Media Grid -->
            <div v-else-if="media.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
              <div
                v-for="item in media"
                :key="item.id"
                class="relative group cursor-pointer"
                @click="selectMedia(item)"
              >
                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                  <img
                    :src="`https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.id}/public`"
                    :alt="item.filename"
                    class="w-full h-full object-cover group-hover:opacity-75 transition-opacity"
                  />
                </div>
                
                <!-- Selection indicator -->
                <div 
                  v-if="selectedMedia?.id === item.id"
                  class="absolute inset-0 bg-blue-500 bg-opacity-30 rounded-lg flex items-center justify-center"
                >
                  <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>

                <!-- Delete button -->
                <button
                  @click.stop="deleteMedia(item)"
                  class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                  :class="{ 'opacity-100': hoveredItem === item.id }"
                  @mouseenter="hoveredItem = item.id"
                  @mouseleave="hoveredItem = null"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>

                <!-- Image info -->
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 rounded-b-lg opacity-0 group-hover:opacity-100 transition-opacity">
                  <p class="text-xs truncate">{{ item.filename }}</p>
                  <p class="text-xs text-gray-300">{{ formatFileSize(item.file_size) }}</p>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No media found</h3>
              <p class="mt-1 text-sm text-gray-500">Upload some images to see them here.</p>
            </div>
          </div>

          <!-- Modal actions -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="confirmSelection"
              :disabled="!selectedMedia"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Select
            </button>
            <button
              @click="closeGallery"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  entityType: {
    type: String,
    required: true
  },
  entityId: {
    type: [String, Number],
    required: true
  },
  buttonText: {
    type: String,
    default: 'Media Gallery'
  }
})

const emit = defineEmits(['media-selected'])

const showGallery = ref(false)
const loading = ref(false)
const error = ref(null)
const media = ref([])
const selectedMedia = ref(null)
const hoveredItem = ref(null)

const openGallery = async () => {
  showGallery.value = true
  await fetchMedia()
}

const closeGallery = () => {
  showGallery.value = false
  selectedMedia.value = null
  error.value = null
}

const fetchMedia = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get(`/api/entities/${props.entityType}/${props.entityId}/media`)
    media.value = response.data.images || []
  } catch (err) {
    console.error('Error fetching media:', err)
    error.value = 'Failed to load media. Please try again.'
  } finally {
    loading.value = false
  }
}

const selectMedia = (item) => {
  selectedMedia.value = item
}

const confirmSelection = () => {
  if (selectedMedia.value) {
    // Transform the item to match the expected format for the uploader
    const transformedItem = {
      cloudflare_id: selectedMedia.value.id,
      filename: selectedMedia.value.filename,
      file_size: selectedMedia.value.file_size,
      url: selectedMedia.value.url
    }
    emit('media-selected', transformedItem)
    closeGallery()
  }
}

const deleteMedia = async (item) => {
  if (!confirm(`Are you sure you want to delete "${item.filename}"?`)) {
    return
  }

  try {
    await axios.delete(`/api/cloudflare/image/${item.id}`)
    
    // Remove from local array
    media.value = media.value.filter(m => m.id !== item.id)
    
    // Clear selection if deleted item was selected
    if (selectedMedia.value?.id === item.id) {
      selectedMedia.value = null
    }
  } catch (err) {
    console.error('Error deleting media:', err)
    alert('Failed to delete media. Please try again.')
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}
</script>