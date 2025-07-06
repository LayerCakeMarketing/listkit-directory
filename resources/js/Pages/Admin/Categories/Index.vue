<template>
    <Head title="Categories" />
    
    <AdminDashboardLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Directory Categories</h2>
                <button 
                    @click="openCreateModal"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Add Category
                </button>
            </div>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Main Categories -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Main Categories</h3>
                        
                        <div v-if="loading" class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="category in mainCategories" 
                                :key="category.id"
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <div v-if="category.svg_icon" v-html="category.svg_icon" class="w-6 h-6"></div>
                                            <span v-else class="text-2xl">{{ category.icon || '📁' }}</span>
                                            <h4 class="font-medium text-lg">{{ category.name }}</h4>
                                        </div>
                                        <p class="text-gray-600 text-sm mt-1">{{ category.description }}</p>
                                        <div class="flex items-center space-x-3 text-xs text-gray-500 mt-2">
                                            <span>{{ category.children?.length || 0 }} subcategories</span>
                                            <span v-if="category.quotes_count > 0" class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                                {{ category.quotes_count }} quotes
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button 
                                            @click="editCategory(category)"
                                            class="text-blue-600 hover:text-blue-800"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteCategory(category)"
                                            class="text-red-600 hover:text-red-800"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Subcategories -->
                                <div v-if="category.children?.length > 0" class="mt-3 pt-3 border-t">
                                    <div class="flex flex-wrap gap-2">
                                        <span 
                                            v-for="subcategory in category.children" 
                                            :key="subcategory.id"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                        >
                                            {{ subcategory.name }}
                                            <button 
                                                @click.stop="deleteCategory(subcategory)"
                                                class="ml-1 text-gray-600 hover:text-red-600"
                                            >
                                                ×
                                            </button>
                                        </span>
                                        <button 
                                            @click="openCreateModal(category.id)"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200"
                                        >
                                            + Add Subcategory
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingCategory ? 'Edit Category' : 'Create Category' }}
                </h3>
                
                <form @submit.prevent="saveCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input 
                            v-model="categoryForm.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Slug</label>
                        <input 
                            v-model="categoryForm.slug"
                            type="text"
                            required
                            pattern="[a-z0-9\-]+"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="auto-generated-from-name"
                        />
                        <p class="mt-1 text-xs text-gray-500">URL-friendly version (lowercase, hyphens only)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Icon (Emoji)</label>
                        <input 
                            v-model="categoryForm.icon"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="🏷️"
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
                            entity-type="App\Models\Category"
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
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea 
                            v-model="categoryForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        ></textarea>
                    </div>
                    
                    <div v-if="!editingCategory || !editingCategory.parent_id">
                        <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                        <select 
                            v-model="categoryForm.parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option :value="null">None (Main Category)</option>
                            <option 
                                v-for="parent in mainCategories" 
                                :key="parent.id" 
                                :value="parent.id"
                                :disabled="editingCategory?.id === parent.id"
                            >
                                {{ parent.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button"
                            @click="closeModal"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="saving"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'
import { Head } from '@inertiajs/vue3'

const axios = window.axios

// Data
const categories = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const editingCategory = ref(null)

const categoryForm = reactive({
    name: '',
    slug: '',
    icon: '',
    svg_icon: '',
    cover_image_cloudflare_id: null,
    cover_image_url: null,
    quotes: [],
    description: '',
    parent_id: null
})

const newQuote = ref('')

// Computed
const mainCategories = computed(() => {
    return categories.value.filter(cat => !cat.parent_id).map(cat => ({
        ...cat,
        children: categories.value.filter(sub => sub.parent_id === cat.id)
    }))
})

// Watch name changes to auto-generate slug
watch(() => categoryForm.name, (newName) => {
    if (!editingCategory.value && newName) {
        categoryForm.slug = newName.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '')
    }
})

// Methods
const fetchCategories = async () => {
    loading.value = true
    try {
        const response = await axios.get('/admin-data/categories')
        // Store all categories in a flat array for easier management
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching categories:', error)
        alert('Error loading categories')
    } finally {
        loading.value = false
    }
}

const openCreateModal = (parentId = null) => {
    editingCategory.value = null
    Object.assign(categoryForm, {
        name: '',
        slug: '',
        icon: '',
        svg_icon: '',
        cover_image_cloudflare_id: null,
        cover_image_url: null,
        quotes: [],
        description: '',
        parent_id: parentId
    })
    newQuote.value = ''
    showModal.value = true
}

const editCategory = (category) => {
    editingCategory.value = category
    Object.assign(categoryForm, {
        name: category.name,
        slug: category.slug,
        icon: category.icon || '',
        svg_icon: category.svg_icon || '',
        cover_image_cloudflare_id: category.cover_image_cloudflare_id || null,
        cover_image_url: category.cover_image_url || null,
        quotes: category.quotes || [],
        description: category.description || '',
        parent_id: category.parent_id
    })
    newQuote.value = ''
    showModal.value = true
}

const saveCategory = async () => {
    saving.value = true
    try {
        if (editingCategory.value) {
            await axios.put(`/admin-data/categories/${editingCategory.value.id}`, categoryForm)
        } else {
            await axios.post('/admin-data/categories', categoryForm)
        }
        await fetchCategories()
        closeModal()
        alert('Category saved successfully')
    } catch (error) {
        console.error('Error saving category:', error)
        alert('Error saving category: ' + (error.response?.data?.message || error.message))
    } finally {
        saving.value = false
    }
}

const deleteCategory = async (category) => {
    if (category.children?.length > 0) {
        alert('Cannot delete category with subcategories. Delete subcategories first.')
        return
    }
    
    if (!confirm(`Are you sure you want to delete "${category.name}"?`)) return
    
    try {
        await axios.delete(`/admin-data/categories/${category.id}`)
        await fetchCategories()
        alert('Category deleted successfully')
    } catch (error) {
        console.error('Error deleting category:', error)
        alert('Error deleting category: ' + (error.response?.data?.message || error.message))
    }
}

const closeModal = () => {
    showModal.value = false
    editingCategory.value = null
    Object.assign(categoryForm, {
        name: '',
        slug: '',
        icon: '',
        svg_icon: '',
        cover_image_cloudflare_id: null,
        cover_image_url: null,
        quotes: [],
        description: '',
        parent_id: null
    })
    newQuote.value = ''
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
})
</script>