<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">List Categories</h1>
                        <p class="text-gray-600 text-sm mt-1">Manage categories for user-created lists</p>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Category
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_categories }}</div>
                    <div class="text-sm text-gray-600">Total Categories</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-2xl font-bold text-green-600">{{ stats.active_categories }}</div>
                    <div class="text-sm text-gray-600">Active</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-2xl font-bold text-gray-400">{{ stats.inactive_categories }}</div>
                    <div class="text-sm text-gray-600">Inactive</div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-2xl font-bold text-blue-600">{{ stats.total_lists_categorized }}</div>
                    <div class="text-sm text-gray-600">Lists Categorized</div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                    <div class="flex-1 max-w-md">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search categories..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="flex items-center space-x-4">
                        <select
                            v-model="filters.status"
                            @change="fetchCategories"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select
                            v-model="filters.sort_by"
                            @change="fetchCategories"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="sort_order">Sort Order</option>
                            <option value="name">Name</option>
                            <option value="lists_count">Lists Count</option>
                            <option value="created_at">Created Date</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading categories</h3>
                        <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table v-if="categories.length > 0" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Lists
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sort Order
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="category in categories" :key="category.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            v-if="category.svg_icon"
                                            class="w-8 h-8 mr-3 text-gray-700"
                                            v-html="category.svg_icon"
                                        ></div>
                                        <div
                                            v-else-if="category.color"
                                            class="w-8 h-8 rounded-full mr-3 flex items-center justify-center text-white text-sm font-bold"
                                            :style="{ backgroundColor: category.color }"
                                        >
                                            {{ category.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-gray-300 mr-3"></div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                                            <div class="text-sm text-gray-500">{{ category.slug }}</div>
                                            <div v-if="category.quotes_count > 0" class="text-xs text-gray-400">{{ category.quotes_count }} quotes</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ category.lists_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            category.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-gray-100 text-gray-800'
                                        ]"
                                    >
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ category.sort_order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        @click="editCategory(category)"
                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center py-12">
                        <div class="text-gray-500 text-lg">No categories found.</div>
                        <p class="text-gray-400 mt-2">Create your first list category to get started.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="bg-gray-50 px-4 py-3 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing 
                            <span class="font-medium">{{ pagination.from }}</span>
                            to
                            <span class="font-medium">{{ pagination.to }}</span>
                            of
                            <span class="font-medium">{{ pagination.total }}</span>
                            results
                        </div>
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 text-sm rounded-md transition-colors"
                                :class="pagination.current_page === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                            >
                                Previous
                            </button>
                            
                            <button
                                v-for="page in paginationRange"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md transition-colors',
                                    page === pagination.current_page 
                                        ? 'bg-blue-600 text-white' 
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <button
                                @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 text-sm rounded-md transition-colors"
                                :class="pagination.current_page === pagination.last_page ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                            >
                                Next
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <Teleport to="body">
                <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ showEditModal ? 'Edit List Category' : 'Create List Category' }}
                            </h3>
                        </div>
                        <form @submit.prevent="saveCategory" class="p-6 space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</div>
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700">Slug *</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    id="slug"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="formErrors.slug" class="mt-1 text-sm text-red-600">{{ formErrors.slug }}</div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    id="description"
                                    rows="3"
                                    maxlength="1000"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
                                <input
                                    v-model="form.color"
                                    type="color"
                                    id="color"
                                    class="mt-1 block h-10 w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <!-- SVG Icon Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">SVG Icon</label>
                                <div class="mt-1">
                                    <!-- Preview existing icon -->
                                    <div v-if="form.svg_icon" class="mb-3 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-600">Current Icon:</span>
                                            <button
                                                type="button"
                                                @click="form.svg_icon = ''"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                        <div 
                                            class="w-16 h-16 text-gray-700"
                                            v-html="form.svg_icon"
                                        ></div>
                                    </div>
                                    
                                    <!-- Upload button -->
                                    <input
                                        type="file"
                                        @change="handleSvgUpload"
                                        accept=".svg"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Upload an SVG file (max 50KB)</p>
                                </div>
                            </div>

                            <!-- Cover Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                                <div class="mt-1">
                                    <!-- Preview existing image -->
                                    <div v-if="form.cover_image_cloudflare_id || form.cover_image_url" class="mb-3">
                                        <img 
                                            :src="coverImagePreview" 
                                            alt="Cover image"
                                            class="w-full h-32 object-cover rounded-lg"
                                        />
                                        <button
                                            type="button"
                                            @click="removeCoverImage"
                                            class="mt-2 text-red-600 hover:text-red-800 text-sm"
                                        >
                                            Remove Cover Image
                                        </button>
                                    </div>
                                    
                                    <!-- Cloudflare Image Upload -->
                                    <DirectCloudflareUpload
                                        v-if="!form.cover_image_cloudflare_id"
                                        @upload-success="handleCoverImageUpload"
                                        @upload-error="handleCoverImageError"
                                        :button-text="'Upload Cover Image'"
                                        :max-size-mb="10"
                                    />
                                </div>
                            </div>

                            <!-- Quotes Management -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quotes</label>
                                <div class="space-y-2">
                                    <!-- Existing quotes -->
                                    <div v-for="(quote, index) in form.quotes" :key="index" class="flex items-start space-x-2">
                                        <textarea
                                            v-model="form.quotes[index]"
                                            rows="2"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            placeholder="Enter a quote..."
                                        ></textarea>
                                        <button
                                            type="button"
                                            @click="removeQuote(index)"
                                            class="text-red-600 hover:text-red-800 p-1"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Add quote button -->
                                    <button
                                        type="button"
                                        @click="addQuote"
                                        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                    >
                                        + Add Quote
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                                <input
                                    v-model="form.sort_order"
                                    type="number"
                                    id="sort_order"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active
                                </label>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    @click="closeModals"
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                                >
                                    {{ processing ? 'Saving...' : (showEditModal ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import DirectCloudflareUpload from '@/components/image-upload/DirectCloudflareUpload.vue'

const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const categories = ref([])
const stats = ref(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingCategory = ref(null)
const processing = ref(false)

// Filters
const filters = reactive({
    search: '',
    status: '',
    sort_by: 'sort_order',
    sort_order: 'asc'
})

// Pagination
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0
})

// Form
const form = reactive({
    name: '',
    slug: '',
    description: '',
    color: '#3B82F6',
    svg_icon: '',
    cover_image_cloudflare_id: '',
    cover_image_url: '',
    quotes: [],
    is_active: true,
    sort_order: 0
})

const formErrors = ref({})

// Computed
const paginationRange = computed(() => {
    const range = []
    const { current_page, last_page } = pagination.value
    const delta = 2

    for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
        range.push(i)
    }

    return range
})

const coverImagePreview = computed(() => {
    if (form.cover_image_cloudflare_id) {
        return `https://imagedelivery.net/${import.meta.env.VITE_CLOUDFLARE_ACCOUNT_HASH}/${form.cover_image_cloudflare_id}/public`
    }
    return form.cover_image_url || ''
})

// Methods
const fetchCategories = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            search: filters.search,
            status: filters.status,
            sort_by: filters.sort_by,
            sort_order: filters.sort_order
        }

        const response = await axios.get('/api/admin/data/list-categories', { params })
        
        categories.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            from: response.data.from,
            to: response.data.to,
            total: response.data.total
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load categories'
        console.error('Error fetching categories:', err)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/data/list-categories/stats')
        stats.value = response.data
    } catch (err) {
        console.error('Error fetching stats:', err)
    }
}

const generateSlug = (name) => {
    return name.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim()
}

// Watch name changes to auto-generate slug
const watchNameForSlug = () => {
    if (!editingCategory.value && form.name) {
        form.slug = generateSlug(form.name)
    }
}

const editCategory = (category) => {
    editingCategory.value = category
    form.name = category.name
    form.slug = category.slug
    form.description = category.description || ''
    form.color = category.color || '#3B82F6'
    form.svg_icon = category.svg_icon || ''
    form.cover_image_cloudflare_id = category.cover_image_cloudflare_id || ''
    form.cover_image_url = category.cover_image_url || ''
    form.quotes = category.quotes || []
    form.is_active = category.is_active
    form.sort_order = category.sort_order || 0
    showEditModal.value = true
}

const saveCategory = async () => {
    processing.value = true
    formErrors.value = {}

    try {
        const data = {
            name: form.name,
            slug: form.slug,
            description: form.description || null,
            color: form.color || null,
            svg_icon: form.svg_icon || null,
            cover_image_cloudflare_id: form.cover_image_cloudflare_id || null,
            cover_image_url: form.cover_image_url || null,
            quotes: form.quotes.length > 0 ? form.quotes : null,
            is_active: form.is_active,
            sort_order: parseInt(form.sort_order) || 0
        }

        if (showEditModal.value && editingCategory.value) {
            await axios.put(`/api/admin/data/list-categories/${editingCategory.value.id}`, data)
        } else {
            await axios.post('/api/admin/data/list-categories', data)
        }

        await fetchCategories(pagination.value.current_page)
        await fetchStats()
        closeModals()
    } catch (err) {
        if (err.response?.status === 422) {
            formErrors.value = err.response.data.errors || {}
        } else {
            alert('Error saving category: ' + (err.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const deleteCategory = async (category) => {
    if (!confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
        return
    }

    try {
        await axios.delete(`/api/admin/data/list-categories/${category.id}`)
        await fetchCategories(pagination.value.current_page)
        await fetchStats()
    } catch (err) {
        alert('Error deleting category: ' + (err.response?.data?.message || 'Unknown error'))
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchCategories(page)
    }
}

const closeModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    editingCategory.value = null
    form.name = ''
    form.slug = ''
    form.description = ''
    form.color = '#3B82F6'
    form.svg_icon = ''
    form.cover_image_cloudflare_id = ''
    form.cover_image_url = ''
    form.quotes = []
    form.is_active = true
    form.sort_order = 0
    formErrors.value = {}
}

// Handle SVG upload
const handleSvgUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    // Validate file type
    if (file.type !== 'image/svg+xml') {
        alert('Please upload an SVG file')
        return
    }

    // Validate file size (50KB)
    if (file.size > 50 * 1024) {
        alert('SVG file must be less than 50KB')
        return
    }

    // Read file content
    const reader = new FileReader()
    reader.onload = (e) => {
        // Basic sanitization - remove script tags
        let svgContent = e.target.result
        svgContent = svgContent.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
        svgContent = svgContent.replace(/on\w+\s*=\s*["'][^"']*["']/gi, '')
        
        form.svg_icon = svgContent
    }
    reader.readAsText(file)
}

// Handle cover image upload
const handleCoverImageUpload = (data) => {
    form.cover_image_cloudflare_id = data.id
    form.cover_image_url = data.url
}

const handleCoverImageError = (error) => {
    console.error('Cover image upload error:', error)
    alert('Failed to upload cover image')
}

const removeCoverImage = () => {
    form.cover_image_cloudflare_id = ''
    form.cover_image_url = ''
}

// Quote management
const addQuote = () => {
    form.quotes.push('')
}

const removeQuote = (index) => {
    form.quotes.splice(index, 1)
}

// Debounced search
let searchTimeout = null
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        fetchCategories()
    }, 300)
}

// Initialize
onMounted(() => {
    document.title = 'List Categories Management'
    fetchCategories()
    fetchStats()
})

// Watch for name changes
onMounted(() => {
    const interval = setInterval(() => {
        if (showCreateModal.value) {
            watchNameForSlug()
        }
    }, 500)

    return () => clearInterval(interval)
})
</script>