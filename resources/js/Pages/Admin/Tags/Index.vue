<template>
    <Head title="Tags Management" />
    
    <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tags Management</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Tags</h2>
                        <button
                            @click="openCreateModal"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add Tag
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Tags</div>
                            <div class="text-2xl font-bold">{{ stats.total_tags }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Active</div>
                            <div class="text-2xl font-bold">{{ stats.active_tags }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Inactive</div>
                            <div class="text-2xl font-bold">{{ stats.inactive_tags }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Usages</div>
                            <div class="text-2xl font-bold">{{ stats.total_usages }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                v-model="filters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Search tags..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filters.status"
                                @change="fetchTags"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
                            <select
                                v-model="filters.content_type"
                                @change="fetchTags"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Types</option>
                                <option value="App\Models\UserList">Lists</option>
                                <!-- Add more content types as they're implemented -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchTags"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="name">Name</option>
                                <option value="usage_count">Usage Count</option>
                                <option value="created_at">Created Date</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select
                                v-model="filters.sort_order"
                                @change="fetchTags"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="asc">Ascending</option>
                                <option value="desc">Descending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tags Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedTags.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedTags.length }} tag{{ selectedTags.length === 1 ? '' : 's' }} selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkActivate"
                                        class="text-sm bg-green-500 hover:bg-green-700 text-white px-3 py-1 rounded"
                                    >
                                        Activate
                                    </button>
                                    <button
                                        @click="bulkDeactivate"
                                        class="text-sm bg-yellow-500 hover:bg-yellow-700 text-white px-3 py-1 rounded"
                                    >
                                        Deactivate
                                    </button>
                                    <button
                                        @click="showMergeModal = true"
                                        v-if="selectedTags.length > 1"
                                        class="text-sm bg-purple-500 hover:bg-purple-700 text-white px-3 py-1 rounded"
                                    >
                                        Merge
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="text-sm bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded"
                                    >
                                        Delete
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
                                            Tag
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Slug
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Usage Count
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Content Types
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="tag.id"
                                                v-model="selectedTags"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div 
                                                    class="w-4 h-4 rounded mr-3" 
                                                    :style="{ backgroundColor: tag.color }"
                                                ></div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ tag.name }}
                                                    </div>
                                                    <div v-if="tag.description" class="text-xs text-gray-500">
                                                        {{ truncate(tag.description, 50) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900 font-mono">{{ tag.slug }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">{{ tag.taggables_count }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span 
                                                    v-for="(count, type) in tag.usage_by_type" 
                                                    :key="type"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                                >
                                                    {{ getContentTypeName(type) }}: {{ count }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="tag.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ tag.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="editTag(tag)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteTag(tag)"
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
                                    ...tags.meta,
                                    links: tags.links,
                                    from: tags.meta?.from || 0,
                                    to: tags.meta?.to || 0,
                                    total: tags.meta?.total || 0
                                }" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingTag ? 'Edit Tag' : 'Create Tag' }}
                </h3>

                <form @submit.prevent="saveTag">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="tagForm.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input
                                v-model="tagForm.slug"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Auto-generated from name"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="tagForm.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <input
                                v-model="tagForm.color"
                                type="color"
                                class="mt-1 block w-16 h-10 rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="tagForm.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
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

        <!-- Merge Modal -->
        <Modal :show="showMergeModal" @close="showMergeModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Merge Tags
                </h3>

                <p class="text-sm text-gray-600 mb-4">
                    Select which tag to keep. All other selected tags will be merged into this one.
                </p>

                <div class="space-y-2 mb-6">
                    <div v-for="tag in selectedTagsData" :key="tag.id" class="flex items-center">
                        <input
                            v-model="targetTagId"
                            :value="tag.id"
                            type="radio"
                            class="rounded border-gray-300"
                        />
                        <span class="ml-2 text-sm">{{ tag.name }} ({{ tag.taggables_count }} usages)</span>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="showMergeModal = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Cancel
                    </button>
                    <button
                        @click="mergeTags"
                        :disabled="!targetTagId || processing"
                        class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                    >
                        {{ processing ? 'Merging...' : 'Merge Tags' }}
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
const tags = ref({ 
    data: [], 
    links: [],
    meta: { from: 0, to: 0, total: 0 }
})
const stats = ref({
    total_tags: 0,
    active_tags: 0,
    inactive_tags: 0,
    total_usages: 0,
})
const filters = reactive({
    search: '',
    status: '',
    content_type: '',
    sort_by: 'name',
    sort_order: 'asc',
})

// UI State
const showModal = ref(false)
const showMergeModal = ref(false)
const processing = ref(false)
const selectedTags = ref([])
const editingTag = ref(null)
const targetTagId = ref(null)

// Forms
const tagForm = reactive({
    name: '',
    slug: '',
    description: '',
    color: '#6B7280',
    is_active: true,
})

// Computed
const isAllSelected = computed(() => {
    return tags.value.data.length > 0 && 
           selectedTags.value.length === tags.value.data.length
})

const selectedTagsData = computed(() => {
    return tags.value.data.filter(tag => selectedTags.value.includes(tag.id))
})

// Methods
const fetchTags = async () => {
    try {
        const response = await axios.get('/admin-data/tags', { params: filters })
        
        if (response.data && response.data.data) {
            tags.value = {
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
        console.error('Error fetching tags:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/tags/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchTags()
}, 300)

const openCreateModal = () => {
    editingTag.value = null
    Object.assign(tagForm, {
        name: '',
        slug: '',
        description: '',
        color: '#6B7280',
        is_active: true,
    })
    showModal.value = true
}

const editTag = (tag) => {
    editingTag.value = tag
    Object.assign(tagForm, {
        name: tag.name,
        slug: tag.slug,
        description: tag.description || '',
        color: tag.color,
        is_active: tag.is_active,
    })
    showModal.value = true
}

const saveTag = async () => {
    processing.value = true
    try {
        if (editingTag.value) {
            await axios.put(`/admin-data/tags/${editingTag.value.id}`, tagForm)
        } else {
            await axios.post('/admin-data/tags', tagForm)
        }
        
        closeModal()
        fetchTags()
        fetchStats()
        alert('Tag saved successfully')
    } catch (error) {
        alert('Error saving tag: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const deleteTag = async (tag) => {
    if (!confirm(`Are you sure you want to delete "${tag.name}"? This will remove it from all content.`)) return
   
    try {
        await axios.delete(`/admin-data/tags/${tag.id}`)
        fetchTags()
        fetchStats()
        alert('Tag deleted successfully')
    } catch (error) {
        alert('Error deleting tag: ' + error.response?.data?.message)
    }
}

const closeModal = () => {
    showModal.value = false
    editingTag.value = null
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedTags.value = []
    } else {
        selectedTags.value = tags.value.data.map(t => t.id)
    }
}

const bulkActivate = async () => {
    if (!confirm(`Activate ${selectedTags.value.length} tags?`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/tags/bulk-update', {
            tag_ids: selectedTags.value,
            is_active: true,
            action: 'activate'
        })
        
        selectedTags.value = []
        fetchTags()
        alert('Tags activated successfully')
    } catch (error) {
        alert('Error activating tags: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const bulkDeactivate = async () => {
    if (!confirm(`Deactivate ${selectedTags.value.length} tags?`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/tags/bulk-update', {
            tag_ids: selectedTags.value,
            is_active: false,
            action: 'deactivate'
        })
        
        selectedTags.value = []
        fetchTags()
        alert('Tags deactivated successfully')
    } catch (error) {
        alert('Error deactivating tags: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedTags.value.length} tags? This will remove them from all content.`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/tags/bulk-update', {
            tag_ids: selectedTags.value,
            action: 'delete'
        })
        
        selectedTags.value = []
        fetchTags()
        fetchStats()
        alert('Tags deleted successfully')
    } catch (error) {
        alert('Error deleting tags: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const mergeTags = async () => {
    if (!targetTagId.value) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/tags/bulk-update', {
            tag_ids: selectedTags.value,
            target_tag_id: targetTagId.value,
            action: 'merge'
        })
        
        showMergeModal.value = false
        selectedTags.value = []
        targetTagId.value = null
        fetchTags()
        fetchStats()
        alert('Tags merged successfully')
    } catch (error) {
        alert('Error merging tags: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const getContentTypeName = (type) => {
    const mapping = {
        'App\\Models\\UserList': 'Lists',
        // Add more mappings as needed
    }
    return mapping[type] || type.split('\\').pop()
}

const truncate = (text, length) => {
    if (!text) return ''
    if (text.length <= length) return text
    return text.substring(0, length) + '...'
}

// Lifecycle
onMounted(() => {
    fetchTags()
    fetchStats()
})
</script>