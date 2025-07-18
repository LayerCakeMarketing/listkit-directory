<template>
  <div class="py-12">
    <div class="mx-auto sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Tags Management</h2>
            <p class="text-sm text-gray-600 mt-1">Manage tags across places, lists, and posts</p>
          </div>
          <button
            @click="showCreateModal = true"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create Tag
          </button>
        </div>

        <!-- Stats -->
        <div v-if="stats" class="grid grid-cols-1 md:grid-cols-6 gap-4 mt-6">
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
            <div class="text-sm text-blue-600 font-medium">Total Tags</div>
            <div class="text-2xl font-bold text-blue-900">{{ stats.total || 0 }}</div>
          </div>
          <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
            <div class="text-sm text-green-600 font-medium">Featured</div>
            <div class="text-2xl font-bold text-green-900">{{ stats.featured || 0 }}</div>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
            <div class="text-sm text-purple-600 font-medium">System</div>
            <div class="text-2xl font-bold text-purple-900">{{ stats.system || 0 }}</div>
          </div>
          <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-4 rounded-lg">
            <div class="text-sm text-amber-600 font-medium">General</div>
            <div class="text-2xl font-bold text-amber-900">{{ stats.by_type?.general || 0 }}</div>
          </div>
          <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-4 rounded-lg">
            <div class="text-sm text-teal-600 font-medium">Category</div>
            <div class="text-2xl font-bold text-teal-900">{{ stats.by_type?.category || 0 }}</div>
          </div>
          <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
            <div class="text-sm text-indigo-600 font-medium">Location</div>
            <div class="text-2xl font-bold text-indigo-900">{{ stats.by_type?.location || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Search tags..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select
              v-model="filters.type"
              @change="fetchTags"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All Types</option>
              <option value="general">General</option>
              <option value="category">Category</option>
              <option value="location">Location</option>
              <option value="event">Event</option>
              <option value="trending">Trending</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="filters.status"
              @change="fetchTags"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All</option>
              <option value="featured">Featured</option>
              <option value="system">System</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select
              v-model="filters.sort_by"
              @change="fetchTags"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="name">Name</option>
              <option value="usage">Usage Count</option>
              <option value="created_at">Created Date</option>
            </select>
          </div>
        </div>

        <!-- Most Used Tags -->
        <div v-if="stats?.most_used?.length > 0" class="mt-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">Most Used Tags</h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in stats.most_used"
              :key="tag.id"
              class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm"
              :style="{ 
                backgroundColor: tag.color ? tag.color + '20' : '#E5E7EB',
                color: tag.color || '#374151'
              }"
            >
              {{ tag.name }}
              <span class="text-xs opacity-70">({{ tag.usage_count }})</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Tags Table -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <!-- Bulk Actions -->
          <div v-if="selectedTags.length > 0" class="mb-4 p-4 bg-gray-50 rounded-lg flex justify-between items-center">
            <span class="text-sm text-gray-700">
              {{ selectedTags.length }} tag(s) selected
            </span>
            <div class="space-x-2">
              <button
                @click="bulkDelete"
                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors"
              >
                Delete Selected
              </button>
              <button
                @click="bulkToggleFeatured"
                class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors"
              >
                Toggle Featured
              </button>
              <button
                @click="showMergeModal = true"
                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
              >
                Merge Tags
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>

          <!-- Table -->
          <div v-else-if="tags.data && tags.data.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left">
                    <input
                      type="checkbox"
                      @change="toggleSelectAll"
                      :checked="selectedTags.length === tags.data.length"
                      class="rounded border-gray-300"
                    />
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tag
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Usage
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Created By
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <input
                      type="checkbox"
                      :value="tag.id"
                      v-model="selectedTags"
                      :disabled="tag.is_system"
                      class="rounded border-gray-300"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: tag.color || '#6B7280' }"
                      ></div>
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ tag.name }}</div>
                        <div v-if="tag.description" class="text-xs text-gray-500">{{ tag.description }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="capitalize text-sm text-gray-900">{{ tag.type }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm">
                      <div class="font-medium text-gray-900">{{ tag.usage_count }} total</div>
                      <div class="text-xs text-gray-500">
                        <span v-if="tag.places_count">Places: {{ tag.places_count }}</span>
                        <span v-if="tag.lists_count" class="ml-2">Lists: {{ tag.lists_count }}</span>
                        <span v-if="tag.posts_count" class="ml-2">Posts: {{ tag.posts_count }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex gap-1">
                      <span
                        v-if="tag.is_featured"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800"
                      >
                        Featured
                      </span>
                      <span
                        v-if="tag.is_system"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"
                      >
                        System
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ tag.creator?.name || 'System' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      @click="editTag(tag)"
                      class="text-blue-600 hover:text-blue-900 mr-3"
                      :disabled="tag.is_system"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteTag(tag)"
                      class="text-red-600 hover:text-red-900"
                      :disabled="tag.is_system || tag.usage_count > 0"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tags found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new tag</p>
          </div>

          <!-- Pagination -->
          <div v-if="tags.last_page > 1" class="mt-6">
            <Pagination 
              :current-page="tags.current_page"
              :last-page="tags.last_page"
              :total="tags.total"
              :from="tags.from"
              :to="tags.to"
              :per-page="tags.per_page"
              @change-page="fetchTags"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || showEditModal" @close="closeModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ showEditModal ? 'Edit Tag' : 'Create New Tag' }}
        </h3>

        <form @submit.prevent="saveTag" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :class="{ 'border-red-300': errors.name }"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select
              v-model="form.type"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="general">General</option>
              <option value="category">Category</option>
              <option value="location">Location</option>
              <option value="event">Event</option>
              <option value="trending">Trending</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <div class="flex items-center gap-2">
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-20 rounded border-gray-300"
              />
              <input
                v-model="form.color"
                type="text"
                placeholder="#000000"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="flex items-center gap-4">
            <label class="flex items-center">
              <input
                v-model="form.is_featured"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Featured</span>
            </label>
            <label v-if="authStore.user?.role === 'admin'" class="flex items-center">
              <input
                v-model="form.is_system"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">System Tag</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="processing"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ processing ? 'Saving...' : 'Save Tag' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Merge Modal -->
    <Modal :show="showMergeModal" @close="showMergeModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Merge Tags</h3>
        <p class="text-sm text-gray-600 mb-4">
          Select the tag to merge the selected tags into. All relationships will be transferred.
        </p>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Merge Into</label>
          <select
            v-model="mergeTargetId"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Select a tag...</option>
            <option
              v-for="tag in availableMergeTargets"
              :key="tag.id"
              :value="tag.id"
            >
              {{ tag.name }} ({{ tag.usage_count }} uses)
            </option>
          </select>
        </div>

        <div class="flex justify-end gap-3">
          <button
            @click="showMergeModal = false"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="mergeTags"
            :disabled="!mergeTargetId || processing"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            Merge Tags
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import { useAuthStore } from '@/stores/auth'

// Auth store
const authStore = useAuthStore()

// State
const loading = ref(false)
const processing = ref(false)
const tags = ref({ 
  data: [], 
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
  per_page: 50
})
const stats = ref(null)
const selectedTags = ref([])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showMergeModal = ref(false)
const editingTag = ref(null)
const mergeTargetId = ref('')
const errors = ref({})

// Filters
const filters = reactive({
  search: '',
  type: '',
  status: '',
  sort_by: 'name',
  sort_order: 'asc'
})

// Form
const form = reactive({
  name: '',
  type: 'general',
  color: '#6B7280',
  description: '',
  is_featured: false,
  is_system: false
})

// Computed
const availableMergeTargets = computed(() => {
  return tags.value.data.filter(tag => !selectedTags.value.includes(tag.id))
})

// Methods
const fetchTags = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      ...filters,
      featured: filters.status === 'featured' ? true : undefined,
      system: filters.status === 'system' ? true : undefined
    }
    
    const response = await axios.get('/api/admin/tags', { params })
    tags.value = response.data
  } catch (error) {
    console.error('Error fetching tags:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/admin/tags/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const debouncedSearch = debounce(() => {
  fetchTags()
}, 300)

const saveTag = async () => {
  processing.value = true
  errors.value = {}
  
  try {
    if (editingTag.value) {
      await axios.put(`/api/admin/tags/${editingTag.value.id}`, form)
    } else {
      await axios.post('/api/admin/tags', form)
    }
    
    closeModal()
    fetchTags()
    fetchStats()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    console.error('Error saving tag:', error)
  } finally {
    processing.value = false
  }
}

const editTag = (tag) => {
  editingTag.value = tag
  Object.assign(form, {
    name: tag.name,
    type: tag.type,
    color: tag.color || '#6B7280',
    description: tag.description || '',
    is_featured: tag.is_featured,
    is_system: tag.is_system
  })
  showEditModal.value = true
}

const deleteTag = async (tag) => {
  if (!confirm(`Are you sure you want to delete "${tag.name}"?`)) {
    return
  }
  
  try {
    await axios.delete(`/api/admin/tags/${tag.id}`)
    fetchTags()
    fetchStats()
  } catch (error) {
    alert(error.response?.data?.message || 'Error deleting tag')
  }
}

const bulkDelete = async () => {
  if (!confirm(`Delete ${selectedTags.value.length} selected tags?`)) {
    return
  }
  
  processing.value = true
  try {
    await axios.post('/api/admin/tags/bulk-update', {
      tag_ids: selectedTags.value,
      action: 'delete'
    })
    
    selectedTags.value = []
    fetchTags()
    fetchStats()
  } catch (error) {
    alert(error.response?.data?.message || 'Error deleting tags')
  } finally {
    processing.value = false
  }
}

const bulkToggleFeatured = async () => {
  processing.value = true
  try {
    await axios.post('/api/admin/tags/bulk-update', {
      tag_ids: selectedTags.value,
      action: 'toggle_featured'
    })
    
    selectedTags.value = []
    fetchTags()
    fetchStats()
  } catch (error) {
    console.error('Error updating tags:', error)
  } finally {
    processing.value = false
  }
}

const mergeTags = async () => {
  if (!mergeTargetId.value) return
  
  processing.value = true
  try {
    await axios.post('/api/admin/tags/bulk-update', {
      tag_ids: selectedTags.value,
      action: 'merge',
      target_tag_id: mergeTargetId.value
    })
    
    selectedTags.value = []
    showMergeModal.value = false
    mergeTargetId.value = ''
    fetchTags()
    fetchStats()
  } catch (error) {
    alert(error.response?.data?.message || 'Error merging tags')
  } finally {
    processing.value = false
  }
}

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedTags.value = tags.value.data
      .filter(tag => !tag.is_system)
      .map(tag => tag.id)
  } else {
    selectedTags.value = []
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingTag.value = null
  errors.value = {}
  
  // Reset form
  Object.assign(form, {
    name: '',
    type: 'general',
    color: '#6B7280',
    description: '',
    is_featured: false,
    is_system: false
  })
}

// Load data on mount
onMounted(() => {
  fetchTags()
  fetchStats()
})
</script>