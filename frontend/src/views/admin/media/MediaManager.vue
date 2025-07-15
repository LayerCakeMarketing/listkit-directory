<template>
  <div class="py-16">
      <!-- Header -->
      <div class="bg-white shadow">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center py-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Media Management</h1>
              <p class="mt-1 text-sm text-gray-500">Manage all uploaded images across your platform</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="performCleanup"
                :disabled="cleaning"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 disabled:opacity-50"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ cleaning ? 'Cleaning...' : 'Cleanup Unused' }}
              </button>
              <button
                @click="fetchMedia"
                :disabled="loading"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ loading ? 'Loading...' : 'Refresh' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
            <div class="text-sm text-blue-600 font-medium">Total Images</div>
            <div class="text-2xl font-bold text-blue-900">{{ stats.total_images || 0 }}</div>
            <div class="text-xs text-blue-500 mt-1">{{ stats.tracked_images || 0 }} tracked in DB</div>
          </div>
          <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
            <div class="text-sm text-green-600 font-medium">User Images</div>
            <div class="text-2xl font-bold text-green-900">{{ stats.user_images || 0 }}</div>
            <div class="text-xs text-green-500 mt-1">avatars & covers</div>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
            <div class="text-sm text-purple-600 font-medium">Region Images</div>
            <div class="text-2xl font-bold text-purple-900">{{ stats.region_images || 0 }}</div>
            <div class="text-xs text-purple-500 mt-1">location covers</div>
          </div>
          <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
            <div class="text-sm text-orange-600 font-medium">Place Images</div>
            <div class="text-2xl font-bold text-orange-900">{{ stats.place_images || 0 }}</div>
            <div class="text-xs text-orange-500 mt-1">logos & galleries</div>
          </div>
          <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
            <div class="text-sm text-indigo-600 font-medium">List Images</div>
            <div class="text-2xl font-bold text-indigo-900">{{ stats.list_images || 0 }}</div>
            <div class="text-xs text-indigo-500 mt-1">list items</div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <input
                v-model="filters.search"
                @input="debouncedSearch"
                type="text"
                placeholder="Filename or ID..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Context</label>
              <select
                v-model="filters.context"
                @change="fetchMedia"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">All Contexts</option>
                <option value="avatar">Avatar</option>
                <option value="cover">Cover</option>
                <option value="region_cover">Region Cover</option>
                <option value="gallery">Gallery</option>
                <option value="logo">Logo</option>
                <option value="item">Item</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
              <select
                v-model="filters.entity_type"
                @change="fetchMedia"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">All Types</option>
                <option value="App\\Models\\User">User</option>
                <option value="region">Region</option>
                <option value="App\\Models\\DirectoryEntry">Place</option>
                <option value="App\\Models\\UserList">List</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
              <select
                v-model="filters.date_range"
                @change="fetchMedia"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                v-model="filters.status"
                @change="fetchMedia"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="orphaned">Orphaned</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !images.data?.length" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Bulk Selection Bar -->
        <div v-if="images.data?.length > 0" class="bg-white rounded-lg shadow mb-4 p-4 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <input
              type="checkbox"
              :checked="isAllSelected"
              :indeterminate="isPartiallySelected"
              @change="toggleSelectAll"
              class="h-4 w-4 text-blue-600 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
              {{ selectedImages.length }} of {{ images.data.length }} selected
            </span>
          </div>
          <div v-if="selectedImages.length > 0" class="flex gap-2">
            <button
              @click="clearSelection"
              class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm"
            >
              Clear Selection
            </button>
            <button
              @click="deleteSelected"
              class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm"
            >
              Delete Selected ({{ selectedImages.length }})
            </button>
          </div>
        </div>

        <!-- Image Grid -->
        <div v-if="images.data?.length > 0" :key="refreshKey">
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
              v-for="image in images.data"
              :key="image.id"
              class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-lg transition-shadow relative group"
            >
              <!-- Selection Checkbox -->
              <div class="absolute top-2 right-2 z-10">
                <input
                  type="checkbox"
                  :value="image.id"
                  v-model="selectedImages"
                  @click.stop="handleCheckboxClick($event, image.id)"
                  @change.stop
                  class="h-4 w-4 text-blue-600 rounded border-gray-300 bg-white shadow-sm"
                />
              </div>

              <!-- Image Preview -->
              <div 
                class="aspect-square bg-gray-100 relative cursor-pointer"
                @click="viewImageDetails(image)"
              >
                <img
                  :src="getImageUrl(image, 'thumbnail')"
                  :alt="image.filename"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div v-if="image.context" class="absolute top-2 left-2">
                  <span class="px-2 py-1 text-xs font-medium rounded-full bg-black bg-opacity-50 text-white">
                    {{ image.context }}
                  </span>
                </div>
                
                <!-- Hover Actions -->
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
                  <div class="flex gap-2">
                    <button
                      @click.stop="viewImageDetails(image)"
                      class="p-2 bg-white rounded-full shadow-lg hover:bg-gray-100"
                      title="View Details"
                    >
                      <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button
                      @click.stop="deleteImage(image)"
                      class="p-2 bg-white rounded-full shadow-lg hover:bg-red-50"
                      title="Delete"
                    >
                      <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Image Info -->
              <div class="p-3">
                <p class="text-sm font-medium text-gray-900 truncate">{{ image.filename }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ formatBytes(image.file_size) }} • {{ formatDate(image.uploaded_at) }}</p>
                
                <!-- Tracking Status -->
                <div v-if="!image.tracked_in_db" class="mt-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                    Not tracked
                  </span>
                </div>
                
                <div v-if="image.entity_type" class="mt-2">
                  <span class="text-xs text-gray-600">
                    {{ getEntityName(image.entity_type) }}
                    <span v-if="image.metadata?.region_name">({{ image.metadata.region_name }})</span>
                  </span>
                </div>
                <div v-if="image.user" class="mt-2 flex items-center">
                  <img 
                    v-if="image.user.avatar_url"
                    :src="image.user.avatar_url" 
                    :alt="image.user.name"
                    class="w-5 h-5 rounded-full mr-1"
                  />
                  <span class="text-xs text-gray-600">{{ image.user.name }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="lastPage > 1" class="mt-6">
            <Pagination 
              :current-page="currentPage"
              :last-page="lastPage"
              :total="total"
              :from="from"
              :to="to"
              :per-page="perPage"
              @change-page="fetchMedia"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && images.data?.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No media found</h3>
          <p class="mt-1 text-sm text-gray-500">Try adjusting your filters</p>
        </div>

      </div>

      <!-- Image Detail Modal -->
      <Modal :show="showDetailModal" @close="closeDetailModal" max-width="4xl">
        <div v-if="selectedImage" class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Image Details</h3>
            <button
              @click="closeDetailModal"
              class="text-gray-400 hover:text-gray-500"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Image Preview -->
            <div>
              <img
                :src="getImageUrl(selectedImage, 'public')"
                :alt="selectedImage.filename"
                class="w-full rounded-lg"
              />
            </div>
            
            <!-- Image Info -->
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-500">Filename</h4>
                <p class="mt-1 text-sm text-gray-900">{{ selectedImage.filename }}</p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Cloudflare ID</h4>
                <p class="mt-1 text-sm text-gray-900 font-mono">{{ selectedImage.cloudflare_id }}</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Size</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ formatBytes(selectedImage.file_size) }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Dimensions</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ selectedImage.width }} × {{ selectedImage.height }}</p>
                </div>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Context</h4>
                <p class="mt-1 text-sm text-gray-900">{{ selectedImage.context || 'None' }}</p>
              </div>
              
              <div v-if="selectedImage.entity_type">
                <h4 class="text-sm font-medium text-gray-500">Associated With</h4>
                <p class="mt-1 text-sm text-gray-900">
                  {{ getEntityName(selectedImage.entity_type) }}
                  <span v-if="selectedImage.metadata?.region_name">({{ selectedImage.metadata.region_name }})</span>
                </p>
              </div>
              
              <div v-if="selectedImage.user">
                <h4 class="text-sm font-medium text-gray-500">Uploaded By</h4>
                <div class="mt-1 flex items-center">
                  <img 
                    v-if="selectedImage.user.avatar_url"
                    :src="selectedImage.user.avatar_url" 
                    :alt="selectedImage.user.name"
                    class="w-6 h-6 rounded-full mr-2"
                  />
                  <span class="text-sm text-gray-900">{{ selectedImage.user.name }}</span>
                </div>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Uploaded</h4>
                <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(selectedImage.uploaded_at) }}</p>
              </div>
              
              <div v-if="selectedImage.metadata && Object.keys(selectedImage.metadata).length > 0">
                <h4 class="text-sm font-medium text-gray-500">Metadata</h4>
                <pre class="mt-1 text-xs bg-gray-100 p-2 rounded overflow-auto">{{ JSON.stringify(selectedImage.metadata, null, 2) }}</pre>
              </div>
              
              <div class="pt-4 flex gap-2">
                <button
                  @click="deleteImage(selectedImage)"
                  class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                >
                  Delete Image
                </button>
                <a
                  :href="getImageUrl(selectedImage, 'public')"
                  target="_blank"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                  View Full Size
                </a>
              </div>
            </div>
          </div>
        </div>
      </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import axios from 'axios'
import { debounce } from 'lodash'

// State
const loading = ref(false)
const cleaning = ref(false)
const images = ref({ 
  data: [], 
  links: [],
  meta: {
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
    per_page: 20
  }
})
const stats = ref({})
const selectedImages = ref([]) // Stores image IDs
const selectedImage = ref(null)
const showDetailModal = ref(false)
const lastSelectedIndex = ref(null)
const refreshKey = ref(0)

// Filters
const filters = reactive({
  search: '',
  context: '',
  entity_type: '',
  date_range: '',
  status: ''
})

// Computed
const isAllSelected = computed(() => {
  return images.value.data?.length > 0 && selectedImages.value.length === images.value.data.length
})

const isPartiallySelected = computed(() => {
  return selectedImages.value.length > 0 && selectedImages.value.length < images.value.data?.length
})

// Pagination computed properties for easier access
const currentPage = computed(() => Number(images.value.meta?.current_page) || 1)
const lastPage = computed(() => Number(images.value.meta?.last_page) || 1)
const total = computed(() => Number(images.value.meta?.total) || 0)
const from = computed(() => Number(images.value.meta?.from) || 0)
const to = computed(() => Number(images.value.meta?.to) || 0)
const perPage = computed(() => Number(images.value.meta?.per_page) || 20)

// Methods
const fetchMedia = async (page = 1) => {
  loading.value = true
  
  // Clear selection when changing pages
  if (page !== currentPage.value) {
    selectedImages.value = []
  }
  
  try {
    const params = {
      page: parseInt(page) || 1,
      per_page: 20,
      ...filters
    }
    
    console.log('Fetching media with params:', params)
    
    const response = await axios.get('/api/admin/media', { params })
    console.log('Media API response:', response.data)
    
    // API returns standard Laravel paginated response
    images.value = response.data
    
    // If no data property, check if response is the data directly
    if (!images.value.data && Array.isArray(response.data)) {
      console.warn('API returned array instead of paginated response')
      images.value = {
        data: response.data,
        links: [],
        meta: {
          current_page: page,
          last_page: 1,
          total: response.data.length,
          from: 1,
          to: response.data.length,
          per_page: 20
        }
      }
    }
    
    console.log('Images after processing:', images.value)
    console.log('Data length:', images.value.data?.length)
    console.log('Current page:', currentPage.value, 'Total:', total.value)
    
    // Force re-render
    refreshKey.value++
  } catch (error) {
    console.error('Error fetching media:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/admin/media/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedImages.value = images.value.data.map(img => img.id)
  } else {
    selectedImages.value = []
  }
  lastSelectedIndex.value = null
}

const handleCheckboxClick = (event, imageId) => {
  event.stopPropagation()
  
  const currentIndex = images.value.data.findIndex(img => img.id === imageId)
  
  // Handle shift-click for range selection
  if (event.shiftKey && lastSelectedIndex.value !== null) {
    event.preventDefault() // Prevent default checkbox behavior
    
    const start = Math.min(lastSelectedIndex.value, currentIndex)
    const end = Math.max(lastSelectedIndex.value, currentIndex)
    
    // Get all IDs in the range
    const rangeIds = images.value.data
      .slice(start, end + 1)
      .map(img => img.id)
    
    // Determine if we're selecting or deselecting based on the last selected item's state
    const lastSelectedId = images.value.data[lastSelectedIndex.value]?.id
    const isLastSelected = selectedImages.value.includes(lastSelectedId)
    
    if (isLastSelected) {
      // Add all range IDs to selection (avoiding duplicates)
      const newSelection = new Set(selectedImages.value)
      rangeIds.forEach(id => newSelection.add(id))
      selectedImages.value = Array.from(newSelection)
    } else {
      // Remove all range IDs from selection
      selectedImages.value = selectedImages.value.filter(id => !rangeIds.includes(id))
    }
  } else {
    // Normal click - toggle selection
    if (selectedImages.value.includes(imageId)) {
      selectedImages.value = selectedImages.value.filter(id => id !== imageId)
    } else {
      selectedImages.value = [...selectedImages.value, imageId]
    }
    
    // Update last selected index
    lastSelectedIndex.value = currentIndex
  }
}

const viewImageDetails = (image) => {
  selectedImage.value = image
  showDetailModal.value = true
}

const clearSelection = () => {
  selectedImages.value = []
}

const deleteSelected = async () => {
  const count = selectedImages.value.length
  if (!confirm(`Are you sure you want to delete ${count} selected images? This action cannot be undone.`)) return
  
  try {
    // Show loading state
    loading.value = true
    
    console.log('Selected images for deletion:', selectedImages.value)
    console.log('Type of first ID:', typeof selectedImages.value[0])
    console.log('Sample IDs:', selectedImages.value.slice(0, 3))
    
    const response = await axios.post('/api/admin/media/bulk-delete', {
      ids: selectedImages.value
    })
    
    console.log('Bulk delete response:', response.data)
    
    // Clear selection and refresh current page
    selectedImages.value = []
    await fetchMedia(currentPage.value)
    await fetchStats()
    
    // Show success message based on actual deletion count
    if (response.data.deleted_count > 0) {
      alert(`Successfully deleted ${response.data.deleted_count} images`)
    } else {
      alert('No images were deleted. They may have already been removed.')
    }
    
    // Show errors if any
    if (response.data.errors && response.data.errors.length > 0) {
      console.error('Deletion errors:', response.data.errors)
      alert(`Some images could not be deleted: ${response.data.errors.map(e => e.filename).join(', ')}`)
    }
  } catch (error) {
    console.error('Error deleting images:', error)
    console.error('Error response:', error.response?.data)
    
    let errorMessage = 'Error deleting images: '
    if (error.response?.data?.errors && error.response.data.errors.length > 0) {
      errorMessage += error.response.data.errors.map(e => `${e.id}: ${e.error}`).join(', ')
    } else {
      errorMessage += error.response?.data?.message || 'Unknown error'
    }
    
    alert(errorMessage)
  } finally {
    loading.value = false
  }
}

const deleteImage = async (image) => {
  if (!confirm(`Delete image "${image.filename}"? This action cannot be undone.`)) return
  
  try {
    // Use the image.id for deletion (backend expects the database ID)
    await axios.delete(`/api/admin/media/${image.id}`)
    
    // If modal is open, close it
    if (showDetailModal.value) {
      closeDetailModal()
    }
    
    // Remove from selected items if it was selected
    selectedImages.value = selectedImages.value.filter(id => id !== image.id)
    
    // Refresh the list on current page
    await fetchMedia(currentPage.value)
    await fetchStats()
  } catch (error) {
    console.error('Error deleting image:', error)
    alert('Error deleting image: ' + (error.response?.data?.message || 'Unknown error'))
  }
}

const performCleanup = async () => {
  if (!confirm('Remove all orphaned images (not associated with any records)?')) return
  
  cleaning.value = true
  try {
    const response = await axios.post('/api/admin/media/cleanup')
    alert(`Cleanup complete. ${response.data.deleted_count} images removed.`)
    fetchMedia()
    fetchStats()
  } catch (error) {
    alert('Error during cleanup: ' + (error.response?.data?.message || 'Unknown error'))
  } finally {
    cleaning.value = false
  }
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedImage.value = null
}

const getImageUrl = (image, variant = 'public') => {
  // Get base URL
  let baseUrl = ''
  
  // If we have variants array from Cloudflare, use the first one
  if (image.variants && image.variants.length > 0) {
    baseUrl = image.variants[0]
  } else if (image.cloudflare_id) {
    // Fallback to constructing URL from ID
    baseUrl = `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${image.cloudflare_id}/public`
  } else {
    return ''
  }
  
  // For thumbnails, use the thumbnail variant if it exists
  if (variant === 'thumbnail' && image.cloudflare_id) {
    return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${image.cloudflare_id}/thumbnail`
  }
  
  return baseUrl
}

const getEntityName = (entityType) => {
  const parts = entityType.split('\\')
  return parts[parts.length - 1]
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString()
}

// Debounced search
const debouncedSearch = debounce(() => {
  fetchMedia()
}, 300)


// Initialize
onMounted(() => {
  fetchMedia()
  fetchStats()
})
</script>