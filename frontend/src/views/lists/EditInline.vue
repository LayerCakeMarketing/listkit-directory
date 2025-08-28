<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Main Content Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Content (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Sticky Title (Inline Editable) -->
          <div ref="titleContainer" class="sticky top-[1.8rem] z-30 bg-gray-50 -mx-4 px-4 -mt-8 pt-8">
            <div class="bg-white rounded-t-lg shadow-sm p-6">
              <div class="group">
                <h1
                  v-if="!editingTitle"
                  @click="startEditingTitle"
                  class="text-4xl font-bold text-gray-900 cursor-text hover:bg-gray-50 rounded px-2 py-1 -ml-2 break-words"
                >
                  {{ listForm.name || 'Untitled List' }}
                  <PencilIcon class="inline-block h-5 w-5 ml-2 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                </h1>
                <input
                  v-else
                  ref="titleInput"
                  v-model="listForm.name"
                  @blur="saveTitle"
                  @keyup.enter="saveTitle"
                  @keyup.escape="cancelEditingTitle"
                  type="text"
                  class="text-4xl font-bold text-gray-900 border-0 outline-none ring-2 ring-indigo-500 rounded px-2 py-1 w-full"
                  placeholder="Enter list name..."
                />
              </div>
            </div>
          </div>

          <!-- Description (Inline Editable with Rich Text) -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <div
              v-if="!editingDescription"
              @click="startEditingDescription"
              class="list-description prose max-w-none cursor-text hover:bg-gray-50 rounded p-3 min-h-[60px] border border-transparent hover:border-gray-300"
            >
              <div v-if="listForm.description" v-html="listForm.description"></div>
              <p v-else class="text-gray-400">Click to add description...</p>
            </div>
            <div v-else>
              <RichTextEditor
                ref="descriptionEditor"
                v-model="listForm.description"
                @blur="saveDescription"
                placeholder="Enter list description..."
                :max-height="300"
                :auto-focus="true"
              />
              <div class="mt-2 flex justify-end gap-2">
                <button
                  @click="cancelEditingDescription"
                  class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                >
                  Cancel
                </button>
                <button
                  @click="saveDescription"
                  class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                >
                  Save
                </button>
              </div>
            </div>
          </div>

          <!-- Items Section -->
          <div class="bg-white rounded-lg shadow-sm">
            <!-- Sticky Add Item Options -->
            <div :style="{ top: buttonsStickyTop }" class="sticky z-20 -mt-4">
              <div class="bg-white px-6 pt-4 pb-4 shadow-sm border-b border-gray-200">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                  <button
                    v-for="type in itemTypes"
                    :key="type.value"
                    type="button"
                    @click="handleItemTypeClick(type.value)"
                    :class="[
                      'p-2 text-sm rounded border transition-all',
                      selectedItemType === type.value
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                    ]"
                  >
                    {{ type.label }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Main content area with padding -->
            <div class="p-6">
              <!-- Add Item Forms -->
              <div v-if="selectedItemType === 'text'" class="space-y-2 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <input
                  v-model="newItem.title"
                  type="text"
                  placeholder="Title"
                  class="w-full rounded-md border-gray-300"
                />
                <RichTextEditor
                  v-model="newItem.content"
                  placeholder="Enter content..."
                  :max-height="150"
                />
                <button
                  type="button"
                  @click="addTextItem"
                  class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded"
                >
                  Add Text
                </button>
              </div>

            <!-- Location Form -->
            <div v-if="selectedItemType === 'location'" class="space-y-2 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <input
                v-model="newItem.title"
                type="text"
                placeholder="Location name"
                class="w-full rounded-md border-gray-300"
              />
              <RichTextEditor
                v-model="newItem.content"
                placeholder="Enter description..."
                :max-height="150"
              />
              <input
                v-model="newItem.data.address"
                type="text"
                placeholder="Address"
                class="w-full rounded-md border-gray-300"
              />
              <button
                type="button"
                @click="addLocationItem"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded"
              >
                Add Location
              </button>
            </div>

            <!-- Event Form -->
            <div v-if="selectedItemType === 'event'" class="space-y-2 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <input
                v-model="newItem.title"
                type="text"
                placeholder="Event name"
                class="w-full rounded-md border-gray-300"
              />
              <RichTextEditor
                v-model="newItem.content"
                placeholder="Enter description..."
                :max-height="150"
              />
              <div class="grid grid-cols-2 gap-2">
                <input
                  v-model="newItem.data.date"
                  type="date"
                  class="rounded-md border-gray-300"
                />
                <input
                  v-model="newItem.data.time"
                  type="time"
                  class="rounded-md border-gray-300"
                />
              </div>
              <button
                type="button"
                @click="addEventItem"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded"
              >
                Add Event
              </button>
            </div>

            <!-- Sections (only if v2.0 with actual sections) -->
            <div v-if="list.structure_version === '2.0' && sections.length > 0" class="mb-4">
              <draggable
                v-model="sections"
                @end="handleReorderSections"
                item-key="id"
                handle=".section-handle"
                class="space-y-4"
              >
                <template #item="{ element: section }">
                  <ListSection
                    :section="section"
                    :items="getSectionItems(section.id)"
                    @update-heading="updateSectionHeading"
                    @remove="removeSection"
                    @reorder-items="handleReorderItems"
                    @edit-item="editItem"
                    @remove-item="removeItem"
                  />
                </template>
              </draggable>
            </div>
            
            <!-- Items List -->
            <div v-if="items && items.length > 0" class="space-y-2">
              <TransitionGroup name="list" tag="div">
                <div
                  v-for="(item, index) in items"
                  :key="item.id"
                  :draggable="true"
                  @dragstart="handleDragStart($event, index)"
                  @dragover.prevent
                  @drop="handleDrop($event, index)"
                  @dragend="handleDragEnd"
                  class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex items-start hover:bg-white transition-colors group cursor-move"
                  :class="{ 'opacity-50': dragIndex === index }"
                >
                  <!-- Smaller Drag Handle -->
                  <div class="drag-handle mr-2 mt-1 p-1 hover:bg-gray-200 rounded pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9 5h2v2H9V5zm0 4h2v2H9V9zm0 4h2v2H9v-2zm0 4h2v2H9v-2zm4-12h2v2h-2V5zm0 4h2v2h-2V9zm0 4h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                    </svg>
                  </div>
                  
                  <!-- Item Content -->
                  <div class="flex-1 pointer-events-none">
                    <div class="flex gap-3">
                      <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ item.title || item.display_title || 'Untitled' }}</h4>
                        <div v-if="item.content || item.display_content" class="text-sm text-gray-600 mt-1 prose prose-sm max-w-none">
                          <div v-html="truncateHtml(item.content || item.display_content, 200)"></div>
                        </div>
                        <div v-if="item.type === 'location' && item.data?.address" class="text-xs text-gray-500 mt-1">
                          📍 {{ item.data.address }}
                        </div>
                        <div v-if="item.type === 'event' && (item.data?.date || item.data?.time)" class="text-xs text-gray-500 mt-1">
                          📅 {{ item.data.date }} {{ item.data.time }}
                        </div>
                        <div v-if="item.type === 'directory_entry' && item.place" class="text-xs text-gray-500 mt-1">
                          📍 {{ item.place.name }}
                        </div>
                      </div>
                      <!-- Item Image -->
                      <div v-if="item.item_image_cloudflare_id || item.item_image_url" class="flex-shrink-0">
                        <img 
                          :src="item.item_image_url || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.item_image_cloudflare_id}/thumbnail`"
                          :alt="item.title || 'Item image'"
                          class="w-16 h-16 rounded-lg object-cover"
                        />
                      </div>
                    </div>
                  </div>
                  
                  <!-- Action Buttons -->
                  <div class="flex items-center space-x-1 ml-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      @click.stop="editItem(item)"
                      class="text-gray-400 hover:text-gray-600 p-1 pointer-events-auto"
                      title="Edit item"
                    >
                      <PencilIcon class="h-4 w-4" />
                    </button>
                    <button
                      @click.stop="removeItem(item)"
                      class="text-gray-400 hover:text-red-600 p-1 pointer-events-auto"
                      title="Remove item"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </TransitionGroup>
            </div>

              <!-- Empty State -->
              <div v-if="items.length === 0 && sections.length === 0" class="text-center py-8 text-gray-500">
                <p>No items yet. Add your first item above.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Sticky Sidebar (1/3 width) -->
        <div class="lg:col-span-1">
          <div class="sticky top-24 space-y-4">
            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="space-y-3">
                <!-- Navigation Buttons -->
                <div class="grid grid-cols-2 gap-2">
                  <router-link
                    :to="backLink"
                    class="flex items-center justify-center text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                  >
                    <ChevronLeftIcon class="h-4 w-4 mr-1" />
                    Back
                  </router-link>
                  
                  <button
                    @click="viewList"
                    type="button"
                    class="flex items-center justify-center text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                  >
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View
                  </button>
                </div>

                <!-- Auto-save indicator -->
                <div v-if="autoSaveStatus" class="text-center text-sm">
                  <span v-if="autoSaveStatus === 'saving'" class="text-gray-500 flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </span>
                  <span v-else-if="autoSaveStatus === 'saved'" class="text-green-600 flex items-center justify-center">
                    <CheckCircleIcon class="h-4 w-4 mr-1" />
                    Saved
                  </span>
                  <span v-else-if="autoSaveStatus === 'error'" class="text-red-600">
                    Error saving
                  </span>
                </div>
                
                <!-- Preview Button (optional, can remove if View is enough) -->
                <button
                  v-if="false && hasSaved"
                  @click="previewList"
                  type="button"
                  class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors"
                >
                  Preview List
                </button>
              </div>
            </div>

            <!-- Settings Card -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-4">List Settings</h3>
              <div class="space-y-4">
                <!-- Visibility -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Visibility</label>
                  <select
                    v-model="listForm.visibility"
                    @change="updateField('visibility', listForm.visibility)"
                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="private">🔒 Private</option>
                    <option value="unlisted">🔗 Unlisted</option>
                    <option value="public">🌍 Public</option>
                  </select>
                </div>

                <!-- Category -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                  <select
                    v-model="listForm.category_id"
                    @change="updateField('category_id', listForm.category_id)"
                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">Select category...</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>

                <!-- Draft Status -->
                <div>
                  <label class="flex items-center">
                    <input
                      type="checkbox"
                      v-model="listForm.is_draft"
                      @change="updateField('is_draft', listForm.is_draft)"
                      class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">Save as Draft</span>
                  </label>
                </div>

                <!-- Cover Image -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Cover Image</label>
                  <div v-if="listForm.featured_image_url" class="relative">
                    <img 
                      :src="listForm.featured_image_url" 
                      alt="Cover" 
                      class="w-full h-24 object-cover rounded-md"
                    />
                    <button
                      @click="removeCoverImage"
                      type="button"
                      class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full hover:bg-red-600"
                    >
                      <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <CloudflareDragDropUploader
                    v-else
                    :max-files="1"
                    :max-file-size="5242880"
                    context="cover"
                    @upload-success="handleCoverImageUploaded"
                  />
                </div>

                <!-- Channel -->
                <div v-if="userChannels.length > 0">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Channel</label>
                  <select
                    v-model="listForm.channel_id"
                    @change="updateField('channel_id', listForm.channel_id)"
                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option :value="null">Personal List</option>
                    <option v-for="channel in userChannels" :key="channel.id" :value="channel.id">
                      @{{ channel.slug }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Tags Card -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-3">Tags</h3>
              <TagInput
                v-model="listForm.tags"
                @update:modelValue="handleTagsUpdate"
                placeholder="Add tags..."
                :allow-create="true"
                :max-tags="10"
                :compact="true"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals (keep existing modals for sections, edit items, etc.) -->
    <AddSectionModal
      v-if="showAddSection"
      @close="showAddSection = false"
      @create="createSection"
    />
    
    <SavedItemsModal
      v-if="showSavedItems"
      @close="showSavedItems = false"
      @items-selected="handleAddSavedItems"
    />

    <!-- Edit Item Modal (keep existing) -->
    <EditItemModal
      v-if="showEditModal"
      :item="editingItem"
      :form="editForm"
      :saving="savingItem"
      @close="closeEditModal"
      @save="updateItem"
      @image-upload="handleItemImageUpload"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { VueDraggableNext as draggable } from 'vue-draggable-next'
import { debounce } from 'lodash'
import { 
  ChevronLeftIcon, 
  PencilIcon, 
  TrashIcon, 
  CheckCircleIcon,
  PlusIcon 
} from '@heroicons/vue/24/outline'
import { useNotification } from '@/composables/useNotification'
import RichTextEditor from '@/components/RichTextEditor.vue'
import TagInput from '@/components/TagInput.vue'
import ListSection from '@/components/lists/ListSection.vue'
import SavedItemsModal from '@/components/lists/SavedItemsModal.vue'
import AddSectionModal from '@/components/lists/AddSectionModal.vue'
import EditItemModal from '@/components/lists/EditItemModal.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showSuccess, showError } = useNotification()

// Props and state
const listId = computed(() => route.params.id)
const loading = ref(true)
const autoSaveStatus = ref(null)
const hasSaved = ref(false)

// Refs for dynamic positioning
const titleContainer = ref(null)
const titleHeight = ref(0)

// List data
const list = ref({})
const listForm = reactive({
  name: '',
  description: '',
  visibility: 'private',
  is_draft: true,
  category_id: '',
  channel_id: null,
  tags: [],
  featured_image: null,
  featured_image_url: null,
  featured_image_cloudflare_id: null
})

// Editing states
const editingTitle = ref(false)
const editingDescription = ref(false)
const originalTitle = ref('')
const originalDescription = ref('')

// Items and sections
const items = ref([])
const sections = ref([])
const showAddSection = ref(false)
const selectedItemType = ref(null)
const showSavedItems = ref(false)
const showEditModal = ref(false)
const editingItem = ref(null)
const savingItem = ref(false)
const dragIndex = ref(null)

// Categories and channels
const categories = ref([])
const userChannels = ref([])

// New item form
const newItem = reactive({
  title: '',
  content: '',
  data: {}
})

// Edit form
const editForm = reactive({
  title: '',
  content: '',
  data: {},
  affiliate_url: '',
  notes: '',
  item_image: null,
  item_image_url: null
})

// Item types configuration
const itemTypes = computed(() => {
  const types = [
    { value: 'text', label: 'Text' },
    { value: 'saved', label: 'Saved' },
    { value: 'location', label: 'Location' },
    { value: 'event', label: 'Event' }
  ]
  
  // Add section option if using v2.0
  if (list.value.structure_version === '2.0') {
    types.push({ value: 'section', label: 'Section' })
  }
  
  return types
})

// Navigation
const backLink = computed(() => {
  if (list.value.channel_id && list.value.channel) {
    return `/@${list.value.channel.slug}/lists`
  }
  return '/mylists'
})

// Dynamic positioning for buttons based on title height
const buttonsStickyTop = computed(() => {
  // Base position: 1.8rem (title top) + title container height
  // We use a minimum height to handle initial render
  const baseTop = 28.8 // 1.8rem in pixels
  const minHeight = 120 // Minimum expected height for single line title
  const actualHeight = titleHeight.value || minHeight
  return `${baseTop + actualHeight}px`
})

// Title editing
const startEditingTitle = () => {
  originalTitle.value = listForm.name
  editingTitle.value = true
  nextTick(() => {
    const input = document.querySelector('input[ref="titleInput"]')
    if (input) input.focus()
  })
}

const saveTitle = () => {
  if (!listForm.name.trim()) {
    listForm.name = originalTitle.value
  }
  editingTitle.value = false
  if (listForm.name !== originalTitle.value) {
    updateField('name', listForm.name)
  }
}

const cancelEditingTitle = () => {
  listForm.name = originalTitle.value
  editingTitle.value = false
}

// Description editing
const startEditingDescription = () => {
  originalDescription.value = listForm.description
  editingDescription.value = true
}

const saveDescription = () => {
  editingDescription.value = false
  if (listForm.description !== originalDescription.value) {
    updateField('description', listForm.description)
  }
}

const cancelEditingDescription = () => {
  listForm.description = originalDescription.value
  editingDescription.value = false
}

// Auto-save functionality
const updateField = debounce(async (field, value) => {
  autoSaveStatus.value = 'saving'
  
  try {
    const response = await axios.patch(`/api/lists/${listId.value}/field`, {
      field,
      value
    })
    
    // Update slug if name was changed
    if (field === 'name' && response.data.slug) {
      list.value.slug = response.data.slug
    }
    
    autoSaveStatus.value = 'saved'
    hasSaved.value = true
    
    // Clear saved status after 2 seconds
    setTimeout(() => {
      autoSaveStatus.value = null
    }, 2000)
  } catch (error) {
    autoSaveStatus.value = 'error'
    showError('Error', 'Failed to save changes')
    console.error('Error updating field:', error)
  }
}, 500)

// Tags update
const handleTagsUpdate = (newTags) => {
  updateList() // Use full update for tags since they're more complex
}

// Full update (for complex fields like tags)
const updateList = debounce(async () => {
  autoSaveStatus.value = 'saving'
  
  try {
    await axios.put(`/api/lists/${listId.value}`, listForm)
    autoSaveStatus.value = 'saved'
    hasSaved.value = true
    
    setTimeout(() => {
      autoSaveStatus.value = null
    }, 2000)
  } catch (error) {
    autoSaveStatus.value = 'error'
    showError('Error', error.response?.data?.message || 'Failed to save list')
  }
}, 1000)

// Fetch list data
const fetchList = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/lists/${listId.value}`)
    
    list.value = response.data
    // Process items to add image URLs
    items.value = (response.data.items || []).map(item => {
      if (item.item_image_cloudflare_id) {
        item.item_image_url = `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.item_image_cloudflare_id}/public`
      }
      return item
    })
    
    console.log('List fetched:', response.data)
    console.log('Items loaded:', items.value)
    
    // Populate form
    Object.assign(listForm, {
      name: response.data.name,
      description: response.data.description || '',
      visibility: response.data.visibility,
      is_draft: response.data.is_draft || false,
      category_id: response.data.category_id,
      channel_id: response.data.channel_id || null,
      tags: response.data.tags || [],
      featured_image_cloudflare_id: response.data.featured_image_cloudflare_id,
      featured_image_url: response.data.featured_image_cloudflare_id 
        ? `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${response.data.featured_image_cloudflare_id}/public`
        : response.data.featured_image_url || response.data.featured_image,
      featured_image: response.data.featured_image_cloudflare_id 
        ? `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${response.data.featured_image_cloudflare_id}/thumbnail`
        : response.data.featured_image
    })
    
    // Load sections if v2 structure
    if (response.data.structure_version === '2.0') {
      sections.value = response.data.sections || []
    }
  } catch (error) {
    console.error('Error fetching list:', error)
    showError('Error', 'Failed to load list')
    router.push('/mylists')
  } finally {
    loading.value = false
  }
}

// Fetch categories
const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/list-categories')
    categories.value = response.data
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

// Fetch channels
const fetchChannels = async () => {
  try {
    const response = await axios.get('/api/my-channels')
    userChannels.value = response.data
  } catch (error) {
    console.error('Error fetching channels:', error)
  }
}

// View list in same window
const viewList = () => {
  let listUrl = ''
  
  if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
    listUrl = `/${list.value.channel.slug}/${list.value.slug}`
  } else if (list.value.channel_id && list.value.channel) {
    listUrl = `/${list.value.channel.slug}/${list.value.slug}`
  } else {
    const user = list.value.user
    const baseUrl = user?.custom_url ? `/up/@${user.custom_url}` : `/up/@${user?.username || user?.id}`
    listUrl = `${baseUrl}/${list.value.slug}`
  }
  
  router.push(listUrl)
}

// Preview list in new window
const previewList = () => {
  let listUrl = ''
  
  if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
    listUrl = `/${list.value.channel.slug}/${list.value.slug}`
  } else if (list.value.channel_id && list.value.channel) {
    listUrl = `/${list.value.channel.slug}/${list.value.slug}`
  } else {
    const user = list.value.user
    const baseUrl = user?.custom_url ? `/up/@${user.custom_url}` : `/up/@${user?.username || user?.id}`
    listUrl = `${baseUrl}/${list.value.slug}`
  }
  
  window.open(listUrl, '_blank')
}

// Item type handling
const handleItemTypeClick = (type) => {
  if (type === 'saved') {
    showSavedItems.value = true
    selectedItemType.value = null
  } else if (type === 'section') {
    showAddSection.value = true
    selectedItemType.value = null
  } else {
    selectedItemType.value = selectedItemType.value === type ? null : type
  }
}

// Drag and drop handling
const handleDragStart = (event, index) => {
  dragIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/html', event.target.innerHTML)
}

const handleDrop = (event, dropIndex) => {
  event.preventDefault()
  if (dragIndex.value === null) return
  
  const draggedItem = items.value[dragIndex.value]
  
  // Remove from old position
  items.value.splice(dragIndex.value, 1)
  
  // Insert at new position
  if (dragIndex.value < dropIndex) {
    items.value.splice(dropIndex - 1, 0, draggedItem)
  } else {
    items.value.splice(dropIndex, 0, draggedItem)
  }
  
  // Save new order
  saveItemOrder()
}

const handleDragEnd = () => {
  dragIndex.value = null
}

// Add text item
const addTextItem = async () => {
  if (!newItem.title) {
    showError('Validation Error', 'Please enter a title')
    return
  }
  
  console.log('Adding item to list:', listId.value)
  console.log('Item data:', { title: newItem.title, content: newItem.content })
  
  try {
    const response = await axios.post(`/api/lists/${listId.value}/items`, {
      type: 'text',
      title: newItem.title,
      content: newItem.content
    })
    console.log('Item added response:', response.data)
    items.value.push(response.data.item)
    newItem.title = ''
    newItem.content = ''
    selectedItemType.value = null
    showSuccess('Success', 'Item added')
    // Force reactivity update
    items.value = [...items.value]
  } catch (error) {
    console.error('Error adding item:', error)
    console.error('Error response:', error.response?.data)
    showError('Error', error.response?.data?.message || 'Failed to add text item')
  }
}

// Add location item
const addLocationItem = async () => {
  if (!newItem.title) {
    showError('Validation Error', 'Please enter a location name')
    return
  }
  
  try {
    const response = await axios.post(`/api/lists/${listId.value}/items`, {
      type: 'location',
      title: newItem.title,
      content: newItem.content,
      data: {
        address: newItem.data.address || ''
      }
    })
    items.value.push(response.data.item)
    newItem.title = ''
    newItem.content = ''
    newItem.data = {}
    selectedItemType.value = null
    showSuccess('Success', 'Location added')
    // Force reactivity update
    items.value = [...items.value]
  } catch (error) {
    showError('Error', error.response?.data?.message || 'Failed to add location')
  }
}

// Add event item
const addEventItem = async () => {
  if (!newItem.title) {
    showError('Validation Error', 'Please enter an event name')
    return
  }
  
  try {
    const response = await axios.post(`/api/lists/${listId.value}/items`, {
      type: 'event',
      title: newItem.title,
      content: newItem.content,
      data: {
        date: newItem.data.date || '',
        time: newItem.data.time || ''
      }
    })
    items.value.push(response.data.item)
    newItem.title = ''
    newItem.content = ''
    newItem.data = {}
    selectedItemType.value = null
    showSuccess('Success', 'Event added')
    // Force reactivity update
    items.value = [...items.value]
  } catch (error) {
    showError('Error', error.response?.data?.message || 'Failed to add event')
  }
}

// Handle saved items
const handleAddSavedItems = async (selectedItems) => {
  try {
    const formattedItems = selectedItems.map(item => ({
      id: item.id,
      type: item.type
    }))
    
    const currentSectionId = sections.value.length > 0 ? sections.value[0].id : null
    
    const response = await axios.post(`/api/lists/${listId.value}/items/add-saved`, {
      items: formattedItems,
      section_id: currentSectionId
    })
    
    response.data.items.forEach(item => {
      if (currentSectionId) {
        item.section_id = currentSectionId
      }
      items.value.push(item)
    })
    
    showSavedItems.value = false
    showSuccess('Success', `Added ${response.data.items.length} items`)
  } catch (error) {
    console.error('Error adding saved items:', error)
    showError('Error', error.response?.data?.message || 'Failed to add saved items')
  }
}

// Section management
const createSection = async (heading) => {
  try {
    const response = await axios.post(`/api/lists/${listId.value}/sections`, {
      heading
    })
    
    showAddSection.value = false
    showSuccess('Success', 'Section created')
    await fetchList() // Refresh to get proper structure
  } catch (error) {
    console.error('Error creating section:', error)
    showError('Error', error.response?.data?.message || 'Failed to create section')
  }
}

const updateSectionHeading = async (sectionId, newHeading) => {
  try {
    await axios.put(`/api/lists/${listId.value}/sections/${sectionId}`, {
      heading: newHeading
    })
    const section = sections.value.find(s => s.id === sectionId)
    if (section) {
      section.heading = newHeading
    }
    showSuccess('Success', 'Section heading updated')
  } catch (error) {
    showError('Error', 'Failed to update section heading')
  }
}

const removeSection = async (sectionId) => {
  if (!confirm('Remove this section? Items will be moved to the default section.')) return
  
  try {
    await axios.delete(`/api/lists/${listId.value}/sections/${sectionId}`)
    sections.value = sections.value.filter(s => s.id !== sectionId)
    showSuccess('Success', 'Section removed')
    await fetchList()
  } catch (error) {
    showError('Error', 'Failed to remove section')
  }
}

const getSectionItems = (sectionId) => {
  return items.value.filter(item => item.section_id === sectionId)
}

// Item management
const editItem = (item) => {
  editingItem.value = item
  // Construct image URL if cloudflare_id exists
  const imageUrl = item.item_image_cloudflare_id 
    ? `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.item_image_cloudflare_id}/public`
    : item.item_image_url || null
    
  Object.assign(editForm, {
    title: item.title || '',
    content: item.content || '',
    data: item.data || {},
    affiliate_url: item.affiliate_url || '',
    notes: item.notes || '',
    item_image: item.item_image_cloudflare_id || null,
    item_image_url: imageUrl
  })
  showEditModal.value = true
}

const updateItem = async () => {
  savingItem.value = true
  try {
    // Only send database fields, not the URL
    const updateData = {
      title: editForm.title,
      content: editForm.content,
      data: editForm.data,
      affiliate_url: editForm.affiliate_url,
      notes: editForm.notes,
      item_image_cloudflare_id: editForm.item_image
    }
    
    console.log('Updating item with data:', updateData)
    const response = await axios.put(`/api/lists/${listId.value}/items/${editingItem.value.id}`, updateData)
    console.log('Update response:', response.data)
    
    const index = items.value.findIndex(i => i.id === editingItem.value.id)
    if (index !== -1) {
      // Update the local item with both the data and the URL for display
      Object.assign(items.value[index], {
        ...updateData,
        item_image_url: editForm.item_image_url
      })
    }
    
    closeEditModal()
    showSuccess('Success', 'Item updated')
  } catch (error) {
    showError('Error', error.response?.data?.message || 'Failed to update item')
  } finally {
    savingItem.value = false
  }
}

const removeItem = async (item) => {
  if (!confirm('Are you sure you want to remove this item?')) return
  
  try {
    await axios.delete(`/api/lists/${listId.value}/items/${item.id}`)
    items.value = items.value.filter(i => i.id !== item.id)
    showSuccess('Success', 'Item removed')
  } catch (error) {
    showError('Error', error.response?.data?.message || 'Failed to remove item')
  }
}

const closeEditModal = () => {
  showEditModal.value = false
  editingItem.value = null
  Object.keys(editForm).forEach(key => {
    editForm[key] = key === 'data' ? {} : ''
  })
}

// Move item up
const moveItemUp = async (index) => {
  if (index > 0) {
    const temp = items.value[index]
    items.value[index] = items.value[index - 1]
    items.value[index - 1] = temp
    await saveItemOrder()
  }
}

// Move item down
const moveItemDown = async (index) => {
  if (index < items.value.length - 1) {
    const temp = items.value[index]
    items.value[index] = items.value[index + 1]
    items.value[index + 1] = temp
    await saveItemOrder()
  }
}

// Save item order to backend
const saveItemOrder = async () => {
  const reorderData = items.value.map((item, index) => ({
    id: item.id,
    order_index: index
  }))
  
  try {
    await axios.put(`/api/lists/${listId.value}/items/reorder`, { items: reorderData })
    items.value.forEach((item, index) => {
      item.order_index = index
    })
    showSuccess('Success', 'Order updated')
  } catch (error) {
    console.error('Error saving order:', error)
    showError('Error', 'Failed to save order')
    await fetchList() // Revert on error
  }
}

// Reordering (for draggable - keeping for future use)
const handleReorder = async () => {
  await saveItemOrder()
}

const handleReorderSections = async () => {
  const reorderData = sections.value.map((section, index) => ({
    id: section.id,
    order_index: index
  }))
  
  try {
    await axios.put(`/api/lists/${listId.value}/sections/reorder`, { sections: reorderData })
    showSuccess('Success', 'Sections reordered')
  } catch (error) {
    showError('Error', 'Failed to save section order')
    await fetchList()
  }
}

const handleReorderItems = async () => {
  await handleReorder()
}

// Handle item image upload
const handleItemImageUpload = (image) => {
  editForm.item_image = image.cloudflare_id
  editForm.item_image_url = image.urls.original
}

// Cover Image handlers
const handleCoverImageUploaded = async (imageData) => {
  // Handle the comprehensive upload data structure
  const cloudflareId = imageData.cloudflare_id
  const imageUrl = imageData.urls?.original || imageData.urls?.public || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${cloudflareId}/public`
  const thumbnailUrl = imageData.urls?.thumbnail || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${cloudflareId}/thumbnail`
  
  listForm.featured_image_cloudflare_id = cloudflareId
  listForm.featured_image_url = imageUrl
  listForm.featured_image = thumbnailUrl
  
  // Save to backend
  await updateField('featured_image_cloudflare_id', cloudflareId)
  showSuccess('Success', 'Cover image uploaded')
}

const removeCoverImage = async () => {
  listForm.featured_image = null
  listForm.featured_image_url = null
  listForm.featured_image_cloudflare_id = null
  
  await updateField('featured_image_cloudflare_id', null)
  showSuccess('Success', 'Cover image removed')
}

// Utility
const truncate = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const truncateHtml = (html, length) => {
  if (!html) return ''
  // Strip HTML tags for truncation
  const stripped = html.replace(/<[^>]*>/g, '')
  if (stripped.length <= length) return html
  // Return truncated plain text (we'll lose formatting for truncated content)
  return stripped.substring(0, length) + '...'
}

// Watch for title changes to recalculate height
watch(() => listForm.name, () => {
  nextTick(() => {
    if (titleContainer.value) {
      titleHeight.value = titleContainer.value.offsetHeight
    }
  })
})

// Lifecycle
onMounted(() => {
  if (!listId.value) {
    showError('Error', 'No list ID provided')
    router.push('/mylists')
    return
  }
  
  fetchList()
  fetchCategories()
  fetchChannels()
  
  // Set up resize observer for title container
  nextTick(() => {
    if (titleContainer.value) {
      const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
          titleHeight.value = entry.contentRect.height
        }
      })
      resizeObserver.observe(titleContainer.value)
      
      // Cleanup on unmount
      onUnmounted(() => {
        resizeObserver.disconnect()
      })
    }
  })
})
</script>

<style scoped>
/* Keep minimal styles - rely on Tailwind */
.prose {
  max-width: none;
}

/* Elegant typography for list description - same as Show.vue */
.list-description {
  @apply text-gray-800;
  line-height: 1.8;
  font-size: 1.125rem;
}

/* Headings */
.list-description :deep(h2) {
  @apply text-2xl font-bold text-gray-900 mt-8 mb-4;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.list-description :deep(h3) {
  @apply text-xl font-semibold text-gray-900 mt-6 mb-3;
  letter-spacing: -0.01em;
  line-height: 1.4;
}

.list-description :deep(h4) {
  @apply text-lg font-semibold text-gray-800 mt-5 mb-2;
  line-height: 1.4;
}

/* Paragraphs */
.list-description :deep(p) {
  @apply mb-4 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
}

.list-description :deep(p:first-child) {
  @apply mt-0;
}

.list-description :deep(p:last-child) {
  @apply mb-0;
}

/* Strong text */
.list-description :deep(strong) {
  @apply font-semibold text-gray-900;
  letter-spacing: -0.01em;
}

/* Lists */
.list-description :deep(ul) {
  @apply my-4 ml-0 list-none;
}

.list-description :deep(ul li) {
  @apply relative pl-7 mb-2 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
}

.list-description :deep(ul li:before) {
  content: "•";
  @apply absolute left-0 text-gray-400;
  font-size: 1.25rem;
  line-height: 1.2;
  top: -0.05em;
}

.list-description :deep(ol) {
  @apply my-4 ml-0 list-none;
  counter-reset: list-counter;
}

.list-description :deep(ol li) {
  @apply relative pl-8 mb-3 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
  counter-increment: list-counter;
}

.list-description :deep(ol li:before) {
  content: counter(list-counter) ".";
  @apply absolute left-0 font-medium text-gray-500;
  top: 0;
}

/* Horizontal rule */
.list-description :deep(hr) {
  @apply my-8 border-0 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent;
}

/* Links */
.list-description :deep(a) {
  @apply text-blue-600 underline decoration-blue-200 hover:decoration-blue-600 transition-colors;
}

/* Blockquotes */
.list-description :deep(blockquote) {
  @apply pl-6 my-6 border-l-4 border-gray-300 italic text-gray-700;
  font-size: 1.125rem;
  line-height: 1.75;
}

/* Code */
.list-description :deep(code) {
  @apply px-1.5 py-0.5 bg-gray-100 text-gray-800 rounded text-sm;
  font-family: 'SF Mono', Monaco, Inconsolata, 'Fira Code', monospace;
}

/* Tables */
.list-description :deep(table) {
  @apply w-full my-6 border-collapse;
}

.list-description :deep(th) {
  @apply text-left font-semibold text-gray-900 border-b-2 border-gray-300 pb-2 px-3;
}

.list-description :deep(td) {
  @apply border-b border-gray-200 py-2 px-3 text-gray-700;
}

/* List transition animations */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.list-leave-active {
  position: absolute;
}
</style>