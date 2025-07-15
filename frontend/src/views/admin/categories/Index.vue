<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Place Categories</h1>
                        <p class="text-gray-600 text-sm mt-1">Manage categories for organizing places in your directory</p>
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

            <!-- Categories Tree -->
            <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div v-if="categories.length > 0" class="space-y-4">
                        <div v-for="category in parentCategories" :key="category.id" class="border rounded-lg">
                            <!-- Parent Category -->
                            <div class="p-4 bg-gray-50 border-b">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <!-- Category Icon/Image -->
                                        <div class="flex-shrink-0">
                                            <div v-if="category.cover_image_url" class="w-16 h-16 rounded-lg overflow-hidden">
                                                <img :src="category.cover_image_url" :alt="category.name" class="w-full h-full object-cover">
                                            </div>
                                            <div v-else class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
                                                <div v-if="category.svg_icon" v-html="category.svg_icon" class="w-8 h-8 text-gray-700"></div>
                                                <span v-else class="text-2xl">{{ category.icon || '📁' }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ category.name }}</h3>
                                            <p class="text-sm text-gray-600">{{ category.slug }}</p>
                                            <p v-if="category.description" class="text-sm text-gray-500 mt-1">{{ category.description }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="editCategory(category)"
                                            class="text-blue-600 hover:text-blue-800 p-2"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteCategory(category)"
                                            class="text-red-600 hover:text-red-800 p-2"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Child Categories -->
                            <div v-if="getChildCategories(category.id).length > 0" class="p-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Subcategories</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div
                                        v-for="child in getChildCategories(category.id)"
                                        :key="child.id"
                                        class="border rounded-lg p-3 bg-gray-50 hover:bg-gray-100 transition-colors"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <div v-if="child.svg_icon" v-html="child.svg_icon" class="w-5 h-5 text-gray-600"></div>
                                                <span v-else class="text-lg">{{ child.icon || '📄' }}</span>
                                                <div>
                                                    <h5 class="font-medium text-gray-900">{{ child.name }}</h5>
                                                    <p class="text-xs text-gray-600">{{ child.slug }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <button
                                                    @click="editCategory(child)"
                                                    class="text-blue-600 hover:text-blue-800 p-1"
                                                    title="Edit"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="deleteCategory(child)"
                                                    class="text-red-600 hover:text-red-800 p-1"
                                                    title="Delete"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-4 text-gray-500 text-sm">
                                No subcategories
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12">
                        <div class="text-gray-500 text-lg">No categories yet.</div>
                        <p class="text-gray-400 mt-2">Get started by creating your first category.</p>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <Teleport to="body">
                <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ showEditModal ? 'Edit Category' : 'Create Category' }}
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
                                <label for="icon" class="block text-sm font-medium text-gray-700">Icon (emoji)</label>
                                <input
                                    v-model="form.icon"
                                    type="text"
                                    id="icon"
                                    maxlength="10"
                                    placeholder="📁"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label for="svg_icon" class="block text-sm font-medium text-gray-700">SVG Icon</label>
                                <textarea
                                    v-model="form.svg_icon"
                                    id="svg_icon"
                                    rows="3"
                                    placeholder='<svg>...</svg>'
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">Paste SVG code for the icon</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                                <div class="mt-1 space-y-2">
                                    <!-- Image Preview -->
                                    <div v-if="form.cover_image_url || coverImagePreview" class="relative">
                                        <img 
                                            :src="coverImagePreview || form.cover_image_url" 
                                            alt="Cover preview"
                                            class="w-full h-48 object-cover rounded-lg"
                                        />
                                        <button
                                            type="button"
                                            @click="removeCoverImage"
                                            class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full hover:bg-red-600"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Upload Button -->
                                    <div v-else>
                                        <div v-if="uploadingImage" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                            <span class="ml-2 text-gray-600">Uploading...</span>
                                        </div>
                                        <label v-else class="block">
                                            <span class="sr-only">Choose cover image</span>
                                            <input
                                                type="file"
                                                @change="handleCoverImageUpload"
                                                accept="image/*"
                                                :disabled="uploadingImage"
                                                class="block w-full text-sm text-gray-500
                                                    file:mr-4 file:py-2 file:px-4
                                                    file:rounded-full file:border-0
                                                    file:text-sm file:font-semibold
                                                    file:bg-blue-50 file:text-blue-700
                                                    hover:file:bg-blue-100
                                                    disabled:opacity-50"
                                            />
                                        </label>
                                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Category</label>
                                <select
                                    v-model="form.parent_id"
                                    id="parent_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">None (Top Level)</option>
                                    <option
                                        v-for="parent in parentCategories"
                                        :key="parent.id"
                                        :value="parent.id"
                                        :disabled="editingCategory && parent.id === editingCategory.id"
                                    >
                                        {{ parent.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    id="description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
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

const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const categories = ref([])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingCategory = ref(null)
const processing = ref(false)

// Form
const form = reactive({
    name: '',
    slug: '',
    icon: '',
    svg_icon: '',
    description: '',
    parent_id: '',
    cover_image_url: '',
    cover_image_cloudflare_id: ''
})

// Image handling
const coverImagePreview = ref(null)
const uploadingImage = ref(false)

const formErrors = ref({})

// Computed
const parentCategories = computed(() => {
    return categories.value.filter(cat => !cat.parent_id)
})

// Methods
const getChildCategories = (parentId) => {
    return categories.value.filter(cat => cat.parent_id === parentId)
}

const fetchCategories = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get('/api/categories')
        // Handle both direct array and wrapped response
        if (response.data.categories) {
            categories.value = response.data.categories
        } else if (Array.isArray(response.data)) {
            categories.value = response.data
        } else {
            categories.value = []
        }
        console.log('Categories loaded:', categories.value.length, categories.value)
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load categories'
        console.error('Error fetching categories:', err)
        categories.value = [] // Initialize as empty array on error
    } finally {
        loading.value = false
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
    form.icon = category.icon || ''
    form.svg_icon = category.svg_icon || ''
    form.description = category.description || ''
    form.parent_id = category.parent_id || ''
    form.cover_image_url = category.cover_image_url || ''
    form.cover_image_cloudflare_id = category.cover_image_cloudflare_id || ''
    coverImagePreview.value = null
    showEditModal.value = true
}

const saveCategory = async () => {
    processing.value = true
    formErrors.value = {}

    try {
        const data = {
            name: form.name,
            slug: form.slug,
            icon: form.icon || null,
            svg_icon: form.svg_icon || null,
            description: form.description || null,
            parent_id: form.parent_id || null,
            cover_image_url: form.cover_image_url || null,
            cover_image_cloudflare_id: form.cover_image_cloudflare_id || null
        }

        if (showEditModal.value && editingCategory.value) {
            await axios.put(`/api/admin/categories/${editingCategory.value.id}`, data)
        } else {
            await axios.post('/api/admin/categories', data)
        }

        await fetchCategories()
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
        await axios.delete(`/api/admin/categories/${category.id}`)
        await fetchCategories()
    } catch (err) {
        alert('Error deleting category: ' + (err.response?.data?.message || 'Unknown error'))
    }
}

const closeModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    editingCategory.value = null
    form.name = ''
    form.slug = ''
    form.icon = ''
    form.svg_icon = ''
    form.description = ''
    form.parent_id = ''
    form.cover_image_url = ''
    form.cover_image_cloudflare_id = ''
    coverImagePreview.value = null
    formErrors.value = {}
}

// Initialize
onMounted(async () => {
    document.title = 'Place Categories Management'
    await fetchCategories()
})

// Image upload handlers
const handleCoverImageUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB')
        return
    }

    uploadingImage.value = true
    
    try {
        // Create form data
        const formData = new FormData()
        formData.append('file', file)

        // Get upload URL from backend
        const uploadUrlResponse = await axios.post('/api/cloudflare/upload-url')
        
        if (!uploadUrlResponse.data.success) {
            throw new Error('Failed to get upload URL')
        }

        // Upload directly to Cloudflare
        const uploadFormData = new FormData()
        uploadFormData.append('file', file)
        
        const uploadResponse = await axios.post(uploadUrlResponse.data.result.uploadURL, uploadFormData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

        if (uploadResponse.data.success && uploadResponse.data.result) {
            form.cover_image_cloudflare_id = uploadResponse.data.result.id
            form.cover_image_url = uploadResponse.data.result.variants[0]
            coverImagePreview.value = uploadResponse.data.result.variants[0]
        }
    } catch (error) {
        console.error('Upload error:', error)
        alert('Failed to upload image')
    } finally {
        uploadingImage.value = false
    }
}

const removeCoverImage = () => {
    form.cover_image_url = ''
    form.cover_image_cloudflare_id = ''
    coverImagePreview.value = null
}

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