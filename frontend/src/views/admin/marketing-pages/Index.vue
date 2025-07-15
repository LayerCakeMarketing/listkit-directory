<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">Marketing Pages</h2>
                    <button
                        @click="showCreateModal = true"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New Page
                    </button>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                        <div class="text-sm text-blue-600 font-medium">Total Pages</div>
                        <div class="text-2xl font-bold text-blue-900">{{ stats.total || 0 }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                        <div class="text-sm text-green-600 font-medium">Published</div>
                        <div class="text-2xl font-bold text-green-900">{{ stats.published || 0 }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-lg">
                        <div class="text-sm text-yellow-600 font-medium">Draft</div>
                        <div class="text-2xl font-bold text-yellow-900">{{ stats.draft || 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            v-model="filters.search"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Search pages..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="filters.status"
                            @change="fetchPages"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select
                            v-model="filters.sort_by"
                            @change="fetchPages"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="created_at">Created Date</option>
                            <option value="updated_at">Updated Date</option>
                            <option value="title">Title</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pages Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Loading State -->
                    <div v-if="loading" class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Table -->
                    <div v-else-if="pages.data && pages.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Page
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Slug
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Last Updated
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="page in pages.data" :key="page.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ page.title }}</div>
                                            <div class="text-xs text-gray-500" v-if="page.meta_title">{{ page.meta_title }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ page.slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(page.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ page.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(page.updated_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <router-link
                                            v-if="page.status === 'published'"
                                            :to="`/${page.slug}`"
                                            target="_blank"
                                            class="text-green-600 hover:text-green-900 mr-3"
                                        >
                                            View
                                        </router-link>
                                        <button
                                            @click="editPage(page)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deletePage(page)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pages found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new page.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pages.last_page > 1" class="mt-6">
                        <Pagination :links="pages.links" @navigate="fetchPages" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="4xl">
            <PageForm 
                :page="editingPage"
                @saved="handlePageSaved"
                @cancel="closeModal"
            />
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from 'vue'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import PageForm from './components/PageForm.vue'
import { debounce } from 'lodash'

// State
const loading = ref(false)
const pages = ref({ data: [], links: [], last_page: 1 })
const stats = ref({})
const showModal = ref(false)
const showCreateModal = ref(false)
const editingPage = ref(null)

// Filters
const filters = reactive({
    search: '',
    status: '',
    sort_by: 'created_at',
    sort_order: 'desc'
})

// Methods
const fetchPages = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/marketing-pages', {
            params: {
                page,
                ...filters
            }
        })
        pages.value = response.data
    } catch (error) {
        console.error('Error fetching pages:', error)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/marketing-pages/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchPages()
}, 300)

const editPage = (page) => {
    editingPage.value = page
    showModal.value = true
}

const deletePage = async (page) => {
    if (!confirm(`Are you sure you want to delete "${page.title}"?`)) return

    try {
        await axios.delete(`/api/admin/marketing-pages/${page.id}`)
        fetchPages()
        fetchStats()
    } catch (error) {
        alert('Error deleting page')
    }
}

const closeModal = () => {
    showModal.value = false
    showCreateModal.value = false
    editingPage.value = null
}

const handlePageSaved = () => {
    closeModal()
    fetchPages()
    fetchStats()
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}

const getStatusBadgeClass = (status) => {
    return status === 'published' 
        ? 'bg-green-100 text-green-800' 
        : 'bg-yellow-100 text-yellow-800'
}

// Watch for create modal
watch(() => showCreateModal.value, (newVal) => {
    if (newVal) {
        editingPage.value = null
        showModal.value = true
    }
})

// Initialize
onMounted(() => {
    fetchPages()
    fetchStats()
})
</script>