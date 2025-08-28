<template>
  <div class="admin-media">
    <div class="header">
      <h1 class="title">Media Management</h1>
      <div class="header-actions">
        <button @click="fetchStatistics" class="stats-button">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Statistics
        </button>
        <button @click="showUploader = !showUploader" class="upload-button">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Upload
        </button>
      </div>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStats" class="stats-modal" @click.self="showStats = false">
      <div class="stats-content">
        <h2 class="stats-title">Media Statistics</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">{{ statistics.total_media || 0 }}</div>
            <div class="stat-label">Total Files</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ formatFileSize(statistics.total_size || 0) }}</div>
            <div class="stat-label">Total Size</div>
          </div>
        </div>
        <div v-if="statistics.by_context" class="stat-section">
          <h3 class="stat-section-title">By Context</h3>
          <div class="stat-bars">
            <div v-for="(count, context) in statistics.by_context" :key="context" class="stat-bar">
              <span class="stat-bar-label">{{ context || 'none' }}</span>
              <span class="stat-bar-value">{{ count }}</span>
            </div>
          </div>
        </div>
        <button @click="showStats = false" class="close-button">Close</button>
      </div>
    </div>

    <!-- Upload Section -->
    <div v-if="showUploader" class="upload-section">
      <CloudflareUploader
        :multiple="true"
        :max-files="10"
        @upload-success="handleUploadSuccess"
        @all-complete="handleAllUploadsComplete"
      />
    </div>

    <!-- Filters -->
    <div class="filters">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by filename..."
        class="search-input"
        @input="debouncedFetch"
      >
      <select v-model="filters.context" @change="fetchMedia" class="filter-select">
        <option value="">All Contexts</option>
        <option value="avatar">Avatar</option>
        <option value="cover">Cover</option>
        <option value="gallery">Gallery</option>
        <option value="logo">Logo</option>
        <option value="post">Post</option>
        <option value="place_image">Place Image</option>
        <option value="list_cover">List Cover</option>
      </select>
      <select v-model="filters.status" @change="fetchMedia" class="filter-select">
        <option value="">All Status</option>
        <option value="approved">Approved</option>
        <option value="pending">Pending</option>
        <option value="rejected">Rejected</option>
      </select>
      <button @click="syncWithCloudflare" class="sync-button">
        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Sync Cloudflare
      </button>
      <button v-if="selectedItems.length > 0" @click="bulkDelete" class="bulk-delete-button">
        Delete Selected ({{ selectedItems.length }})
      </button>
    </div>

    <!-- Media Table -->
    <div class="table-container">
      <table class="media-table">
        <thead>
          <tr>
            <th>
              <input
                type="checkbox"
                @change="toggleSelectAll"
                :checked="selectedItems.length === mediaItems.length && mediaItems.length > 0"
              >
            </th>
            <th>Preview</th>
            <th>Filename</th>
            <th>Context</th>
            <th>Entity</th>
            <th>User</th>
            <th>Size</th>
            <th>Dimensions</th>
            <th>Status</th>
            <th>Uploaded</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in mediaItems" :key="item.id">
            <td>
              <input
                type="checkbox"
                :value="item.id"
                v-model="selectedItems"
              >
            </td>
            <td>
              <img
                v-if="item.is_image"
                :src="item.thumbnail_url"
                :alt="item.filename"
                class="preview-image"
                @click="showLightbox(item)"
              >
              <div v-else class="no-preview">No Preview</div>
            </td>
            <td class="filename-cell">
              <a :href="item.url" target="_blank" class="filename-link">
                {{ item.filename }}
              </a>
            </td>
            <td>
              <span class="context-badge" :class="`context-${item.context}`">
                {{ item.context || 'none' }}
              </span>
            </td>
            <td>
              <span v-if="item.entity_type" class="entity-info">
                {{ item.entity_name }}
                <span class="entity-id">#{{ item.entity_id }}</span>
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td>
              <span v-if="item.user" class="user-info">
                {{ item.user.firstname }} {{ item.user.lastname }}
              </span>
            </td>
            <td class="text-right">{{ item.formatted_file_size }}</td>
            <td class="text-center">
              <span v-if="item.width && item.height" class="dimensions">
                {{ item.width }}×{{ item.height }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td>
              <span class="status-badge" :class="`status-${item.status}`">
                {{ item.status }}
              </span>
            </td>
            <td class="date-cell">
              {{ formatDate(item.created_at) }}
            </td>
            <td class="actions-cell">
              <button @click="viewDetails(item)" class="action-button view">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
              <button @click="deleteMedia(item)" class="action-button delete">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading media...</p>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && mediaItems.length === 0" class="empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p>No media files found</p>
        <button @click="syncWithCloudflare" class="sync-cloudflare-btn">
          Sync with Cloudflare to check for images
        </button>
      </div>
    </div>

    <!-- Untracked Images Section -->
    <div v-if="untrackedImages.length > 0" class="untracked-section">
      <h2 class="untracked-title">Untracked Cloudflare Images ({{ untrackedImages.length }})</h2>
      <div class="untracked-grid">
        <div v-for="image in untrackedImages" :key="image.cloudflare_id" class="untracked-card">
          <img :src="image.thumbnail_url" :alt="image.filename" class="untracked-image">
          <div class="untracked-info">
            <p class="untracked-filename">{{ image.filename }}</p>
            <p class="untracked-date">{{ formatDate(image.uploaded) }}</p>
            <button @click="importImage(image)" class="import-btn">Import</button>
          </div>
        </div>
      </div>
      <button v-if="untrackedImages.length > 5" @click="importAllUntracked" class="import-all-btn">
        Import All Untracked Images
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="pagination">
      <button
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="page-button"
      >
        Previous
      </button>
      <span class="page-info">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </span>
      <button
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="page-button"
      >
        Next
      </button>
    </div>

    <!-- Lightbox -->
    <div v-if="lightboxImage" class="lightbox" @click="lightboxImage = null">
      <img :src="lightboxImage.url" :alt="lightboxImage.filename">
      <button @click="lightboxImage = null" class="lightbox-close">
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import CloudflareUploader from '@/components/CloudflareUploader.vue'

// State
const mediaItems = ref([])
const untrackedImages = ref([])
const selectedItems = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})
const filters = ref({
  search: '',
  context: '',
  status: '',
  sort_by: 'created_at',
  sort_order: 'desc',
  show_untracked: false
})
const loading = ref(false)
const showUploader = ref(false)
const showStats = ref(false)
const statistics = ref({})
const lightboxImage = ref(null)
const syncing = ref(false)

// Debounce timer
let searchTimer = null

// Methods
const fetchMedia = async (page = 1) => {
  loading.value = true
  
  // Remove show_untracked from the params as it's not used by the regular media endpoint
  const { show_untracked, ...apiFilters } = filters.value
  
  console.log('=== FETCH MEDIA START ===')
  console.log('Page:', page)
  console.log('Filters:', apiFilters)
  console.log('Auth check - getting user...')
  
  try {
    // First verify we're authenticated
    const authResponse = await axios.get('/api/user')
    console.log('Authenticated user:', authResponse.data)
    
    // Filter out empty values from params
    const params = {
      page,
      per_page: 20
    }
    
    // Only add filters if they have values
    Object.keys(apiFilters).forEach(key => {
      if (apiFilters[key] !== '' && apiFilters[key] !== null && apiFilters[key] !== undefined) {
        params[key] = apiFilters[key]
      }
    })
    
    const response = await axios.get('/api/media', { params })
    
    console.log('=== MEDIA API RESPONSE ===')
    console.log('Full response:', response)
    console.log('Response status:', response.status)
    console.log('Response headers:', response.headers)
    console.log('Response data type:', typeof response.data)
    console.log('Response data:', response.data)
    
    if (response.data) {
      console.log('Has data property?', 'data' in response.data)
      console.log('Data.data:', response.data.data)
      console.log('Data.total:', response.data.total)
      console.log('Data.current_page:', response.data.current_page)
      console.log('Data.last_page:', response.data.last_page)
    }
    
    // Use standard Laravel pagination structure
    mediaItems.value = response.data.data || []
    pagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      per_page: response.data.per_page || 20,
      total: response.data.total || 0
    }
    
    console.log('=== AFTER ASSIGNMENT ===')
    console.log('mediaItems.value:', mediaItems.value)
    console.log('mediaItems.value length:', mediaItems.value.length)
    console.log('pagination.value:', pagination.value)
    
    if (mediaItems.value.length > 0) {
      console.log('First item full object:', JSON.stringify(mediaItems.value[0], null, 2))
    } else {
      console.log('No items in mediaItems.value array')
    }
    
    console.log('=== FETCH MEDIA END ===')
  } catch (error) {
    console.error('=== FETCH MEDIA ERROR ===')
    console.error('Full error object:', error)
    if (error.response) {
      console.error('Error response status:', error.response.status)
      console.error('Error response data:', error.response.data)
      console.error('Error response headers:', error.response.headers)
    }
    console.error('=== END ERROR ===')
  } finally {
    loading.value = false
  }
}

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/api/media/statistics')
    statistics.value = response.data.statistics
    showStats.value = true
  } catch (error) {
    console.error('Failed to fetch statistics:', error)
  }
}

const debouncedFetch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    fetchMedia()
  }, 500)
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchMedia(page)
  }
}

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedItems.value = mediaItems.value.map(item => item.id)
  } else {
    selectedItems.value = []
  }
}

const viewDetails = (item) => {
  // Could open a modal with full details
  console.log('View details:', item)
}

const showLightbox = (item) => {
  lightboxImage.value = item
}

const deleteMedia = async (item) => {
  if (!confirm(`Delete ${item.filename}?`)) return
  
  try {
    await axios.delete(`/api/media/${item.id}`)
    fetchMedia(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete media:', error)
    alert('Failed to delete media item')
  }
}

const syncWithCloudflare = async () => {
  // For now, just refresh the media list
  // The sync functionality can be added later
  await fetchMedia()
  alert('Media list refreshed')
}

const importImage = async (image) => {
  try {
    const response = await axios.post('/api/admin/media/import-cloudflare', {
      cloudflare_id: image.cloudflare_id,
      filename: image.filename,
      context: 'imported'
    })
    
    if (response.data.success) {
      // Remove from untracked list
      untrackedImages.value = untrackedImages.value.filter(
        img => img.cloudflare_id !== image.cloudflare_id
      )
      // Refresh tracked media
      await fetchMedia()
      alert('Image imported successfully')
    }
  } catch (error) {
    console.error('Failed to import image:', error)
    alert('Failed to import image')
  }
}

const importAllUntracked = async () => {
  if (!confirm(`Import all ${untrackedImages.value.length} untracked images?`)) return
  
  try {
    const cloudflareIds = untrackedImages.value.map(img => img.cloudflare_id)
    const response = await axios.post('/api/admin/media/bulk-import', {
      cloudflare_ids: cloudflareIds
    })
    
    if (response.data.success) {
      alert(`Successfully imported ${response.data.imported} images. Failed: ${response.data.failed}`)
      untrackedImages.value = []
      await fetchMedia()
    }
  } catch (error) {
    console.error('Failed to bulk import:', error)
    alert('Failed to bulk import images')
  }
}

const bulkDelete = async () => {
  if (!confirm(`Delete ${selectedItems.value.length} selected items?`)) return
  
  try {
    await axios.post('/api/media/bulk-delete', {
      ids: selectedItems.value
    })
    selectedItems.value = []
    fetchMedia(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to bulk delete:', error)
    alert('Failed to delete selected items')
  }
}

const handleUploadSuccess = (data) => {
  console.log('Upload success:', data)
}

const handleAllUploadsComplete = () => {
  showUploader.value = false
  fetchMedia()
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}


// Check authentication
const checkAuth = async () => {
  try {
    const response = await axios.get('/api/user')
    console.log('User authenticated:', response.data)
    return true
  } catch (error) {
    console.error('User not authenticated')
    alert('You must be logged in to access the media management page. Please log in first.')
    window.location.href = '/login'
    return false
  }
}

// Lifecycle
onMounted(async () => {
  console.log('Media component mounted')
  const isAuthenticated = await checkAuth()
  if (isAuthenticated) {
    console.log('User is authenticated, fetching media')
    fetchMedia()
  }
})
</script>

<style scoped>
.admin-media {
  @apply p-6 max-w-7xl mx-auto;
}

.header {
  @apply flex justify-between items-center mb-6;
}

.title {
  @apply text-2xl font-bold text-gray-900;
}

.header-actions {
  @apply flex gap-3;
}

.stats-button, .upload-button {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors;
}

.icon {
  @apply h-5 w-5;
}

.upload-section {
  @apply mb-6 p-4 bg-gray-50 rounded-lg;
}

.filters {
  @apply flex gap-3 mb-6;
}

.search-input {
  @apply flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.filter-select {
  @apply px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.bulk-delete-button {
  @apply px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors;
}

.table-container {
  @apply bg-white rounded-lg shadow overflow-hidden;
}

.media-table {
  @apply w-full;
}

.media-table thead {
  @apply bg-gray-50;
}

.media-table th {
  @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
}

.media-table td {
  @apply px-4 py-3 text-sm text-gray-900;
}

.media-table tbody tr {
  @apply border-t border-gray-200 hover:bg-gray-50;
}

.preview-image {
  @apply h-12 w-12 object-cover rounded cursor-pointer hover:opacity-75;
}

.no-preview {
  @apply h-12 w-12 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500;
}

.filename-link {
  @apply text-blue-600 hover:text-blue-800 hover:underline;
}

.context-badge {
  @apply px-2 py-1 text-xs rounded-full font-medium;
}

.context-avatar { @apply bg-purple-100 text-purple-800; }
.context-cover { @apply bg-blue-100 text-blue-800; }
.context-gallery { @apply bg-green-100 text-green-800; }
.context-logo { @apply bg-yellow-100 text-yellow-800; }
.context-post { @apply bg-pink-100 text-pink-800; }

.status-badge {
  @apply px-2 py-1 text-xs rounded-full font-medium;
}

.status-approved { @apply bg-green-100 text-green-800; }
.status-pending { @apply bg-yellow-100 text-yellow-800; }
.status-rejected { @apply bg-red-100 text-red-800; }

.actions-cell {
  @apply flex gap-2;
}

.action-button {
  @apply p-1 rounded hover:bg-gray-100 transition-colors;
}

.action-button svg {
  @apply h-4 w-4;
}

.action-button.view {
  @apply text-blue-600 hover:text-blue-800;
}

.action-button.delete {
  @apply text-red-600 hover:text-red-800;
}

.loading-state, .empty-state {
  @apply py-12 text-center;
}

.spinner {
  @apply mx-auto h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin;
}

.empty-icon {
  @apply mx-auto h-16 w-16 text-gray-400;
}

.pagination {
  @apply mt-6 flex justify-center items-center gap-4;
}

.page-button {
  @apply px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.page-info {
  @apply text-sm text-gray-600;
}

.lightbox {
  @apply fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center p-4;
}

.lightbox img {
  @apply max-w-full max-h-full object-contain;
}

.lightbox-close {
  @apply absolute top-4 right-4 p-2 bg-white bg-opacity-20 rounded-full text-white hover:bg-opacity-30;
}

.lightbox-close svg {
  @apply h-6 w-6;
}

.stats-modal {
  @apply fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4;
}

.stats-content {
  @apply bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto;
}

.stats-title {
  @apply text-xl font-bold mb-4;
}

.stats-grid {
  @apply grid grid-cols-2 gap-4 mb-6;
}

.stat-card {
  @apply bg-gray-50 rounded-lg p-4 text-center;
}

.stat-value {
  @apply text-2xl font-bold text-gray-900;
}

.stat-label {
  @apply text-sm text-gray-600 mt-1;
}

.stat-section {
  @apply mb-6;
}

.stat-section-title {
  @apply text-lg font-semibold mb-3;
}

.stat-bars {
  @apply space-y-2;
}

.stat-bar {
  @apply flex justify-between items-center p-2 bg-gray-50 rounded;
}

.stat-bar-label {
  @apply text-sm font-medium text-gray-700;
}

.stat-bar-value {
  @apply text-sm font-bold text-gray-900;
}

.close-button {
  @apply w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors;
}

.sync-button {
  @apply flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors;
}

.sync-cloudflare-btn {
  @apply mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors;
}

.untracked-section {
  @apply mt-8 p-6 bg-yellow-50 rounded-lg border border-yellow-200;
}

.untracked-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.untracked-grid {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4;
}

.untracked-card {
  @apply bg-white rounded-lg shadow-sm overflow-hidden;
}

.untracked-image {
  @apply w-full h-32 object-cover;
}

.untracked-info {
  @apply p-3;
}

.untracked-filename {
  @apply text-sm font-medium text-gray-900 truncate;
}

.untracked-date {
  @apply text-xs text-gray-500 mt-1;
}

.import-btn {
  @apply w-full mt-2 px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors;
}

.import-all-btn {
  @apply mt-4 px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors;
}
</style>