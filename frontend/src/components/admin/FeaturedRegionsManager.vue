<template>
  <div class="featured-regions-manager">
    <!-- Header -->
    <div class="mb-4 flex justify-between items-center">
      <h4 class="text-sm font-medium text-gray-900">Featured Regions</h4>
      <button
        @click="showAddModal = true"
        class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-200"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Add Featured Region
      </button>
    </div>

    <!-- Featured Regions List -->
    <div v-if="featuredRegions.length > 0" class="space-y-3">
      <draggable
        v-model="featuredRegions"
        @end="updateOrder"
        item-key="id"
        handle=".drag-handle"
      >
        <template #item="{ element: region }">
          <div class="flex items-center justify-between p-4 bg-white border rounded-lg hover:shadow-md transition-shadow">
            <!-- Drag Handle -->
            <div class="drag-handle cursor-move mr-3">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </div>

            <!-- Region Info -->
            <div class="flex-1 flex items-center space-x-4">
              <img
                v-if="region.cover_image_url"
                :src="region.cover_image_url"
                :alt="region.name"
                class="w-16 h-16 rounded-lg object-cover"
              />
              <div v-else class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              
              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <h5 class="font-medium text-gray-900">{{ region.name }}</h5>
                  <span v-if="region.pivot?.label" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded-full">
                    {{ region.pivot.label }}
                  </span>
                  <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full capitalize">
                    {{ region.type }}
                  </span>
                </div>
                <p v-if="region.pivot?.description" class="text-sm text-gray-600 mt-1">
                  {{ region.pivot.description }}
                </p>
                <div class="flex items-center space-x-4 text-xs text-gray-500 mt-1">
                  <span>{{ region.cached_place_count || 0 }} places</span>
                  <span v-if="region.parent">in {{ region.parent.name }}</span>
                  <span v-if="!region.pivot?.is_active" class="text-orange-600">Inactive</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <button
                @click="editFeaturedRegion(region)"
                class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                title="Edit"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="toggleActive(region)"
                :class="region.pivot?.is_active ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600'"
                class="p-2 hover:bg-gray-50 rounded-md transition-colors"
                :title="region.pivot?.is_active ? 'Active' : 'Inactive'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button
                @click="removeFeaturedRegion(region)"
                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors"
                title="Remove"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </template>
      </draggable>
    </div>
    <div v-else class="text-center py-12 bg-gray-50 rounded-lg text-gray-500">
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p>No featured regions yet</p>
      <p class="text-sm mt-1">Add regions to highlight within this area</p>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || editingRegion" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div @click="closeModal" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        <!-- Modal panel -->
        <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
          <div class="px-6 py-4 bg-gray-50 border-b">
            <h3 class="text-lg font-medium text-gray-900">
              {{ editingRegion ? 'Edit Featured Region' : 'Add Featured Region' }}
            </h3>
          </div>

          <div class="px-6 py-4">
            <!-- Region Search (for adding new) -->
            <div v-if="!editingRegion" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Search Region</label>
              <div class="relative">
                <input
                  v-model="searchQuery"
                  @input="searchRegions"
                  type="text"
                  placeholder="Type to search regions..."
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                
                <!-- Search Results Dropdown -->
                <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg max-h-60 overflow-auto">
                  <div
                    v-for="result in searchResults"
                    :key="result.id"
                    @click="selectRegion(result)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
                  >
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="font-medium text-gray-900">{{ result.name }}</p>
                        <p class="text-sm text-gray-500">
                          {{ result.type }} 
                          <span v-if="result.parent_name">• {{ result.parent_name }}</span>
                          <span v-if="result.place_count"> • {{ result.place_count }} places</span>
                        </p>
                      </div>
                      <span v-if="!result.has_coordinates" class="text-xs text-orange-600">No coordinates</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Selected Region Info -->
            <div v-if="selectedRegion || editingRegion" class="mb-4 p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center space-x-3">
                <img
                  v-if="(selectedRegion || editingRegion).cover_image_url"
                  :src="(selectedRegion || editingRegion).cover_image_url"
                  :alt="(selectedRegion || editingRegion).name"
                  class="w-12 h-12 rounded object-cover"
                />
                <div>
                  <p class="font-medium">{{ (selectedRegion || editingRegion).name }}</p>
                  <p class="text-sm text-gray-500">{{ (selectedRegion || editingRegion).type }}</p>
                </div>
              </div>
            </div>

            <!-- Form Fields -->
            <div v-if="selectedRegion || editingRegion" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Label (Optional)</label>
                <input
                  v-model="formData.label"
                  type="text"
                  placeholder="e.g., Must Visit, Popular Destination"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                <textarea
                  v-model="formData.description"
                  rows="3"
                  placeholder="Brief description of why this region is featured..."
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                  <input
                    v-model.number="formData.display_order"
                    type="number"
                    min="0"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select
                    v-model="formData.is_active"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option :value="true">Active</option>
                    <option :value="false">Inactive</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Featured Until (Optional)</label>
                <input
                  v-model="formData.featured_until"
                  type="datetime-local"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3">
            <button
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              @click="saveRegion"
              :disabled="!selectedRegion && !editingRegion"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ editingRegion ? 'Update' : 'Add' }} Region
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { VueDraggableNext as draggable } from 'vue-draggable-next'
import axios from 'axios'

const props = defineProps({
  regionId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['updated'])

// State
const featuredRegions = ref([])
const showAddModal = ref(false)
const editingRegion = ref(null)
const selectedRegion = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const loading = ref(false)
const formData = ref({
  label: '',
  description: '',
  display_order: 0,
  is_active: true,
  featured_until: null
})

// Debounce timer
let searchTimer = null

// Methods
const fetchFeaturedRegions = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/admin/regions/${props.regionId}/featured-regions`)
    featuredRegions.value = response.data.featured_regions || []
  } catch (error) {
    console.error('Failed to fetch featured regions:', error)
  } finally {
    loading.value = false
  }
}

const searchRegions = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    if (searchQuery.value.length < 2) {
      searchResults.value = []
      return
    }

    try {
      const response = await axios.get(`/api/admin/regions/${props.regionId}/search-regions-to-feature`, {
        params: { search: searchQuery.value }
      })
      searchResults.value = response.data.regions || []
    } catch (error) {
      console.error('Failed to search regions:', error)
    }
  }, 300)
}

const selectRegion = (region) => {
  selectedRegion.value = region
  searchResults.value = []
  searchQuery.value = region.name
}

const editFeaturedRegion = (region) => {
  editingRegion.value = region
  formData.value = {
    label: region.pivot?.label || '',
    description: region.pivot?.description || '',
    display_order: region.pivot?.display_order || 0,
    is_active: region.pivot?.is_active ?? true,
    featured_until: region.pivot?.featured_until || null
  }
}

const toggleActive = async (region) => {
  try {
    await axios.put(`/api/admin/regions/${props.regionId}/featured-regions/${region.id}`, {
      ...region.pivot,
      is_active: !region.pivot.is_active
    })
    await fetchFeaturedRegions()
  } catch (error) {
    console.error('Failed to toggle region status:', error)
  }
}

const saveRegion = async () => {
  try {
    if (editingRegion.value) {
      // Update existing
      await axios.put(`/api/admin/regions/${props.regionId}/featured-regions/${editingRegion.value.id}`, formData.value)
    } else if (selectedRegion.value) {
      // Add new
      await axios.post(`/api/admin/regions/${props.regionId}/featured-regions`, {
        featured_region_id: selectedRegion.value.id,
        ...formData.value
      })
    }
    
    await fetchFeaturedRegions()
    closeModal()
    emit('updated')
  } catch (error) {
    console.error('Failed to save featured region:', error)
    alert(error.response?.data?.message || 'Failed to save featured region')
  }
}

const removeFeaturedRegion = async (region) => {
  if (!confirm(`Remove ${region.name} from featured regions?`)) return
  
  try {
    await axios.delete(`/api/admin/regions/${props.regionId}/featured-regions/${region.id}`)
    await fetchFeaturedRegions()
    emit('updated')
  } catch (error) {
    console.error('Failed to remove featured region:', error)
  }
}

const updateOrder = async () => {
  try {
    const regions = featuredRegions.value.map((region, index) => ({
      id: region.id,
      display_order: index
    }))
    
    await axios.put(`/api/admin/regions/${props.regionId}/featured-regions/order`, { regions })
    emit('updated')
  } catch (error) {
    console.error('Failed to update order:', error)
  }
}

const closeModal = () => {
  showAddModal.value = false
  editingRegion.value = null
  selectedRegion.value = null
  searchQuery.value = ''
  searchResults.value = []
  formData.value = {
    label: '',
    description: '',
    display_order: 0,
    is_active: true,
    featured_until: null
  }
}

// Lifecycle
onMounted(() => {
  fetchFeaturedRegions()
})

// Watch for region changes
watch(() => props.regionId, () => {
  fetchFeaturedRegions()
})
</script>

<style scoped>
.drag-handle {
  cursor: move;
}
</style>