<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">Saved Items</h1>
          <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-500">
              {{ totalItems }} items saved
            </div>
            <button
              @click="showCollectionModal = true"
              class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              <PlusIcon class="h-4 w-4 mr-1" />
              New Collection
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="flex gap-6">
        <!-- Sidebar with Collections -->
        <div class="w-64 flex-shrink-0">
          <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-sm font-medium text-gray-900 mb-3">Collections</h2>
            
            <!-- All Items -->
            <button
              @click="selectedCollection = null"
              :class="[
                'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors mb-1',
                selectedCollection === null
                  ? 'bg-indigo-50 text-indigo-700'
                  : 'text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-between">
                <span class="flex items-center">
                  <FolderIcon class="h-4 w-4 mr-2" />
                  All Items
                </span>
                <span class="text-xs text-gray-500">{{ totalItems }}</span>
              </div>
            </button>

            <!-- Uncategorized -->
            <button
              @click="selectedCollection = 'uncategorized'"
              :class="[
                'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors mb-1',
                selectedCollection === 'uncategorized'
                  ? 'bg-indigo-50 text-indigo-700'
                  : 'text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-between">
                <span class="flex items-center">
                  <InboxIcon class="h-4 w-4 mr-2" />
                  Uncategorized
                </span>
                <span class="text-xs text-gray-500">{{ uncategorizedCount }}</span>
              </div>
            </button>

            <!-- Divider -->
            <div class="my-3 border-t border-gray-200"></div>

            <!-- User Collections -->
            <div v-if="collections.length > 0" class="space-y-1">
              <button
                v-for="collection in collections"
                :key="collection.id"
                @click="selectedCollection = collection.id"
                :class="[
                  'w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors group',
                  selectedCollection === collection.id
                    ? 'bg-indigo-50 text-indigo-700'
                    : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center justify-between">
                  <span class="flex items-center">
                    <div :class="`h-4 w-4 mr-2 rounded ${getColorClass(collection.color)} flex items-center justify-center`">
                      <component 
                        :is="getIconComponent(collection.icon)" 
                        class="h-3 w-3 text-white"
                      />
                    </div>
                    <span class="truncate">{{ collection.name }}</span>
                  </span>
                  <div class="flex items-center space-x-1">
                    <span class="text-xs text-gray-500">{{ collection.saved_items_count || 0 }}</span>
                    <Menu as="div" class="relative inline-block opacity-0 group-hover:opacity-100 transition-opacity">
                      <MenuButton class="p-0.5 rounded hover:bg-gray-200" @click.stop>
                        <EllipsisVerticalIcon class="h-3 w-3 text-gray-400" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                      >
                        <MenuItems class="absolute right-0 z-10 mt-1 w-40 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                          <div class="py-1">
                            <MenuItem v-slot="{ active }">
                              <button
                                @click.stop="editCollection(collection)"
                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700 w-full text-left']"
                              >
                                Edit
                              </button>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                              <button
                                @click.stop="deleteCollection(collection)"
                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-red-600 w-full text-left']"
                              >
                                Delete
                              </button>
                            </MenuItem>
                          </div>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </div>
              </button>
            </div>

            <!-- Empty Collections State -->
            <div v-else class="text-center py-4">
              <p class="text-sm text-gray-500">No collections yet</p>
            </div>
          </div>

          <!-- Type Filter -->
          <div class="bg-white rounded-lg shadow p-4 mt-4">
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
                  value="list"
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
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredItems.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
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
            <h3 class="mt-2 text-sm font-medium text-gray-900">No saved items</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ selectedCollection === 'uncategorized' ? 'No uncategorized items' : 'Start saving items to see them here' }}.
            </p>
          </div>

          <!-- Items Grid -->
          <div v-else class="space-y-4">
            <!-- Multi-select toolbar -->
            <div class="bg-white rounded-lg shadow p-3 flex items-center justify-between">
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
                <span v-if="selectedItems.size > 0" class="text-sm text-gray-700">
                  {{ selectedItems.size }} item{{ selectedItems.size > 1 ? 's' : '' }} selected
                </span>
              </div>
              <div v-if="selectedItems.size > 0" class="flex items-center space-x-2">
                <button
                  @click="moveSelectedToCollection"
                  class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                >
                  Move to Collection
                </button>
                <button
                  @click="removeSelectedItems"
                  class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                >
                  Remove
                </button>
                <button
                  @click="selectedItems.clear()"
                  class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                >
                  Cancel
                </button>
              </div>
            </div>

            <!-- Items -->
            <div 
              v-for="item in filteredItems"
              :key="`${item.type}-${item.id}`"
              class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-4"
            >
              <div class="flex items-start">
                <!-- Checkbox -->
                <input
                  type="checkbox"
                  :checked="selectedItems.has(item.id)"
                  @change="toggleItemSelection(item.id)"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1 mr-3"
                />

                <!-- Content based on type -->
                <div class="flex-1 min-w-0">
                  <!-- Place -->
                  <div v-if="item.type === 'place' && item.item">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                      <router-link
                        :to="item.item.url"
                        class="hover:text-blue-600"
                      >
                        {{ item.item.title }}
                      </router-link>
                    </h3>
                    <p v-if="item.item.category" class="text-sm text-gray-500">
                      {{ item.item.category }}
                      <span v-if="item.item.location"> · {{ item.item.location }}</span>
                    </p>
                    <p v-if="item.item.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                      {{ item.item.description }}
                    </p>
                  </div>

                  <!-- List -->
                  <div v-else-if="item.type === 'list' && item.item">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                      <router-link
                        :to="item.item.url"
                        class="hover:text-blue-600"
                      >
                        {{ item.item.name }}
                      </router-link>
                    </h3>
                    <p class="text-sm text-gray-500">
                      by {{ item.item.user.name }} · {{ item.item.items_count }} items
                      <span v-if="item.item.category"> · {{ item.item.category }}</span>
                    </p>
                    <p v-if="item.item.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                      {{ item.item.description }}
                    </p>
                  </div>

                  <!-- Region -->
                  <div v-else-if="item.type === 'region' && item.item">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                      <router-link
                        :to="item.item.url || getRegionUrl(item.item)"
                        class="hover:text-blue-600"
                      >
                        {{ item.item.name }}
                      </router-link>
                    </h3>
                    <p class="text-sm text-gray-500">
                      {{ item.item.type }}
                      <span v-if="item.item.parent"> in {{ item.item.parent }}</span>
                      · {{ item.item.place_count || 0 }} places
                    </p>
                  </div>

                  <!-- Metadata -->
                  <div class="mt-2 flex items-center justify-between">
                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                      <span>Saved {{ formatDate(item.saved_at) }}</span>
                      <span v-if="item.collection" class="flex items-center">
                        <div :class="`h-3 w-3 mr-1 rounded ${getColorClass(item.collection.color)}`"></div>
                        {{ item.collection.name }}
                      </span>
                    </div>
                    <Menu as="div" class="relative inline-block text-left">
                      <MenuButton class="p-1 rounded hover:bg-gray-100">
                        <EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                      >
                        <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                          <div class="py-1">
                            <MenuItem v-slot="{ active }">
                              <button
                                @click="moveToCollection(item)"
                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700 w-full text-left']"
                              >
                                Move to Collection
                              </button>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                              <button
                                @click="unsaveItem(item)"
                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-red-600 w-full text-left']"
                              >
                                Remove from Saved
                              </button>
                            </MenuItem>
                          </div>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </div>

                <!-- Image (for places) -->
                <div v-if="item.type === 'place' && item.item?.image_url" class="ml-4 flex-shrink-0">
                  <img
                    :src="item.item.image_url"
                    :alt="item.item.title"
                    class="h-20 w-20 rounded-lg object-cover"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-8 flex justify-center">
            <nav class="flex items-center space-x-2">
              <button
                @click="currentPage--"
                :disabled="currentPage === 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              <span class="px-4 py-2 text-sm text-gray-700">
                Page {{ currentPage }} of {{ totalPages }}
              </span>
              <button
                @click="currentPage++"
                :disabled="currentPage === totalPages"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Collection Modal -->
    <CollectionModal
      :is-open="showCollectionModal"
      :collection="editingCollection"
      @close="closeCollectionModal"
      @saved="handleCollectionSaved"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
      :is-open="showDeleteModal"
      :title="deleteModalTitle"
      :message="deleteModalMessage"
      :item-details="deleteModalDetails"
      @close="handleDeleteClose"
      @confirm="handleDeleteConfirm"
    />

    <!-- Move to Collection Modal -->
    <TransitionRoot as="template" :show="showMoveModal">
      <Dialog as="div" class="relative z-50" @close="showMoveModal = false">
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
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    Move to Collection
                  </DialogTitle>
                  <div class="space-y-2">
                    <button
                      @click="executeMoveToCollection(null)"
                      class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-50 border border-gray-200"
                    >
                      <div class="flex items-center">
                        <InboxIcon class="h-4 w-4 mr-2 text-gray-400" />
                        Uncategorized
                      </div>
                    </button>
                    <button
                      v-for="collection in collections"
                      :key="collection.id"
                      @click="executeMoveToCollection(collection.id)"
                      class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-50 border border-gray-200"
                    >
                      <div class="flex items-center">
                        <div :class="`h-4 w-4 mr-2 rounded ${getColorClass(collection.color)} flex items-center justify-center`">
                          <component 
                            :is="getIconComponent(collection.icon)" 
                            class="h-3 w-3 text-white"
                          />
                        </div>
                        {{ collection.name }}
                      </div>
                    </button>
                  </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button
                    type="button"
                    @click="showMoveModal = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                  >
                    Cancel
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { 
  PlusIcon, 
  FolderIcon, 
  InboxIcon,
  EllipsisVerticalIcon,
  EllipsisHorizontalIcon,
  StarIcon,
  HeartIcon,
  BookmarkIcon,
  FlagIcon,
  TagIcon,
  MapIcon,
  CameraIcon,
  ShoppingBagIcon,
  BriefcaseIcon
} from '@heroicons/vue/20/solid'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'
import CollectionModal from '@/components/CollectionModal.vue'
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue'

const { showSuccess, showError } = useNotification()

// State
const loading = ref(false)
const collections = ref([])
const uncategorizedCount = ref(0)
const savedItems = ref([])
const selectedCollection = ref(null)
const selectedTypes = ref(['place', 'list', 'region'])
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const selectedItems = ref(new Set())

// Modals
const showCollectionModal = ref(false)
const editingCollection = ref(null)
const showMoveModal = ref(false)
const movingItem = ref(null)
const movingItems = ref([])
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleteType = ref('') // 'collection', 'item', 'bulk'

// Computed
const filteredItems = computed(() => {
  return savedItems.value.filter(item => selectedTypes.value.includes(item.type))
})

const isAllSelected = computed(() => {
  return filteredItems.value.length > 0 && 
         filteredItems.value.every(item => selectedItems.value.has(item.id))
})

const isPartiallySelected = computed(() => {
  return selectedItems.value.size > 0 && !isAllSelected.value
})

// Icon mapping
const iconComponents = {
  folder: FolderIcon,
  star: StarIcon,
  heart: HeartIcon,
  bookmark: BookmarkIcon,
  flag: FlagIcon,
  tag: TagIcon,
  map: MapIcon,
  camera: CameraIcon,
  'shopping-bag': ShoppingBagIcon,
  briefcase: BriefcaseIcon
}

// Fetch collections
async function fetchCollections() {
  try {
    const response = await axios.get('/api/saved-collections')
    collections.value = response.data.collections
    uncategorizedCount.value = response.data.uncategorized_count
  } catch (error) {
    console.error('Error fetching collections:', error)
  }
}

// Fetch saved items
async function fetchSavedItems() {
  loading.value = true
  
  try {
    const params = {
      page: currentPage.value,
      per_page: 20
    }
    
    if (selectedCollection.value !== null) {
      params.collection = selectedCollection.value
    }
    
    const response = await axios.get('/api/saved-items', { params })
    
    savedItems.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
    totalItems.value = response.data.total
  } catch (error) {
    console.error('Error fetching saved items:', error)
    showError('Failed to load saved items')
  } finally {
    loading.value = false
  }
}

// Collection management
function editCollection(collection) {
  editingCollection.value = collection
  showCollectionModal.value = true
}

function deleteCollection(collection) {
  deleteTarget.value = collection
  deleteType.value = 'collection'
  showDeleteModal.value = true
}

async function confirmDeleteCollection() {
  try {
    await axios.delete(`/api/saved-collections/${deleteTarget.value.id}`)
    showSuccess('Collection deleted successfully')
    await fetchCollections()
    if (selectedCollection.value === deleteTarget.value.id) {
      selectedCollection.value = null
    }
    await fetchSavedItems()
  } catch (error) {
    showError('Failed to delete collection')
  }
}

function closeCollectionModal() {
  showCollectionModal.value = false
  editingCollection.value = null
}

async function handleCollectionSaved() {
  await fetchCollections()
  closeCollectionModal()
}

// Item management
function unsaveItem(item) {
  deleteTarget.value = item
  deleteType.value = 'item'
  showDeleteModal.value = true
}

async function confirmDeleteItem() {
  try {
    const item = deleteTarget.value
    const itemId = item.item?.id || item.saveable_id
    await axios.delete(`/api/saved-items/${item.type}/${itemId}`)
    
    savedItems.value = savedItems.value.filter(i => i.id !== item.id)
    totalItems.value--
    showSuccess('Item removed from saved')
    
    await fetchCollections() // Update counts
  } catch (error) {
    showError('Failed to remove item')
  }
}

function toggleItemSelection(itemId) {
  if (selectedItems.value.has(itemId)) {
    selectedItems.value.delete(itemId)
  } else {
    selectedItems.value.add(itemId)
  }
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    // Deselect all
    selectedItems.value.clear()
  } else {
    // Select all visible items
    filteredItems.value.forEach(item => {
      selectedItems.value.add(item.id)
    })
  }
}

function moveToCollection(item) {
  movingItem.value = item
  movingItems.value = [item]
  showMoveModal.value = true
}

function moveSelectedToCollection() {
  movingItems.value = savedItems.value.filter(item => selectedItems.value.has(item.id))
  showMoveModal.value = true
}

async function executeMoveToCollection(collectionId) {
  try {
    const items = movingItems.value
    
    for (const item of items) {
      await axios.put(`/api/saved-items/${item.id}/move`, {
        collection_id: collectionId
      })
    }
    
    showSuccess(`Moved ${items.length} item${items.length > 1 ? 's' : ''} to collection`)
    showMoveModal.value = false
    movingItem.value = null
    movingItems.value = []
    selectedItems.value.clear()
    
    await fetchCollections()
    await fetchSavedItems()
  } catch (error) {
    showError('Failed to move items')
  }
}

function removeSelectedItems() {
  const count = selectedItems.value.size
  deleteTarget.value = { count, items: savedItems.value.filter(item => selectedItems.value.has(item.id)) }
  deleteType.value = 'bulk'
  showDeleteModal.value = true
}

async function confirmBulkRemove() {
  try {
    const itemsToRemove = deleteTarget.value.items
    
    // Remove each selected item
    for (const item of itemsToRemove) {
      const itemId = item.item?.id || item.saveable_id
      await axios.delete(`/api/saved-items/${item.type}/${itemId}`)
    }
    
    const count = deleteTarget.value.count
    showSuccess(`Successfully removed ${count} item${count > 1 ? 's' : ''} from saved`)
    selectedItems.value.clear()
    
    // Update counts and refresh
    totalItems.value -= count
    await fetchCollections()
    await fetchSavedItems()
  } catch (error) {
    showError('Failed to remove some items')
  }
}

// Utilities
function formatDate(date) {
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'today'
  if (days === 1) return 'yesterday'
  if (days < 7) return `${days} days ago`
  if (days < 30) return `${Math.floor(days / 7)} weeks ago`
  return d.toLocaleDateString()
}

function getRegionUrl(region) {
  if (!region) return '#'
  if (region.url) return region.url
  
  if (region.type === 'state') {
    return `/regions/${region.slug}`
  } else if (region.type === 'city') {
    const stateSlug = region.parent?.slug
    return stateSlug ? `/regions/${stateSlug}/${region.slug}` : `/regions/${region.slug}`
  }
  
  return `/regions/${region.slug}`
}

function getColorClass(color) {
  const colorMap = {
    gray: 'bg-gray-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    teal: 'bg-teal-500',
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500'
  }
  return colorMap[color] || 'bg-gray-500'
}

function getIconComponent(icon) {
  return iconComponents[icon] || FolderIcon
}

// Delete modal handlers
function handleDeleteConfirm() {
  showDeleteModal.value = false
  
  switch (deleteType.value) {
    case 'collection':
      confirmDeleteCollection()
      break
    case 'bulk':
      confirmBulkRemove()
      break
    case 'item':
      confirmDeleteItem()
      break
  }
}

function handleDeleteClose() {
  showDeleteModal.value = false
  deleteTarget.value = null
  deleteType.value = ''
}

// Computed properties for delete modal
const deleteModalTitle = computed(() => {
  switch (deleteType.value) {
    case 'collection':
      return 'Delete Collection'
    case 'bulk':
      return 'Remove Items'
    case 'item':
      return 'Remove Item'
    default:
      return 'Confirm Action'
  }
})

const deleteModalMessage = computed(() => {
  switch (deleteType.value) {
    case 'collection':
      return `Are you sure you want to delete "${deleteTarget.value?.name}"? Items in this collection will be moved to Uncategorized.`
    case 'bulk':
      const count = deleteTarget.value?.count || 0
      return `Are you sure you want to remove ${count} item${count > 1 ? 's' : ''} from your saved items?`
    case 'item':
      return 'Are you sure you want to remove this item from your saved items?'
    default:
      return 'Are you sure you want to proceed?'
  }
})

const deleteModalDetails = computed(() => {
  if (deleteType.value === 'bulk' && deleteTarget.value?.count) {
    return {
      name: `${deleteTarget.value.count} selected items`,
      description: 'This action cannot be undone'
    }
  }
  return null
})

// Watchers
watch(selectedCollection, () => {
  currentPage.value = 1
  fetchSavedItems()
})

watch(currentPage, () => {
  fetchSavedItems()
})

// Lifecycle
onMounted(() => {
  fetchCollections()
  fetchSavedItems()
})
</script>