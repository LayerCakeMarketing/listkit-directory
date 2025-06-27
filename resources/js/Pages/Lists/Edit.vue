<template>
    <Head :title="list.name ? `Edit ${list.name}` : 'Edit List'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit List: {{ list.name || 'Loading...' }}
                </h2>
                <Link
                    :href="route('lists.my')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    Back to Lists
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div v-if="loading" class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-center">
                <p class="text-gray-500">Loading list...</p>
            </div>
            <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- List Settings -->
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-4">List Settings</h3>
                            
                            <form @submit.prevent="updateList" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input
                                        v-model="listForm.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea
                                        v-model="listForm.description"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input
                                            v-model="listForm.is_public"
                                            type="checkbox"
                                            class="rounded border-gray-300"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Make this list public</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Featured Image URL</label>
                                    <input
                                        v-model="listForm.featured_image"
                                        type="url"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                        placeholder="https://example.com/image.jpg"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    :disabled="savingList"
                                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    {{ savingList ? 'Saving...' : 'Save Settings' }}
                                </button>
                            </form>
                        </div>

                        <!-- Add New Item -->
                        <div class="bg-white p-6 rounded-lg shadow mt-6">
                            <h3 class="text-lg font-semibold mb-4">Add Item</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Type</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-for="type in itemTypes"
                                            :key="type.value"
                                            @click="selectedItemType = type.value"
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

                                <!-- Directory Entry Search -->
                                <div v-if="selectedItemType === 'directory_entry'">
                                    <input
                                        v-model="entrySearch"
                                        @input="debouncedSearchEntries"
                                        type="text"
                                        placeholder="Search directory entries..."
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <div v-if="searchResults.length > 0" class="mt-2 max-h-60 overflow-y-auto border rounded">
                                        <button
                                            v-for="entry in searchResults"
                                            :key="entry.id"
                                            @click="addDirectoryEntry(entry)"
                                            class="w-full text-left p-2 hover:bg-gray-100 border-b last:border-b-0"
                                        >
                                            <div class="font-medium">{{ entry.title }}</div>
                                            <div class="text-sm text-gray-500">{{ entry.category?.name }}</div>
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
                                        @click="addEventItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Event
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List Items -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 border-b">
                                <h3 class="text-lg font-semibold">List Items</h3>
                                <p class="text-sm text-gray-500 mt-1">Drag to reorder items</p>
                            </div>

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
                                <template #item="{ element, index }">
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
                                                            @click="editItem(element)"
                                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
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
                        <textarea
                            v-model="editForm.content"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        ></textarea>
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
                        <textarea
                            v-model="editForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            placeholder="Personal notes about this item..."
                        ></textarea>
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
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'
import draggable from 'vuedraggable'
import { debounce } from 'lodash'

const axios = window.axios

const props = defineProps({
    listId: [String, Number]
})

// Data
const list = ref({})
const items = ref([])
const loading = ref(false)
const savingList = ref(false)
const savingItem = ref(false)
const showEditModal = ref(false)
const editingItem = ref(null)
const selectedItemType = ref('directory_entry')
const entrySearch = ref('')
const searchResults = ref([])

const listForm = reactive({
    name: '',
    description: '',
    is_public: true,
    featured_image: ''
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
    notes: ''
})

const itemTypes = [
    { value: 'directory_entry', label: 'Directory Entry' },
    { value: 'text', label: 'Text' },
    { value: 'location', label: 'Location' },
    { value: 'event', label: 'Event' }
]

// Methods
const fetchList = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/data/lists/${props.listId}`)
        list.value = response.data
        items.value = response.data.items || []
        
        // Update form
        Object.assign(listForm, {
            name: response.data.name,
            description: response.data.description || '',
            is_public: response.data.is_public,
            featured_image: response.data.featured_image || ''
        })
    } catch (error) {
        console.error('Error fetching list:', error)
        alert('Error loading list')
    } finally {
        loading.value = false
    }
}

const updateList = async () => {
    savingList.value = true
    try {
        await axios.put(`/data/lists/${props.listId}`, listForm)
        alert('List settings saved successfully')
    } catch (error) {
        alert('Error saving list: ' + error.response?.data?.message)
    } finally {
        savingList.value = false
    }
}

const searchEntries = async () => {
    if (!entrySearch.value) {
        searchResults.value = []
        return
    }
    
    try {
        const response = await axios.get('/data/directory-entries/search', {
            params: { q: entrySearch.value }
        })
        searchResults.value = response.data
    } catch (error) {
        console.error('Error searching entries:', error)
    }
}

const debouncedSearchEntries = debounce(searchEntries, 300)

const addDirectoryEntry = async (entry) => {
    try {
        const response = await axios.post(`/data/lists/${props.listId}/items`, {
            type: 'directory_entry',
            directory_entry_id: entry.id
        })
        items.value.push(response.data.item)
        entrySearch.value = ''
        searchResults.value = []
    } catch (error) {
        alert('Error adding entry: ' + error.response?.data?.message)
    }
}

const addTextItem = async () => {
    if (!newItem.title) {
        alert('Please enter a title')
        return
    }
    
    try {
        const response = await axios.post(`/data/lists/${props.listId}/items`, {
            type: 'text',
            title: newItem.title,
            content: newItem.content
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.content = ''
    } catch (error) {
        alert('Error adding text: ' + error.response?.data?.message)
    }
}

const addLocationItem = async () => {
    if (!newItem.title || !newItem.data.latitude || !newItem.data.longitude) {
        alert('Please fill in all location fields')
        return
    }
    
    try {
        const response = await axios.post(`/data/lists/${props.listId}/items`, {
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
        alert('Error adding location: ' + error.response?.data?.message)
    }
}

const addEventItem = async () => {
    if (!newItem.title || !newItem.data.start_date) {
        alert('Please enter event name and start date')
        return
    }
    
    try {
        const response = await axios.post(`/data/lists/${props.listId}/items`, {
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
        alert('Error adding event: ' + error.response?.data?.message)
    }
}

const editItem = (item) => {
    editingItem.value = item
    Object.assign(editForm, {
        title: item.title || '',
        content: item.content || '',
        data: item.data || {},
        affiliate_url: item.affiliate_url || '',
        notes: item.notes || ''
    })
    showEditModal.value = true
}

const updateItem = async () => {
    savingItem.value = true
    try {
        await axios.put(`/data/lists/${props.listId}/items/${editingItem.value.id}`, editForm)
        
        // Update local item
        const index = items.value.findIndex(i => i.id === editingItem.value.id)
        if (index !== -1) {
            Object.assign(items.value[index], editForm)
        }
        
        closeEditModal()
    } catch (error) {
        alert('Error updating item: ' + error.response?.data?.message)
    } finally {
        savingItem.value = false
    }
}

const removeItem = async (item) => {
    if (!confirm('Are you sure you want to remove this item?')) return
    
    try {
        await axios.delete(`/data/lists/${props.listId}/items/${item.id}`)
        items.value = items.value.filter(i => i.id !== item.id)
    } catch (error) {
        alert('Error removing item: ' + error.response?.data?.message)
    }
}

const handleReorder = async () => {
    const reorderData = items.value.map((item, index) => ({
        id: item.id,
        order_index: index
    }))
    
    console.log('Reordering items:', reorderData)
    
    try {
        const response = await axios.put(`/data/lists/${props.listId}/items/reorder`, { items: reorderData })
        console.log('Reorder successful:', response.data)
        
        // Update local order_index values to match what was sent
        items.value.forEach((item, index) => {
            item.order_index = index
        })
    } catch (error) {
        console.error('Error reordering items:', error)
        alert('Error saving order: ' + (error.response?.data?.message || error.message))
        // Revert on error
        fetchList()
    }
}

const closeEditModal = () => {
    showEditModal.value = false
    editingItem.value = null
    Object.keys(editForm).forEach(key => {
        editForm[key] = key === 'data' ? {} : ''
    })
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString()
}

// Lifecycle
onMounted(() => {
    fetchList()
})
</script>