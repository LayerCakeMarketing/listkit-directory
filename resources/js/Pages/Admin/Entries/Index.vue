<template>
    <Head title="Directory Entries" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Directory Entries Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold">{{ stats.total_entries }}</div>
                        <div class="text-gray-600">Total Entries</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold text-green-600">{{ stats.published }}</div>
                        <div class="text-gray-600">Published</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_review }}</div>
                        <div class="text-gray-600">Pending Review</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.featured }}</div>
                        <div class="text-gray-600">Featured</div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Manage Entries</h3>
                        <button
                            @click="showCreateModal = true"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add New Entry
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input
                            v-model="filters.search"
                            @input="debouncedFetch"
                            type="text"
                            placeholder="Search entries..."
                            class="rounded-md border-gray-300"
                        />
                        <select v-model="filters.type" @change="fetchEntries" class="rounded-md border-gray-300">
                            <option value="">All Types</option>
                            <option value="physical_location">Physical Location</option>
                            <option value="online_business">Online Business</option>
                            <option value="service">Service</option>
                            <option value="event">Event</option>
                            <option value="resource">Resource</option>
                        </select>
                        <select v-model="filters.status" @change="fetchEntries" class="rounded-md border-gray-300">
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="archived">Archived</option>
                        </select>
                        <select v-model="filters.category_id" @change="fetchEntries" class="rounded-md border-gray-300">
                            <option value="">All Categories</option>
                            <optgroup v-for="parent in categories" :key="parent.id" :label="parent.name">
                                <option v-for="child in parent.children" :key="child.id" :value="child.id">
                                    {{ child.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Entries Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedEntries.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedEntries.length }} entries selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkAction('publish')"
                                        class="text-sm bg-green-500 hover:bg-green-700 text-white px-3 py-1 rounded"
                                    >
                                        Publish
                                    </button>
                                    <button
                                        @click="bulkAction('archive')"
                                        class="text-sm bg-yellow-500 hover:bg-yellow-700 text-white px-3 py-1 rounded"
                                    >
                                        Archive
                                    </button>
                                    <button
                                        @click="bulkAction('delete')"
                                        class="text-sm bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="loading" class="text-center py-4">Loading...</div>
                        <div v-else-if="entries.data.length === 0" class="text-center py-4 text-gray-500">
                            No entries found
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="isAllSelected"
                                                class="rounded border-gray-300"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="entry.id"
                                                v-model="selectedEntries"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ entry.title }}</div>
                                                <div class="text-sm text-gray-500">{{ truncate(entry.description, 50) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="getTypeBadgeClass(entry.type)">
                                                {{ formatType(entry.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ entry.category?.name }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="getStatusBadgeClass(entry.status)">
                                                {{ entry.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div v-if="entry.location">
                                                {{ entry.location.city }}, {{ entry.location.state }}
                                            </div>
                                            <div v-else class="text-gray-400">N/A</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="editEntry(entry)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteEntry(entry)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4" v-if="entries.meta">
                            <Pagination 
                                :links="{
                                    ...entries.meta,
                                    links: entries.links,
                                    from: entries.meta?.from || 0,
                                    to: entries.meta?.to || 0,
                                    total: entries.meta?.total || 0
                                }" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal" :max-width="'4xl'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingEntry ? 'Edit Entry' : 'Create New Entry' }}
                </h3>

                <form @submit.prevent="saveEntry">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Basic Information -->
                        <div class="col-span-2">
                            <h4 class="text-md font-semibold mb-2">Basic Information</h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select
                                v-model="form.type"
                                @change="onTypeChange"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                                <option value="">Select Type</option>
                                <option value="physical_location">Physical Location</option>
                                <option value="online_business">Online Business</option>
                                <option value="service">Service</option>
                                <option value="event">Event</option>
                                <option value="resource">Resource</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category *</label>
                            <select
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                                <option value="">Select Category</option>
                                <optgroup v-for="parent in categories" :key="parent.id" :label="parent.name">
                                    <option v-for="child in parent.children" :key="child.id" :value="child.id">
                                        {{ child.name }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            >
                                <option value="draft">Draft</option>
                                <option value="pending_review">Pending Review</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            ></textarea>
                        </div>

                        <!-- Contact Information -->
                        <div class="col-span-2 mt-4">
                            <h4 class="text-md font-semibold mb-2">Contact Information</h4>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Website</label>
                            <input
                                v-model="form.website_url"
                                type="url"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            />
                        </div>

                        <!-- Location Information (for physical locations) -->
                        <template v-if="showLocationFields">
                            <div class="col-span-2 mt-4">
                                <h4 class="text-md font-semibold mb-2">Location Information</h4>
                            </div>

                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Address *</label>
                                <input
                                    v-model="form.location.address_line1"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">City *</label>
                                <input
                                    v-model="form.location.city"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">State *</label>
                                <input
                                    v-model="form.location.state"
                                    type="text"
                                    maxlength="2"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">ZIP Code *</label>
                                <input
                                    v-model="form.location.zip_code"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                <input
                                    v-model="form.location.country"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Latitude *</label>
                                <input
                                    v-model="form.location.latitude"
                                    type="number"
                                    step="any"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Longitude *</label>
                                <input
                                    v-model="form.location.longitude"
                                    type="number"
                                    step="any"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    :required="showLocationFields"
                                />
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const axios = window.axios

// Data
const entries = ref({ data: [], links: [], meta: {} })
const categories = ref([])
const stats = ref({
    total_entries: 0,
    published: 0,
    pending_review: 0,
    featured: 0,
})
const filters = reactive({
    search: '',
    type: '',
    category_id: '',
    status: '',
})

// UI State
const loading = ref(false)
const processing = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedEntries = ref([])
const editingEntry = ref(null)

// Forms
const form = reactive({
    title: '',
    description: '',
    type: '',
    category_id: '',
    status: 'draft',
    phone: '',
    email: '',
    website_url: '',
    tags: [],
    social_links: {},
    location: {
        address_line1: '',
        city: '',
        state: '',
        zip_code: '',
        country: 'USA',
        latitude: '',
        longitude: '',
        hours_of_operation: {},
        amenities: [],
    }
})

// Computed
const isAllSelected = computed(() => {
    return entries.value.data.length > 0 && 
           selectedEntries.value.length === entries.value.data.length
})

const showLocationFields = computed(() => {
    return ['physical_location', 'event'].includes(form.type)
})

// Methods
const fetchEntries = async () => {
    loading.value = true
    try {
        const response = await axios.get('/admin-data/entries', { params: filters })
        entries.value = {
            data: response.data.data,
            links: response.data.links,
            meta: {
                from: response.data.from,
                to: response.data.to,
                total: response.data.total,
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
            }
        }
    } catch (error) {
        console.error('Error fetching entries:', error)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/entries/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const fetchCategories = async () => {
    try {
        const response = await axios.get('/admin-data/categories')
        console.log('Categories response:', response.data) // Debug line
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching categories:', error)
        alert('Failed to load categories. Please refresh the page.')
    }
}

const debouncedFetch = debounce(fetchEntries, 300)

const editEntry = (entry) => {
    editingEntry.value = entry
    Object.assign(form, {
        title: entry.title,
        description: entry.description || '',
        type: entry.type,
        category_id: entry.category_id,
        status: entry.status,
        phone: entry.phone || '',
        email: entry.email || '',
        website_url: entry.website_url || '',
        tags: entry.tags || [],
        social_links: entry.social_links || {},
    })
    
    if (entry.location) {
        Object.assign(form.location, {
            address_line1: entry.location.address_line1 || '',
            city: entry.location.city || '',
            state: entry.location.state || '',
            zip_code: entry.location.zip_code || '',
            country: entry.location.country || 'USA',
            latitude: entry.location.latitude || '',
            longitude: entry.location.longitude || '',
            hours_of_operation: entry.location.hours_of_operation || {},
            amenities: entry.location.amenities || [],
        })
    }
    
    showEditModal.value = true
}

const saveEntry = async () => {
    processing.value = true
    try {
        const data = { ...form }
        
        // Only include location if it's a physical location
        if (!showLocationFields.value) {
            delete data.location
        }
        
        if (editingEntry.value) {
            await axios.put(`/admin-data/entries/${editingEntry.value.id}`, data)
            alert('Entry updated successfully')
        } else {
            await axios.post('/admin-data/entries', data)
            alert('Entry created successfully')
        }
        
        closeModal()
        fetchEntries()
        fetchStats()
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat()
            alert('Error: ' + errors.join(', '))
        } else {
            alert('Error saving entry: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const deleteEntry = async (entry) => {
    if (!confirm(`Are you sure you want to delete "${entry.title}"?`)) return
    
    try {
        await axios.delete(`/admin-data/entries/${entry.id}`)
        fetchEntries()
        fetchStats()
        alert('Entry deleted successfully')
    } catch (error) {
        alert('Error deleting entry: ' + error.response?.data?.message)
    }
}

const bulkAction = async (action) => {
    if (!confirm(`Are you sure you want to ${action} ${selectedEntries.value.length} entries?`)) return
    
    try {
        await axios.post('/admin-data/entries/bulk-update', {
            entry_ids: selectedEntries.value,
            action: action
        })
        
        selectedEntries.value = []
        fetchEntries()
        fetchStats()
        alert(`Entries ${action}ed successfully`)
    } catch (error) {
        alert('Error performing bulk action: ' + error.response?.data?.message)
    }
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    editingEntry.value = null
    
    // Reset form
    Object.assign(form, {
        title: '',
        description: '',
        type: '',
        category_id: '',
        status: 'draft',
        phone: '',
        email: '',
        website_url: '',
        tags: [],
        social_links: {},
        location: {
            address_line1: '',
            city: '',
            state: '',
            zip_code: '',
            country: 'USA',
            latitude: '',
            longitude: '',
            hours_of_operation: {},
            amenities: [],
        }
    })
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedEntries.value = []
    } else {
        selectedEntries.value = entries.value.data.map(e => e.id)
    }
}

const onTypeChange = () => {
    // Clear location data if switching to non-physical type
    if (!showLocationFields.value) {
        Object.keys(form.location).forEach(key => {
            if (typeof form.location[key] === 'string') {
                form.location[key] = ''
            } else if (Array.isArray(form.location[key])) {
                form.location[key] = []
            } else {
                form.location[key] = {}
            }
        })
    }
}

// Helper methods
const getTypeBadgeClass = (type) => {
    const classes = {
        physical_location: 'bg-blue-100 text-blue-800',
        online_business: 'bg-purple-100 text-purple-800',
        service: 'bg-green-100 text-green-800',
        event: 'bg-yellow-100 text-yellow-800',
        resource: 'bg-gray-100 text-gray-800',
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        published: 'bg-green-100 text-green-800',
        draft: 'bg-gray-100 text-gray-800',
        pending_review: 'bg-yellow-100 text-yellow-800',
        archived: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatType = (type) => {
    return type.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

// Lifecycle
onMounted(() => {
    fetchEntries()
    fetchStats()
    fetchCategories()
})
</script>