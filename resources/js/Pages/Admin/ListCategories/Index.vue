<template>
    <Head title="List Categories Management" />
    
    <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">List Categories Management</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">List Categories</h2>
                        <button
                            @click="openCreateModal"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add Category
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Categories</div>
                            <div class="text-2xl font-bold">{{ stats.total_categories }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Active</div>
                            <div class="text-2xl font-bold">{{ stats.active_categories }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Inactive</div>
                            <div class="text-2xl font-bold">{{ stats.inactive_categories }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Lists Categorized</div>
                            <div class="text-2xl font-bold">{{ stats.total_lists_categorized }}</div>
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
                                placeholder="Search categories..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filters.status"
                                @change="fetchCategories"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchCategories"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="sort_order">Sort Order</option>
                                <option value="name">Name</option>
                                <option value="lists_count">Lists Count</option>
                                <option value="created_at">Created Date</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select
                                v-model="filters.sort_order"
                                @change="fetchCategories"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="asc">Ascending</option>
                                <option value="desc">Descending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedCategories.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedCategories.length }} categor{{ selectedCategories.length === 1 ? 'y' : 'ies' }} selected
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
                                            Category
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Media
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Slug
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Lists Count
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Order
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="category in categories.data" :key="category.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="category.id"
                                                v-model="selectedCategories"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div v-if="category.svg_icon" v-html="category.svg_icon" class="w-6 h-6 mr-3"></div>
                                                <div v-else
                                                    class="w-4 h-4 rounded mr-3" 
                                                    :style="{ backgroundColor: category.color }"
                                                ></div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ category.name }}
                                                    </div>
                                                    <div v-if="category.description" class="text-xs text-gray-500">
                                                        {{ truncate(category.description, 50) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                <span v-if="category.cover_image_cloudflare_id || category.cover_image_url"
                                                    class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Cover
                                                </span>
                                                <span v-if="category.quotes_count > 0" class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                    </svg>
                                                    {{ category.quotes_count }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900 font-mono">{{ category.slug }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">{{ category.lists_count }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ category.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">{{ category.sort_order }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="editCategory(category)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteCategory(category)"
                                                class="text-red-600 hover:text-red-900"
                                                :disabled="category.lists_count > 0"
                                                :class="{ 'opacity-50 cursor-not-allowed': category.lists_count > 0 }"
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
                                    ...categories.meta,
                                    links: categories.links,
                                    from: categories.meta?.from || 0,
                                    to: categories.meta?.to || 0,
                                    total: categories.meta?.total || 0
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
                    {{ editingCategory ? 'Edit Category' : 'Create Category' }}
                </h3>

                <form @submit.prevent="saveCategory">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="categoryForm.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input
                                v-model="categoryForm.slug"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Auto-generated from name"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="categoryForm.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <input
                                v-model="categoryForm.color"
                                type="color"
                                class="mt-1 block w-16 h-10 rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">SVG Icon</label>
                            <div class="mt-1 space-y-2">
                                <textarea 
                                    v-model="categoryForm.svg_icon"
                                    rows="4"
                                    class="block w-full rounded-md border-gray-300 shadow-sm font-mono text-xs"
                                    placeholder="<svg>...</svg>"
                                ></textarea>
                                <div v-if="categoryForm.svg_icon" class="p-4 bg-gray-50 rounded-md flex items-center justify-center">
                                    <div v-html="categoryForm.svg_icon" class="w-8 h-8"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                            <CloudflareDragDropUploader
                                v-if="showModal"
                                :max-files="1"
                                :max-file-size="14680064"
                                context="cover"
                                entity-type="App\Models\ListCategory"
                                :entity-id="editingCategory?.id"
                                @upload-success="handleCoverUpload"
                                @upload-error="handleUploadError"
                            />
                            <div v-if="categoryForm.cover_image_cloudflare_id || categoryForm.cover_image_url" class="mt-2">
                                <img 
                                    :src="getCoverImageUrl()" 
                                    alt="Cover"
                                    class="w-full h-32 object-cover rounded-md"
                                />
                                <button
                                    type="button"
                                    @click="removeCoverImage"
                                    class="mt-2 text-sm text-red-600 hover:text-red-800"
                                >
                                    Remove cover image
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Quotes ({{ categoryForm.quotes.length }})
                            </label>
                            <div class="mt-1 space-y-2">
                                <div v-for="(quote, index) in categoryForm.quotes" :key="index" class="flex items-start space-x-2">
                                    <textarea 
                                        v-model="categoryForm.quotes[index]"
                                        rows="2"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                                    ></textarea>
                                    <button
                                        type="button"
                                        @click="removeQuote(index)"
                                        class="p-2 text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input
                                        v-model="newQuote"
                                        type="text"
                                        placeholder="Add a new quote..."
                                        class="flex-1 rounded-md border-gray-300 shadow-sm"
                                        @keyup.enter="addQuote"
                                    />
                                    <button
                                        type="button"
                                        @click="addQuote"
                                        class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                            <input
                                v-model.number="categoryForm.sort_order"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="categoryForm.is_active"
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
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
const axios = window.axios
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

// Data
const categories = ref({ 
    data: [], 
    links: [],
    meta: { from: 0, to: 0, total: 0 }
})
const stats = ref({
    total_categories: 0,
    active_categories: 0,
    inactive_categories: 0,
    total_lists_categorized: 0,
})
const filters = reactive({
    search: '',
    status: '',
    sort_by: 'sort_order',
    sort_order: 'asc',
})

// UI State
const showModal = ref(false)
const processing = ref(false)
const selectedCategories = ref([])
const editingCategory = ref(null)

// Forms
const categoryForm = reactive({
    name: '',
    slug: '',
    description: '',
    color: '#3B82F6',
    svg_icon: '',
    cover_image_cloudflare_id: null,
    cover_image_url: null,
    quotes: [],
    is_active: true,
    sort_order: 0,
})

const newQuote = ref('')

// Computed
const isAllSelected = computed(() => {
    return categories.value.data.length > 0 && 
           selectedCategories.value.length === categories.value.data.length
})

// Methods
const fetchCategories = async () => {
    try {
        const response = await axios.get('/admin-data/list-categories', { params: filters })
        
        if (response.data && response.data.data) {
            categories.value = {
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
        console.error('Error fetching categories:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/list-categories/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchCategories()
}, 300)

const openCreateModal = () => {
    editingCategory.value = null
    Object.assign(categoryForm, {
        name: '',
        slug: '',
        description: '',
        color: '#3B82F6',
        svg_icon: '',
        cover_image_cloudflare_id: null,
        cover_image_url: null,
        quotes: [],
        is_active: true,
        sort_order: 0,
    })
    newQuote.value = ''
    showModal.value = true
}

const editCategory = (category) => {
    editingCategory.value = category
    Object.assign(categoryForm, {
        name: category.name,
        slug: category.slug,
        description: category.description || '',
        color: category.color,
        svg_icon: category.svg_icon || '',
        cover_image_cloudflare_id: category.cover_image_cloudflare_id || null,
        cover_image_url: category.cover_image_url || null,
        quotes: category.quotes || [],
        is_active: category.is_active,
        sort_order: category.sort_order,
    })
    newQuote.value = ''
    showModal.value = true
}

const saveCategory = async () => {
    processing.value = true
    try {
        if (editingCategory.value) {
            await axios.put(`/admin-data/list-categories/${editingCategory.value.id}`, categoryForm)
        } else {
            await axios.post('/admin-data/list-categories', categoryForm)
        }
        
        closeModal()
        fetchCategories()
        fetchStats()
        alert('Category saved successfully')
    } catch (error) {
        alert('Error saving category: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const deleteCategory = async (category) => {
    if (category.lists_count > 0) {
        alert('Cannot delete category that has lists. Please move or delete the lists first.')
        return
    }
    
    if (!confirm(`Are you sure you want to delete "${category.name}"?`)) return
   
    try {
        await axios.delete(`/admin-data/list-categories/${category.id}`)
        fetchCategories()
        fetchStats()
        alert('Category deleted successfully')
    } catch (error) {
        alert('Error deleting category: ' + error.response?.data?.message)
    }
}

const closeModal = () => {
    showModal.value = false
    editingCategory.value = null
    Object.assign(categoryForm, {
        name: '',
        slug: '',
        description: '',
        color: '#3B82F6',
        svg_icon: '',
        cover_image_cloudflare_id: null,
        cover_image_url: null,
        quotes: [],
        is_active: true,
        sort_order: 0,
    })
    newQuote.value = ''
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedCategories.value = []
    } else {
        selectedCategories.value = categories.value.data.map(c => c.id)
    }
}

const bulkActivate = async () => {
    if (!confirm(`Activate ${selectedCategories.value.length} categories?`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/list-categories/bulk-update', {
            category_ids: selectedCategories.value,
            is_active: true,
            action: 'activate'
        })
        
        selectedCategories.value = []
        fetchCategories()
        alert('Categories activated successfully')
    } catch (error) {
        alert('Error activating categories: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const bulkDeactivate = async () => {
    if (!confirm(`Deactivate ${selectedCategories.value.length} categories?`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/list-categories/bulk-update', {
            category_ids: selectedCategories.value,
            is_active: false,
            action: 'deactivate'
        })
        
        selectedCategories.value = []
        fetchCategories()
        alert('Categories deactivated successfully')
    } catch (error) {
        alert('Error deactivating categories: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedCategories.value.length} categories? Categories with lists will be skipped.`)) return
    
    processing.value = true
    try {
        await axios.post('/admin-data/list-categories/bulk-update', {
            category_ids: selectedCategories.value,
            action: 'delete'
        })
        
        selectedCategories.value = []
        fetchCategories()
        fetchStats()
        alert('Categories deleted successfully (categories with lists were skipped)')
    } catch (error) {
        alert('Error deleting categories: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const truncate = (text, length) => {
    if (!text) return ''
    if (text.length <= length) return text
    return text.substring(0, length) + '...'
}

// Cover image methods
const handleCoverUpload = (response) => {
    if (response.image) {
        categoryForm.cover_image_cloudflare_id = response.image.cloudflare_id
        categoryForm.cover_image_url = response.image.url
    }
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Error uploading cover image: ' + (error.message || 'Unknown error'))
}

const removeCoverImage = () => {
    categoryForm.cover_image_cloudflare_id = null
    categoryForm.cover_image_url = null
}

const getCoverImageUrl = () => {
    if (categoryForm.cover_image_cloudflare_id) {
        // Use Cloudflare URL with public variant
        return `https://imagedelivery.net/${import.meta.env.VITE_CLOUDFLARE_ACCOUNT_HASH}/${categoryForm.cover_image_cloudflare_id}/public`
    }
    return categoryForm.cover_image_url || ''
}

// Quote methods
const addQuote = () => {
    if (newQuote.value.trim()) {
        categoryForm.quotes.push(newQuote.value.trim())
        newQuote.value = ''
    }
}

const removeQuote = (index) => {
    categoryForm.quotes.splice(index, 1)
}

// Lifecycle
onMounted(() => {
    fetchCategories()
    fetchStats()
})
</script>