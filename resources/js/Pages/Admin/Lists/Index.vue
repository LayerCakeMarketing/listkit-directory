<template>
    <Head title="Lists Management" />
    
    <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lists Management</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">User Lists</h2>
                        <div class="flex space-x-2">
                            <button
                                @click="fetchLists"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Lists</div>
                            <div class="text-2xl font-bold">{{ stats.total_lists }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Public</div>
                            <div class="text-2xl font-bold">{{ stats.public_lists }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Private</div>
                            <div class="text-2xl font-bold">{{ stats.private_lists }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Unlisted</div>
                            <div class="text-2xl font-bold">{{ stats.unlisted_lists }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Featured</div>
                            <div class="text-2xl font-bold">{{ stats.featured_lists }}</div>
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
                                placeholder="List name, description, or user"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Visibility</label>
                            <select
                                v-model="filters.visibility"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Visibility</option>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="unlisted">Unlisted</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="name">List Name</option>
                                <option value="view_count">View Count</option>
                                <option value="user_name">User Name</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select
                                v-model="filters.sort_order"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lists Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedLists.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedLists.length }} list(s) selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="showBulkVisibilityModal = true"
                                        class="text-sm bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded"
                                    >
                                        Change Visibility
                                    </button>
                                    <button
                                        @click="toggleFeatured"
                                        class="text-sm bg-yellow-500 hover:bg-yellow-700 text-white px-3 py-1 rounded"
                                    >
                                        Toggle Featured
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="text-sm bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded"
                                    >
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            List
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Visibility
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Stats
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="list in lists.data" :key="list.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="list.id"
                                                v-model="selectedLists"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <a 
                                                    :href="list.list_url"
                                                    target="_blank"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-900"
                                                >
                                                    {{ list.name }}
                                                </a>
                                                <span v-if="list.is_featured" class="ml-2 text-yellow-500">⭐</span>
                                                <p class="text-xs text-gray-500 mt-1">{{ truncate(list.description, 100) }}</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ list.user.name }}
                                                </div>
                                                <a 
                                                    :href="list.user.profile_url"
                                                    target="_blank"
                                                    class="text-xs text-blue-600 hover:text-blue-900"
                                                >
                                                    @{{ list.user.custom_url || list.user.username }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="getVisibilityBadgeClass(list.visibility)"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ list.visibility }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div>Views: {{ formatNumber(list.view_count) }}</div>
                                            <div>Items: {{ list.items_count }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(list.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="viewList(list)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                View
                                            </button>
                                            <button
                                                @click="editList(list)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteList(list)"
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
                        <div class="mt-4">
                            <Pagination 
                                :links="{
                                    ...lists.meta,
                                    links: lists.links,
                                    from: lists.meta?.from || 0,
                                    to: lists.meta?.to || 0,
                                    total: lists.meta?.total || 0
                                }" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View List Modal -->
        <Modal :show="showViewModal" @close="showViewModal = false" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ viewingList?.name }}
                </h3>
                
                <div v-if="viewingListDetails" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Created by</p>
                            <p class="font-medium">{{ viewingListDetails.user.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Visibility</p>
                            <p class="font-medium capitalize">{{ viewingListDetails.visibility }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Views</p>
                            <p class="font-medium">{{ formatNumber(viewingListDetails.view_count) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Items</p>
                            <p class="font-medium">{{ viewingListDetails.items?.length || 0 }}</p>
                        </div>
                    </div>
                    
                    <div v-if="viewingListDetails.description">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="mt-1">{{ viewingListDetails.description }}</p>
                    </div>
                    
                    <div v-if="viewingListDetails.items?.length > 0" class="border-t pt-4">
                        <h4 class="font-medium mb-2">List Items</h4>
                        <div class="space-y-2">
                            <div 
                                v-for="item in viewingListDetails.items" 
                                :key="item.id"
                                class="flex items-center space-x-3 p-3 bg-gray-50 rounded"
                            >
                                <span class="text-gray-500">{{ item.order_index + 1 }}.</span>
                                <div class="flex-1">
                                    <p class="font-medium">{{ item.entry?.title || 'Deleted Entry' }}</p>
                                    <p v-if="item.notes" class="text-sm text-gray-600">{{ item.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button
                        @click="showViewModal = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Edit List Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Edit List: {{ editingList?.name }}
                </h3>

                <form @submit.prevent="updateList">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Visibility</label>
                            <select
                                v-model="editForm.visibility"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="unlisted">Unlisted</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="editForm.is_featured"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700">Featured List</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Bulk Visibility Modal -->
        <Modal :show="showBulkVisibilityModal" @close="showBulkVisibilityModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Change Visibility for {{ selectedLists.length }} Lists
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Visibility</label>
                    <select
                        v-model="bulkVisibility"
                        class="w-full rounded-md border-gray-300 shadow-sm"
                    >
                        <option value="">Select visibility</option>
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                        <option value="unlisted">Unlisted</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="showBulkVisibilityModal = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Cancel
                    </button>
                    <button
                        @click="bulkUpdateVisibility"
                        :disabled="!bulkVisibility || processing"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                    >
                        Update Visibility
                    </button>
                </div>
            </div>
        </Modal>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
const axios = window.axios
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

// Data
const lists = ref({ 
    data: [], 
    links: [],
    meta: {
        from: 0,
        to: 0,
        total: 0
    }
})
const stats = ref({
    total_lists: 0,
    public_lists: 0,
    private_lists: 0,
    unlisted_lists: 0,
    featured_lists: 0,
    total_views: 0,
})
const filters = reactive({
    search: '',
    visibility: '',
    sort_by: 'created_at',
    sort_order: 'desc',
})

// UI State
const showViewModal = ref(false)
const showEditModal = ref(false)
const showBulkVisibilityModal = ref(false)
const processing = ref(false)
const selectedLists = ref([])
const viewingList = ref(null)
const viewingListDetails = ref(null)
const editingList = ref(null)
const bulkVisibility = ref('')

// Forms
const editForm = reactive({
    name: '',
    description: '',
    visibility: '',
    is_featured: false,
})

// Computed
const isAllSelected = computed(() => {
    return lists.value.data.length > 0 && 
           selectedLists.value.length === lists.value.data.length
})

// Methods
const fetchLists = async () => {
    try {
        const response = await axios.get('/admin-data/lists', { params: filters })
        
        // Handle pagination data structure
        if (response.data && response.data.data) {
            lists.value = {
                data: response.data.data,
                links: response.data.links || [],
                meta: {
                    from: response.data.from || 0,
                    to: response.data.to || 0,
                    total: response.data.total || 0,
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    per_page: response.data.per_page || 20,
                }
            }
        }
    } catch (error) {
        console.error('Error fetching lists:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/lists/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchLists()
}, 300)

const viewList = async (list) => {
    viewingList.value = list
    showViewModal.value = true
    
    try {
        const response = await axios.get(`/admin-data/lists/${list.id}`)
        viewingListDetails.value = response.data
    } catch (error) {
        console.error('Error fetching list details:', error)
    }
}

const editList = (list) => {
    editingList.value = list
    Object.assign(editForm, {
        name: list.name,
        description: list.description || '',
        visibility: list.visibility,
        is_featured: list.is_featured,
    })
    showEditModal.value = true
}

const updateList = async () => {
    processing.value = true
    try {
        await axios.put(`/admin-data/lists/${editingList.value.id}`, editForm)
        showEditModal.value = false
        fetchLists()
        alert('List updated successfully')
    } catch (error) {
        alert('Error updating list: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const deleteList = async (list) => {
   if (!confirm(`Are you sure you want to delete "${list.name}"?`)) return
   
   try {
       await axios.delete(`/admin-data/lists/${list.id}`)
       fetchLists()
       fetchStats()
       alert('List deleted successfully')
   } catch (error) {
       alert('Error deleting list: ' + error.response?.data?.message)
   }
}

const toggleSelectAll = () => {
   if (isAllSelected.value) {
       selectedLists.value = []
   } else {
       selectedLists.value = lists.value.data.map(l => l.id)
   }
}

const bulkUpdateVisibility = async () => {
   if (!bulkVisibility.value) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/lists/bulk-update', {
           list_ids: selectedLists.value,
           visibility: bulkVisibility.value,
           action: 'update_visibility'
       })
       
       showBulkVisibilityModal.value = false
       selectedLists.value = []
       bulkVisibility.value = ''
       fetchLists()
       alert('Visibility updated successfully')
   } catch (error) {
       alert('Error updating visibility: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const toggleFeatured = async () => {
   if (!confirm(`Toggle featured status for ${selectedLists.value.length} lists?`)) return
   
   processing.value = true
   try {
       // Get current featured status of selected lists
       const selectedListsData = lists.value.data.filter(l => selectedLists.value.includes(l.id))
       const allFeatured = selectedListsData.every(l => l.is_featured)
       
       await axios.post('/admin-data/lists/bulk-update', {
           list_ids: selectedLists.value,
           is_featured: !allFeatured,
           action: 'toggle_featured'
       })
       
       selectedLists.value = []
       fetchLists()
       alert('Featured status updated successfully')
   } catch (error) {
       alert('Error updating featured status: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const bulkDelete = async () => {
   if (!confirm(`Are you sure you want to delete ${selectedLists.value.length} lists?`)) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/lists/bulk-update', {
           list_ids: selectedLists.value,
           action: 'delete'
       })
       
       selectedLists.value = []
       fetchLists()
       fetchStats()
       alert('Lists deleted successfully')
   } catch (error) {
       alert('Error deleting lists: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const getVisibilityBadgeClass = (visibility) => {
   const classes = {
       public: 'bg-green-100 text-green-800',
       private: 'bg-red-100 text-red-800',
       unlisted: 'bg-yellow-100 text-yellow-800',
   }
   return classes[visibility] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
   if (!dateString) return 'N/A'
   return new Date(dateString).toLocaleDateString('en-US', {
       year: 'numeric',
       month: 'short',
       day: 'numeric'
   })
}

const formatNumber = (num) => {
    if (!num) return '0'
    return num.toLocaleString()
}

const truncate = (text, length) => {
    if (!text) return ''
    if (text.length <= length) return text
    return text.substring(0, length) + '...'
}

// Lifecycle
onMounted(() => {
   fetchLists()
   fetchStats()
})
</script>