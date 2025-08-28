<template>
    <div class="min-h-screen bg-gray-100">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-16">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ list.name && list.name.startsWith('Untitled List') ? 'New List' : `Edit List: ${list.name || 'Loading...'}` }}
                    </h2>
                    <div class="flex items-center space-x-4">
                        <button
                            v-if="hasSaved"
                            @click="previewList"
                            type="button"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Preview
                        </button>
                        <button
                            @click="updateList"
                            :disabled="savingList"
                            type="button"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ savingList ? 'Saving...' : 'Save' }}
                        </button>
                        <button
                            @click="settingsOpen = true"
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md"
                            title="List Settings"
                        >
                            <Cog6ToothIcon class="h-5 w-5" />
                        </button>
                        <router-link
                            :to="backLink"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            {{ backLinkText }}
                        </router-link>
                    </div>
                </div>

                <div v-if="loading" class="text-center">
                    <p class="text-gray-500">Loading list...</p>
                </div>
                <div v-else>
                    <!-- On Hold Warning -->
                    <div v-if="list.status === 'on_hold'" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">This list is on hold</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>This list has been put on hold by an administrator. It is not visible to the public.</p>
                                    <p v-if="list.status_reason" class="mt-1">Reason: {{ list.status_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Settings Drawer (Left-sliding) -->
                    <ListSettingsDrawer
                        :open="settingsOpen"
                        :form="listForm"
                        :channels="userChannels"
                        :photos="photos"
                        :existing-photos="existingPhotos"
                        :list-id="listId"
                        :shares="shares"
                        :saving="savingList"
                        @close="settingsOpen = false"
                        @save="updateList"
                        @photo-upload="handlePhotoUpload"
                        @upload-error="handleUploadError"
                        @gallery-remove="handleGalleryRemove"
                        @image-reorder="updateImageOrder"
                        @existing-gallery-remove="handleExistingGalleryRemove"
                        @existing-image-reorder="updateExistingImageOrder"
                        @share-added="handleShareAdded"
                        @share-removed="handleShareRemoved"
                    />

                    <!-- List Items (full width when drawer is closed) -->
                    <div class="flex flex-col gap-6">
                        <!-- Add New Item -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Add Item</h3>
                                <button
                                    @click="showAddSection = true"
                                    type="button"
                                    class="flex items-center gap-2 px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm font-medium"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    Add Section
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Type</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-for="type in itemTypes"
                                            :key="type.value"
                                            type="button"
                                            @click="handleItemTypeClick(type.value)"
                                            :class="[
                                                'p-2 text-sm rounded border',
                                                selectedItemType === type.value
                                                    ? 'bg-blue-500 text-white border-blue-500'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            ]"
                                        >
                                            {{ type.label }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Text Item -->
                                <div v-if="selectedItemType === 'text'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Title"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <textarea
                                        v-model="newItem.content"
                                        rows="3"
                                        placeholder="Content"
                                        class="w-full rounded-md border-gray-300"
                                    ></textarea>
                                    <button
                                        type="button"
                                        @click="addTextItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Text
                                    </button>
                                </div>
                                <!-- Location Item -->
                                <div v-if="selectedItemType === 'location'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Location name"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <input
                                        v-model="newItem.data.address"
                                        type="text"
                                        placeholder="Address"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model.number="newItem.data.latitude"
                                            type="number"
                                            step="any"
                                            placeholder="Latitude"
                                            class="rounded-md border-gray-300"
                                        />
                                        <input
                                            v-model.number="newItem.data.longitude"
                                            type="number"
                                            step="any"
                                            placeholder="Longitude"
                                            class="rounded-md border-gray-300"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="addLocationItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Location
                                    </button>
                                </div>
                                <!-- Event Item -->
                                <div v-if="selectedItemType === 'event'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Event name"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <textarea
                                        v-model="newItem.content"
                                        rows="2"
                                        placeholder="Description"
                                        class="w-full rounded-md border-gray-300"
                                    ></textarea>
                                    <input
                                        v-model="newItem.data.start_date"
                                        type="datetime-local"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <input
                                        v-model="newItem.data.location"
                                        type="text"
                                        placeholder="Event location"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <button
                                        type="button"
                                        @click="addEventItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Event
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- List Items with Sections -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 border-b">
                                <h3 class="text-lg font-semibold">List Items</h3>
                                <p class="text-sm text-gray-500 mt-1">Drag to reorder items and sections</p>
                            </div>
                            
                            <!-- If using sections -->
                            <div v-if="list.structure_version === '2.0' && sections.length > 0" class="p-6">
                                <draggable
                                    v-model="sections"
                                    @end="handleSectionReorder"
                                    item-key="id"
                                    handle=".drag-handle"
                                    class="space-y-4"
                                >
                                    <template #item="{ element: section }">
                                        <ListSection
                                            :section="section"
                                            :items="getSectionItems(section.id)"
                                            @update-heading="updateSectionHeading(section.id, $event)"
                                            @reorder="handleItemReorder(section.id, $event)"
                                            @remove="removeSection(section.id)"
                                            @edit-item="editItem"
                                            @remove-item="removeItem"
                                        />
                                    </template>
                                </draggable>
                            </div>
                            
                            <!-- Legacy flat list -->
                            <div v-else>
                                <div v-if="items.length === 0" class="p-6 text-center text-gray-500">
                                    No items yet. Add some content to your list!
                                </div>
                                <draggable
                                    v-else
                                    v-model="items"
                                    @end="handleReorder"
                                    item-key="id"
                                    class="divide-y"
                                    handle=".drag-handle"
                                >
                                    <template #item="{ element }">
                                        <div class="p-4 hover:bg-gray-50 cursor-move">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 mt-1 drag-handle cursor-grab">
                                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <h4 class="font-medium text-gray-900">
                                                            {{ element.display_title || element.title }}
                                                        </h4>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                                {{ element.type }}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                @click="editItem(element)"
                                                                class="text-blue-600 hover:text-blue-800 text-sm"
                                                            >
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="removeItem(element)"
                                                                class="text-red-600 hover:text-red-800 text-sm"
                                                            >
                                                                Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <p v-if="element.display_content || element.content" class="text-sm text-gray-600 mt-1">
                                                        {{ truncate(element.display_content || element.content, 150) }}
                                                    </p>
                                                    <div v-if="element.type === 'location' && element.data" class="text-sm text-gray-500 mt-1">
                                                        📍 {{ element.data.address }}
                                                    </div>
                                                    <div v-if="element.type === 'event' && element.data" class="text-sm text-gray-500 mt-1">
                                                        📅 {{ formatDate(element.data.start_date) }}
                                                        <span v-if="element.data.location"> @ {{ element.data.location }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <Modal :show="showEditModal" @close="closeEditModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Edit Item
                </h3>
                <form @submit.prevent="updateItem" class="space-y-4">
                    <div v-if="editingItem.type !== 'directory_entry'">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        />
                    </div>
                    <div v-if="['text', 'event'].includes(editingItem.type)">
                        <label class="block text-sm font-medium text-gray-700">Content</label>
                        <RichTextEditor
                            v-model="editForm.content"
                            placeholder="Enter item content..."
                            :max-height="300"
                        />
                    </div>
                    <div v-if="editingItem.type === 'directory_entry' || editForm.affiliate_url !== undefined">
                        <label class="block text-sm font-medium text-gray-700">Affiliate URL</label>
                        <input
                            v-model="editForm.affiliate_url"
                            type="url"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            placeholder="https://example.com/affiliate"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <RichTextEditor
                            v-model="editForm.notes"
                            placeholder="Personal notes about this item..."
                            :max-height="150"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Image</label>
                        <DirectCloudflareUpload
                            v-model="itemImage"
                            label="Upload Image for this Item"
                            upload-type="list_image"
                            :entity-id="editingItem?.id || 1"
                            :max-size-m-b="14"
                            :current-image-url="editForm.item_image_url"
                            @upload-complete="handleItemImageUpload"
                        />
                        <p class="text-xs text-gray-500 mt-1">Add an image to make this list item more visually appealing</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="savingItem"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ savingItem ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
        
        <!-- Saved Items Modal -->
        <SavedItemsModal
            :show="showSavedItems"
            @close="showSavedItems = false"
            @add-items="handleAddSavedItems"
        />
        
        <!-- Add Section Modal -->
        <Modal :show="showAddSection" @close="showAddSection = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Add New Section
                </h3>
                <form @submit.prevent="createSection" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Section Heading</label>
                        <input
                            v-model="newSectionHeading"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            placeholder="e.g., Day 1, Best Restaurants, Must-See Places..."
                            required
                            autofocus
                        />
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showAddSection = false"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add Section
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import DirectCloudflareUpload from '@/components/image-upload/DirectCloudflareUpload.vue'
import CategorySelect from '@/components/ui/CategorySelect.vue'
import TagInput from '@/components/ui/TagInput.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import draggable from 'vuedraggable'
import { debounce } from 'lodash'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon, Cog6ToothIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { useNotification } from '@/composables/useNotification'
import { useToast } from '@/composables/useToast'
import ListSettingsDrawer from '@/components/lists/ListSettingsDrawer.vue'
import SavedItemsModal from '@/components/lists/SavedItemsModal.vue'
import ListSection from '@/components/lists/ListSection.vue'

// Vue Router
const route = useRoute()
const router = useRouter()
const { showSuccess, showError } = useNotification()
const { success: toastSuccess, error: toastError } = useToast()

// Get listId from route params
const listId = ref(route.params.id)

// Update page title
const updateTitle = (title) => {
    document.title = title ? `${title} - Edit List` : 'Edit List'
}

// Data
const list = ref({})
const items = ref([])
const loading = ref(false)
const savingList = ref(false)
const savingItem = ref(false)
const showEditModal = ref(false)
const editingItem = ref(null)
const selectedItemType = ref('text')
const entrySearch = ref('')
const searchResults = ref([])

// Sharing state
const shares = ref([])
const shareSearch = ref('')
const userSearchResults = ref([])
const selectedUser = ref(null)
const sharing = ref(false)

// Image upload state
const itemImage = ref(null)
const photos = ref([])
const existingPhotos = ref([])

// Drawer state
const settingsOpen = ref(false)
const hasSaved = ref(false)

// Saved items state
const showSavedItems = ref(false)
const savedItems = ref([])
const userChannels = ref([])

const listForm = reactive({
    name: '',
    description: '',
    visibility: 'public',
    is_draft: false,
    scheduled_for: '',
    featured_image: '',
    featured_image_cloudflare_id: null,
    gallery_images: [],
    category_id: '',
    channel_id: null,
    tags: []
})

const newItem = reactive({
    title: '',
    content: '',
    data: {},
    affiliate_url: '',
    notes: ''
})

const editForm = reactive({
    title: '',
    content: '',
    data: {},
    affiliate_url: '',
    notes: '',
    item_image: null,
    item_image_url: null
})

const shareForm = reactive({
    user_id: null,
    permission: 'view',
    expires_at: ''
})

const itemTypes = [
    { value: 'text', label: 'Text' },
    { value: 'saved', label: 'From Saved' },
    { value: 'location', label: 'Location' },
    { value: 'event', label: 'Event' }
]

// Sections support
const sections = ref([])
const showAddSection = ref(false)
const newSectionHeading = ref('')

// Computed
const backLink = computed(() => {
    // Check if it's a channel list
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        return `/${list.value.channel.slug}`
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        return `/${list.value.channel.slug}`
    }
    // Default to user lists
    return '/mylists'
})

const backLinkText = computed(() => {
    // Check if it's a channel list
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        return 'Back to Channel'
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        return 'Back to Channel'
    }
    // Default to user lists
    return 'Back to My Lists'
})


// Methods
const fetchList = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/api/lists/${listId.value}`)
        list.value = response.data
        items.value = response.data.items || []
        
        // Update page title
        updateTitle(response.data.name)
        
        // Update form
        Object.assign(listForm, {
            name: response.data.name,
            description: response.data.description || '',
            visibility: response.data.visibility || 'public',
            is_draft: response.data.is_draft || false,
            scheduled_for: response.data.scheduled_for ? new Date(response.data.scheduled_for).toISOString().slice(0, 16) : '',
            featured_image: response.data.featured_image || '',
            featured_image_cloudflare_id: response.data.featured_image_cloudflare_id || null,
            gallery_images: response.data.gallery_images || [],
            category_id: response.data.category_id || '',
            channel_id: response.data.channel_id || null,
            tags: response.data.tags || []
        })
        
        // Populate existing photos for display
        if (response.data.gallery_images && response.data.gallery_images.length > 0) {
            existingPhotos.value = response.data.gallery_images.map((image, index) => ({
                id: image.id || `existing-${index}`,
                url: image.url,
                filename: image.filename || `Photo ${index + 1}`
            }))
        }
        
        // Fetch shares if private list
        if (response.data.visibility === 'private') {
            fetchShares()
        }
        
        // Load sections if using v2 structure
        if (response.data.structure_version === '2.0') {
            sections.value = response.data.sections || []
        }
    } catch (error) {
        console.error('Error fetching list:', error)
        if (error.response?.status === 404) {
            showError('Not Found', 'List not found')
            router.push('/lists/my')
        } else if (error.response?.status === 401) {
            showError('Authentication Required', 'Please log in to edit this list')
            router.push('/login')
        } else {
            showError('Error', 'Failed to load list')
        }
    } finally {
        loading.value = false
    }
}

const updateList = async () => {
    // Validate required fields
    if (!listForm.category_id) {
        showError('Validation Error', 'Please select a category for your list.')
        return
    }

    savingList.value = true
    try {
        await axios.put(`/api/lists/${listId.value}`, listForm)
        hasSaved.value = true
        showSuccess('Saved', 'List settings saved successfully')
    } catch (error) {
        showError('Error', error.response?.data?.message || 'Failed to save list settings')
    } finally {
        savingList.value = false
    }
}

const previewList = () => {
    // Open the list in a new tab
    let listUrl = ''
    
    // Check if it's a channel list or user list based on owner_type
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        // Channel list
        listUrl = `/${list.value.channel.slug}/${list.value.slug}`
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        listUrl = `/${list.value.channel.slug}/${list.value.slug}`
    } else {
        // User list
        const user = list.value.user
        const baseUrl = user?.custom_url ? `/up/@${user.custom_url}` : `/up/@${user?.username || user?.id}`
        listUrl = `${baseUrl}/${list.value.slug}`
    }
    
    window.open(listUrl, '_blank')
}

const searchEntries = async () => {
    if (!entrySearch.value) {
        searchResults.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/directory-entries/search', {
            params: { q: entrySearch.value }
        })
        searchResults.value = response.data
    } catch (error) {
        console.error('Error searching entries:', error)
    }
}

const debouncedSearchEntries = debounce(searchEntries, 300)

const convertToSections = async () => {
    try {
        const response = await axios.post(`/api/lists/${listId.value}/convert-to-sections`)
        list.value = response.data.list
        sections.value = response.data.sections || []
        toastSuccess('Success', 'List converted to sections format')
    } catch (error) {
        toastError('Error', 'Failed to convert list format')
    }
}

const getSectionItems = (sectionId) => {
    return items.value.filter(item => item.section_id === sectionId)
}

const createSection = async () => {
    if (!newSectionHeading.value.trim()) return
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/sections`, {
            heading: newSectionHeading.value.trim()
        })
        
        newSectionHeading.value = ''
        showAddSection.value = false
        toastSuccess('Success', 'Section created')
        
        // Refresh the entire list to get proper structure
        await fetchList()
    } catch (error) {
        console.error('Error creating section:', error)
        toastError('Error', error.response?.data?.message || 'Failed to create section')
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
        toastSuccess('Success', 'Section heading updated')
    } catch (error) {
        toastError('Error', 'Failed to update section heading')
    }
}

const removeSection = async (sectionId) => {
    if (!confirm('Remove this section? Items will be moved to the default section.')) return
    
    try {
        await axios.delete(`/api/lists/${listId.value}/sections/${sectionId}`)
        sections.value = sections.value.filter(s => s.id !== sectionId)
        // Move items to default section
        items.value.forEach(item => {
            if (item.section_id === sectionId) {
                item.section_id = 'default-section'
            }
        })
        toastSuccess('Success', 'Section removed')
    } catch (error) {
        toastError('Error', 'Failed to remove section')
    }
}

const handleSectionReorder = async () => {
    // Update order of sections
    try {
        const sectionOrder = sections.value.map((section, index) => ({
            id: section.id,
            order_index: index
        }))
        await axios.put(`/api/lists/${listId.value}/sections/reorder`, {
            sections: sectionOrder
        })
    } catch (error) {
        toastError('Error', 'Failed to reorder sections')
        fetchList() // Revert on error
    }
}

const handleItemReorder = async (sectionId, newItems) => {
    // Update items within a section
    const sectionItems = newItems.map((item, index) => ({
        ...item,
        section_id: sectionId,
        order_index: index
    }))
    
    // Update local state
    items.value = items.value.filter(i => i.section_id !== sectionId).concat(sectionItems)
    
    // Save to server
    await handleReorder()
}

const handleItemTypeClick = (type) => {
    if (type === 'saved') {
        showSavedItems.value = true
    } else {
        selectedItemType.value = type
    }
}

const handleAddSavedItems = async (selectedItems) => {
    try {
        // Format items for the new API structure
        const formattedItems = selectedItems.map(item => ({
            id: item.id,
            type: item.type
        }))
        
        // Get current section ID if using sections
        const currentSectionId = sections.value.length > 0 ? sections.value[0].id : null
        
        const response = await axios.post(`/api/lists/${listId.value}/items/add-saved`, {
            items: formattedItems,
            section_id: currentSectionId
        })
        
        // Add to the items array
        response.data.items.forEach(item => {
            if (currentSectionId) {
                item.section_id = currentSectionId
            }
            items.value.push(item)
        })
        
        showSavedItems.value = false
        toastSuccess('Success', `Added ${response.data.items.length} items`)
    } catch (error) {
        console.error('Error adding saved items:', error)
        toastError('Error', error.response?.data?.message || 'Failed to add saved items')
    }
}

const handleShareAdded = (share) => {
    shares.value.push(share)
}

const handleShareRemoved = (shareId) => {
    shares.value = shares.value.filter(s => s.id !== shareId)
}

const addTextItem = async () => {
    if (!newItem.title) {
        showError('Validation Error', 'Please enter a title')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'text',
            title: newItem.title,
            content: newItem.content
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.content = ''
    } catch (error) {
        showError('Error', error.response?.data?.message || 'Failed to add text item')
    }
}

const addLocationItem = async () => {
    if (!newItem.title || !newItem.data.latitude || !newItem.data.longitude) {
        showError('Validation Error', 'Please fill in all location fields')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'location',
            title: newItem.title,
            data: {
                latitude: newItem.data.latitude,
                longitude: newItem.data.longitude,
                address: newItem.data.address,
                name: newItem.title
            }
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.data = {}
    } catch (error) {
        showError('Error', error.response?.data?.message || 'Failed to add location')
    }
}

const addEventItem = async () => {
    if (!newItem.title || !newItem.data.start_date) {
        showError('Validation Error', 'Please enter event name and start date')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'event',
            title: newItem.title,
            content: newItem.content,
            data: {
                start_date: newItem.data.start_date,
                location: newItem.data.location
            }
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.content = ''
        newItem.data = {}
    } catch (error) {
        showError('Error', error.response?.data?.message || 'Failed to add event')
    }
}

const editItem = (item) => {
    editingItem.value = item
    Object.assign(editForm, {
        title: item.title || '',
        content: item.content || '',
        data: item.data || {},
        affiliate_url: item.affiliate_url || '',
        notes: item.notes || '',
        item_image: item.item_image_cloudflare_id || null,
        item_image_url: item.item_image_url || null
    })
    showEditModal.value = true
}

const updateItem = async () => {
    savingItem.value = true
    try {
        await axios.put(`/api/lists/${listId.value}/items/${editingItem.value.id}`, editForm)
        
        // Update local item
        const index = items.value.findIndex(i => i.id === editingItem.value.id)
        if (index !== -1) {
            Object.assign(items.value[index], editForm)
        }
        
        closeEditModal()
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
    } catch (error) {
        showError('Error', error.response?.data?.message || 'Failed to remove item')
    }
}

const handleReorder = async () => {
    const reorderData = items.value.map((item, index) => ({
        id: item.id,
        order_index: index
    }))
    
    console.log('Reordering items:', reorderData)
    console.log('List ID:', listId.value)
    console.log('Items before reorder:', items.value)
    
    try {
        const response = await axios.put(`/api/lists/${listId.value}/items/reorder`, { items: reorderData })
        console.log('Reorder successful:', response.data)
        
        // Update local order_index values to match what was sent
        items.value.forEach((item, index) => {
            item.order_index = index
        })
        
        showSuccess('Items reordered successfully')
    } catch (error) {
        console.error('Error reordering items:', error)
        console.error('Error response:', error.response?.data)
        console.error('Error status:', error.response?.status)
        toastError('Error', error.response?.data?.message || 'Failed to save order')
        // Revert on error
        fetchList()
    }
}

const closeEditModal = () => {
    showEditModal.value = false
    editingItem.value = null
    itemImage.value = null
    Object.keys(editForm).forEach(key => {
        editForm[key] = key === 'data' ? {} : ''
    })
}

// Handle item image upload
const handleItemImageUpload = (image) => {
    itemImage.value = image
    editForm.item_image = image.cloudflare_id
    editForm.item_image_url = image.urls.original
}

// Handle photo upload
const handlePhotoUpload = (uploadResult) => {
    photos.value.push(uploadResult)
    updateGalleryImages()
    console.log('Photo uploaded:', uploadResult)
}

const handleGalleryRemove = (index, removedImage) => {
    updateGalleryImages()
}

const updateImageOrder = () => {
    updateGalleryImages()
}

const handleExistingGalleryRemove = (index, removedImage) => {
    updateCombinedGalleryImages()
}

const updateExistingImageOrder = () => {
    updateCombinedGalleryImages()
}

const updateGalleryImages = () => {
    // Update gallery_images array with all photos (existing + new)
    const combinedImages = [...existingPhotos.value, ...photos.value]
    
    listForm.gallery_images = combinedImages.map(photo => ({
        id: photo.id,
        url: photo.url,
        filename: photo.filename
    }))
    
    // Set first image as featured_image for backward compatibility
    if (combinedImages.length > 0) {
        listForm.featured_image = combinedImages[0].url
        listForm.featured_image_cloudflare_id = combinedImages[0].id
    } else {
        listForm.featured_image = null
        listForm.featured_image_cloudflare_id = null
    }
    
    console.log('Gallery images updated:', listForm.gallery_images)
}

const updateCombinedGalleryImages = () => {
    updateGalleryImages()
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    showError('Upload Error', error.message || 'Failed to upload image')
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString()
}

// Sharing methods
const fetchShares = async () => {
    if (listForm.visibility !== 'private') return
    
    try {
        const response = await axios.get(`/api/lists/${listId.value}/shares`)
        shares.value = response.data
    } catch (error) {
        console.error('Error fetching shares:', error)
    }
}

const searchUsers = async () => {
    if (!shareSearch.value || shareSearch.value.length < 2) {
        userSearchResults.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/users/search', {
            params: { q: shareSearch.value }
        })
        userSearchResults.value = response.data
    } catch (error) {
        console.error('Error searching users:', error)
    }
}

const debouncedSearchUsers = debounce(searchUsers, 300)

const selectUserToShare = (user) => {
    selectedUser.value = user
    shareForm.user_id = user.id
    shareSearch.value = ''
    userSearchResults.value = []
}

const clearSelectedUser = () => {
    selectedUser.value = null
    shareForm.user_id = null
    shareForm.permission = 'view'
    shareForm.expires_at = ''
}

const shareList = async () => {
    if (!selectedUser.value) return
    
    sharing.value = true
    try {
        const response = await axios.post(`/api/lists/${listId.value}/shares`, shareForm)
        shares.value.push(response.data.share)
        clearSelectedUser()
        showSuccess('Shared', 'List shared successfully!')
    } catch (error) {
        showError('Error', error.response?.data?.error || error.message || 'Failed to share list')
    } finally {
        sharing.value = false
    }
}

const editShare = (share) => {
    // For now, just allow removing and re-adding
    // Could implement inline editing later
    if (confirm(`Edit sharing permissions for ${share.user.name}?\n\nCurrent: ${share.permission} access`)) {
        removeShare(share)
    }
}

const removeShare = async (share) => {
    if (!confirm(`Remove ${share.user.name}'s access to this list?`)) return
    
    try {
        await axios.delete(`/api/lists/${listId.value}/shares/${share.id}`)
        shares.value = shares.value.filter(s => s.id !== share.id)
    } catch (error) {
        showError('Error', error.response?.data?.message || error.message || 'Failed to remove share')
    }
}

// Watch for visibility changes
watch(() => listForm.visibility, (newValue, oldValue) => {
    if (newValue === 'private' && oldValue !== 'private') {
        fetchShares()
    } else if (newValue !== 'private') {
        shares.value = []
    }
})

// Watch for saved items modal
watch(showSavedItems, (newValue) => {
    if (newValue && savedItems.value.length === 0) {
        fetchSavedItems()
    }
})

const fetchSavedItems = async () => {
    try {
        const response = await axios.get('/api/saved')
        savedItems.value = response.data.places || []
    } catch (error) {
        console.error('Failed to fetch saved items:', error)
    }
}

const fetchChannels = async () => {
    try {
        const response = await axios.get('/api/my-channels')
        userChannels.value = response.data
    } catch (error) {
        console.error('Error fetching channels:', error)
    }
}

// Lifecycle
onMounted(() => {
    if (!listId.value) {
        showError('Error', 'No list ID provided')
        router.push('/lists/my')
        return
    }
    fetchList()
    fetchChannels()
})
</script>