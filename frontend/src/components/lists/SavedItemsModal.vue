<template>
  <TransitionRoot :show="show" as="template" @after-leave="query = ''">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-gray-50 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <!-- Header -->
              <div class="bg-white px-6 py-4 border-b">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-gray-900">
                    Add from Saved Items
                  </h3>
                  <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                  >
                    <span class="sr-only">Close</span>
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="p-6">
                <div class="flex gap-6">
                  <!-- Sidebar -->
                  <div class="w-64 flex-shrink-0">
                    <!-- Search -->
                    <div class="mb-4">
                      <input
                        v-model="query"
                        type="text"
                        placeholder="Search saved items..."
                        class="w-full rounded-md border-gray-300 text-sm"
                      />
                    </div>

                    <!-- Collections -->
                    <div class="bg-white rounded-lg shadow p-4 mb-4">
                      <h2 class="text-sm font-medium text-gray-900 mb-3">Collections</h2>
                      <div class="space-y-1">
                        <!-- All Items -->
                        <button
                          @click="selectedCollection = null"
                          :class="[
                            'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors',
                            selectedCollection === null
                              ? 'bg-indigo-50 text-indigo-700'
                              : 'text-gray-700 hover:bg-gray-50'
                          ]"
                        >
                          <div class="flex items-center justify-between">
                            <span>All Items</span>
                            <span class="text-xs text-gray-500">{{ items.length }}</span>
                          </div>
                        </button>
                        
                        <!-- Uncategorized -->
                        <button
                          v-if="uncategorizedCount > 0"
                          @click="selectedCollection = 'uncategorized'"
                          :class="[
                            'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors',
                            selectedCollection === 'uncategorized'
                              ? 'bg-indigo-50 text-indigo-700'
                              : 'text-gray-700 hover:bg-gray-50'
                          ]"
                        >
                          <div class="flex items-center justify-between">
                            <span>Uncategorized</span>
                            <span class="text-xs text-gray-500">{{ uncategorizedCount }}</span>
                          </div>
                        </button>
                        
                        <!-- User Collections -->
                        <button
                          v-for="collection in collections"
                          :key="collection.id"
                          @click="selectedCollection = collection.id"
                          :class="[
                            'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors',
                            selectedCollection === collection.id
                              ? 'bg-indigo-50 text-indigo-700'
                              : 'text-gray-700 hover:bg-gray-50'
                          ]"
                        >
                          <div class="flex items-center justify-between">
                            <span>{{ collection.name }}</span>
                            <span class="text-xs text-gray-500">{{ collection.items_count }}</span>
                          </div>
                        </button>
                      </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="bg-white rounded-lg shadow p-4">
                      <h2 class="text-sm font-medium text-gray-900 mb-3">Filter by Type</h2>
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input
                            v-model="selectedTypes"
                            type="checkbox"
                            value="place"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Places</span>
                        </label>
                        <label class="flex items-center">
                          <input
                            v-model="selectedTypes"
                            type="checkbox"
                            value="userlist"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Lists</span>
                        </label>
                        <label class="flex items-center">
                          <input
                            v-model="selectedTypes"
                            type="checkbox"
                            value="region"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Regions</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Main Content -->
                  <div class="flex-1">
                    <!-- Loading -->
                    <div v-if="loading" class="flex justify-center py-12">
                      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="typeFilteredItems.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                      <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                        />
                      </svg>
                      <h3 class="mt-2 text-sm font-medium text-gray-900">No saved items found</h3>
                      <p class="mt-1 text-sm text-gray-500">
                        Try adjusting your filters or search query.
                      </p>
                    </div>

                    <!-- Items -->
                    <div v-else>
                      <!-- Multi-select toolbar -->
                      <div class="bg-white rounded-lg shadow p-3 flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                          <label class="flex items-center">
                            <input
                              type="checkbox"
                              :checked="isAllSelected"
                              :indeterminate="isPartiallySelected"
                              @change="toggleSelectAll"
                              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm text-gray-700">Select All</span>
                          </label>
                          <span v-if="selectedItems.length > 0" class="text-sm text-gray-700">
                            {{ selectedItems.length }} item{{ selectedItems.length > 1 ? 's' : '' }} selected
                          </span>
                        </div>
                      </div>

                      <!-- Items List -->
                      <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                          v-for="item in filteredItems"
                          :key="`${item.type}-${item.id}`"
                          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-4"
                        >
                          <div class="flex items-start">
                            <!-- Checkbox -->
                            <input
                              type="checkbox"
                              :checked="isItemSelected(item)"
                              @change="toggleItemSelection(item)"
                              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1 mr-3"
                            />

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                              <div class="flex items-center gap-2">
                                <h4 class="font-medium text-gray-900 truncate">{{ item.title }}</h4>
                                <span 
                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                  :class="{
                                    'bg-blue-100 text-blue-800': item.type === 'place',
                                    'bg-green-100 text-green-800': item.type === 'region',
                                    'bg-purple-100 text-purple-800': item.type === 'userlist'
                                  }"
                                >
                                  {{ item.type === 'userlist' ? 'List' : item.type }}
                                </span>
                              </div>
                              <p class="text-sm text-gray-500">
                                {{ item.category }}
                                <span v-if="item.location"> · {{ item.location }}</span>
                                <span v-if="item.region_type"> · {{ item.region_type }}</span>
                                <span v-if="item.author"> · by {{ item.author }}</span>
                                <span v-if="item.item_count"> · {{ item.item_count }} items</span>
                              </p>
                              <p v-if="item.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                                {{ item.description }}
                              </p>
                            </div>

                            <!-- Thumbnail if available -->
                            <div v-if="item.image_url" class="ml-4 flex-shrink-0">
                              <img 
                                :src="item.image_url" 
                                :alt="item.title"
                                class="h-16 w-16 rounded object-cover"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3 border-t">
                <button
                  @click="$emit('close')"
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  @click="addSelectedItems"
                  :disabled="selectedItems.length === 0"
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Add {{ selectedItems.length || 'Selected' }} Item{{ selectedItems.length !== 1 ? 's' : '' }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'add-items'])

const query = ref('')
const items = ref([])
const selectedItems = ref([])
const selectedTypes = ref(['place', 'region', 'userlist'])
const loading = ref(false)
const collections = ref([])
const selectedCollection = ref(null)
const uncategorizedCount = ref(0)

// Computed
const collectionFilteredItems = computed(() => {
  if (selectedCollection.value === null) {
    return items.value // All items
  } else if (selectedCollection.value === 'uncategorized') {
    return items.value.filter(item => !item.collection_id)
  } else {
    return items.value.filter(item => item.collection_id === selectedCollection.value)
  }
})

const typeFilteredItems = computed(() => {
  const result = collectionFilteredItems.value.filter(item => selectedTypes.value.includes(item.type))
  console.log('Type filtered items:', result.length, 'from', collectionFilteredItems.value.length)
  console.log('Selected types:', selectedTypes.value)
  console.log('Item types in data:', [...new Set(collectionFilteredItems.value.map(item => item.type))])
  return result
})

const filteredItems = computed(() => {
  if (!query.value) return typeFilteredItems.value
  
  const q = query.value.toLowerCase()
  return typeFilteredItems.value.filter(item => 
    item.title.toLowerCase().includes(q) ||
    (item.description && item.description.toLowerCase().includes(q)) ||
    (item.category && item.category.toLowerCase().includes(q))
  )
})

const isAllSelected = computed(() => {
  return filteredItems.value.length > 0 && 
    filteredItems.value.every(item => isItemSelected(item))
})

const isPartiallySelected = computed(() => {
  return filteredItems.value.some(item => isItemSelected(item)) && 
    !isAllSelected.value
})

// Methods
const fetchSavedItems = async () => {
  loading.value = true
  try {
    console.log('Fetching saved items for modal...')
    const response = await axios.get('/api/saved-items/for-list-creation')
    console.log('SavedItemsModal API Response:', response.data)
    console.log('Items count:', response.data.items?.length || 0)
    console.log('Collections count:', response.data.collections?.length || 0)
    console.log('First item example:', response.data.items?.[0])
    console.log('First collection example:', response.data.collections?.[0])
    
    items.value = response.data.items || []
    collections.value = response.data.collections || []
    uncategorizedCount.value = response.data.uncategorized_count || 0
    
    console.log('Items set in component:', items.value.length)
    console.log('Collections set in component:', collections.value.length)
  } catch (error) {
    console.error('Error fetching saved items:', error)
    console.error('Error response:', error.response?.data)
    console.error('Error status:', error.response?.status)
    items.value = []
    collections.value = []
  } finally {
    loading.value = false
  }
}

const isItemSelected = (item) => {
  return selectedItems.value.some(selected => 
    selected.id === item.id && selected.type === item.type
  )
}

const toggleItemSelection = (item) => {
  const index = selectedItems.value.findIndex(selected => 
    selected.id === item.id && selected.type === item.type
  )
  
  if (index === -1) {
    selectedItems.value.push(item)
  } else {
    selectedItems.value.splice(index, 1)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    // Deselect all visible items
    selectedItems.value = selectedItems.value.filter(selected => 
      !filteredItems.value.some(item => 
        item.id === selected.id && item.type === selected.type
      )
    )
  } else {
    // Select all visible items
    filteredItems.value.forEach(item => {
      if (!isItemSelected(item)) {
        selectedItems.value.push(item)
      }
    })
  }
}

const addSelectedItems = () => {
  emit('add-items', selectedItems.value)
  selectedItems.value = []
  emit('close')
}

// Watchers
watch(() => props.show, (newValue) => {
  console.log('SavedItemsModal show prop changed:', newValue)
  if (newValue) {
    // Always fetch when modal opens to get fresh data
    console.log('Modal opened, fetching saved items...')
    fetchSavedItems()
  } else {
    // Reset selection when closing
    selectedItems.value = []
  }
})
</script>