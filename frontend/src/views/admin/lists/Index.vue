<template>
    <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">User Lists</h2>
                        <div class="text-sm text-gray-600">
                            Manage and moderate user-generated lists
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-7 gap-4 mt-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                            <div class="text-sm text-blue-600 font-medium">Total Lists</div>
                            <div class="text-2xl font-bold text-blue-900">{{ stats.total || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                            <div class="text-sm text-green-600 font-medium">Public</div>
                            <div class="text-2xl font-bold text-green-900">{{ stats.public || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                            <div class="text-sm text-purple-600 font-medium">Private</div>
                            <div class="text-2xl font-bold text-purple-900">{{ stats.private || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
                            <div class="text-sm text-orange-600 font-medium">Featured</div>
                            <div class="text-2xl font-bold text-orange-900">{{ stats.featured || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
                            <div class="text-sm text-indigo-600 font-medium">Avg Items</div>
                            <div class="text-2xl font-bold text-indigo-900">{{ stats.avg_items || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-pink-50 to-pink-100 p-4 rounded-lg">
                            <div class="text-sm text-pink-600 font-medium">Total Items</div>
                            <div class="text-2xl font-bold text-pink-900">{{ stats.total_items || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-lg">
                            <div class="text-sm text-red-600 font-medium">On Hold</div>
                            <div class="text-2xl font-bold text-red-900">{{ stats.on_hold || 0 }}</div>
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
                                placeholder="List name..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <input
                                v-model="filters.user_search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="User name or email..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select
                                v-model="filters.category_id"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Categories</option>
                                <option v-for="category in listCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Visibility</label>
                            <select
                                v-model="filters.visibility"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Visibility</option>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filters.status"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="on_hold">On Hold</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchLists"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="name">Name</option>
                                <option value="items_count">Items Count</option>
                                <option value="views">Views</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Filters -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button
                            @click="setQuickFilter('featured')"
                            :class="quickFilter === 'featured' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Featured Only
                        </button>
                        <button
                            @click="setQuickFilter('has_items')"
                            :class="quickFilter === 'has_items' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Has Items
                        </button>
                        <button
                            @click="setQuickFilter('empty')"
                            :class="quickFilter === 'empty' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Empty Lists
                        </button>
                        <button
                            v-if="quickFilter"
                            @click="clearQuickFilter"
                            class="px-3 py-1 bg-gray-300 text-gray-700 rounded-full text-sm font-medium transition-colors"
                        >
                            Clear Filter
                        </button>
                    </div>
                </div>

                <!-- Lists Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedLists.length > 0" class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700 font-medium">
                                    {{ selectedLists.length }} list(s) selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkUpdateVisibility"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                                    >
                                        Change Visibility
                                    </button>
                                    <button
                                        @click="bulkToggleFeatured"
                                        class="px-3 py-1 bg-orange-600 text-white text-sm rounded hover:bg-orange-700 transition-colors"
                                    >
                                        Toggle Featured
                                    </button>
                                    <button
                                        @click="bulkPutOnHold"
                                        class="px-3 py-1 bg-amber-600 text-white text-sm rounded hover:bg-amber-700 transition-colors"
                                    >
                                        Put On Hold
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors"
                                    >
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="lists.data && lists.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="selectedLists.length === lists.data.length"
                                                class="rounded border-gray-300"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            List
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Owner
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Category
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Items
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Visibility
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Stats
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="list in lists.data" :key="list.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :value="list.id"
                                                v-model="selectedLists"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img
                                                        v-if="list.image_url"
                                                        class="h-10 w-10 rounded-lg object-cover"
                                                        :src="list.image_url"
                                                        :alt="list.name"
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-lg bg-gray-300 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ list.name }}</div>
                                                    <div class="text-sm text-gray-500">/{{ list.slug }}</div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span v-if="list.is_featured" class="text-orange-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </span>
                                                        <span v-if="list.has_content" class="text-indigo-600">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <!-- Channel Owner -->
                                                <template v-if="list.owner_type === 'App\\Models\\Channel' && list.owner">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <img
                                                            v-if="list.owner.avatar_url"
                                                            class="h-8 w-8 rounded-full"
                                                            :src="list.owner.avatar_url"
                                                            :alt="list.owner.name"
                                                        />
                                                        <div v-else class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center">
                                                            <span class="text-white text-xs font-medium">
                                                                {{ list.owner.name?.charAt(0)?.toUpperCase() || 'C' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ list.owner.name }}</div>
                                                        <div class="text-xs text-purple-600">Channel</div>
                                                    </div>
                                                </template>
                                                <!-- User Owner -->
                                                <template v-else>
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <img
                                                            v-if="list.user?.avatar"
                                                            class="h-8 w-8 rounded-full"
                                                            :src="list.user.avatar"
                                                            :alt="list.user.name"
                                                        />
                                                        <div v-else class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                            <span class="text-gray-600 text-xs font-medium">
                                                                {{ list.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ list.user?.name || 'Unknown' }}</div>
                                                        <div class="text-xs text-gray-500">{{ list.user?.email }}</div>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="list.category" class="text-sm text-gray-900">
                                                {{ list.category.name }}
                                            </div>
                                            <div v-else class="text-sm text-gray-400">
                                                Uncategorized
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ list.items_count || 0 }} items</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span :class="getVisibilityBadgeClass(list.is_public)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                    {{ list.is_public ? 'Public' : 'Private' }}
                                                </span>
                                                <span v-if="list.is_featured" class="text-xs text-orange-600 font-medium">Featured</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span 
                                                    :class="getStatusBadgeClass(list.status || 'active')" 
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                >
                                                    {{ getStatusLabel(list.status || 'active') }}
                                                </span>
                                                <button
                                                    v-if="list.status !== 'on_hold'"
                                                    @click="putOnHold(list)"
                                                    class="text-xs text-amber-600 hover:text-amber-800 font-medium"
                                                    title="Put this list on hold for TOS violation"
                                                >
                                                    Hold
                                                </button>
                                                <button
                                                    v-else
                                                    @click="reactivateList(list)"
                                                    class="text-xs text-green-600 hover:text-green-800 font-medium"
                                                    title="Reactivate this list"
                                                >
                                                    Reactivate
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center gap-4">
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>{{ list.views || 0 }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                    </svg>
                                                    <span>{{ list.likes || 0 }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <router-link
                                                :to="`/admin/lists/${list.id}/edit`"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Edit
                                            </router-link>
                                            <router-link
                                                :to="`/up/@${list.user?.custom_url || list.user?.username || list.user_id}/${list.slug}`"
                                                target="_blank"
                                                class="text-green-600 hover:text-green-900 mr-3"
                                            >
                                                View
                                            </router-link>
                                            <button
                                                @click="manageItems(list)"
                                                class="text-purple-600 hover:text-purple-900 mr-3"
                                            >
                                                Items
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

                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No lists found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="lists.last_page > 1" class="mt-6">
                            <Pagination :links="lists.links" />
                        </div>
                    </div>
                </div>
            </div>


        <!-- List Items Modal -->
        <Modal :show="showItemsModal" @close="closeItemsModal" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Manage Items for {{ selectedList?.name }}
                </h3>

                <!-- This would be a more complex component to manage list items -->
                <div class="text-center py-8 text-gray-500">
                    List items management interface would go here
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeItemsModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import { debounce } from 'lodash'

// State
const loading = ref(false)
const lists = ref({ data: [], links: [], last_page: 1 })
const listCategories = ref([])
const users = ref([])
const stats = ref({})
const selectedLists = ref([])
const showModal = ref(false)
const showItemsModal = ref(false)
const editingList = ref(null)
const selectedList = ref(null)
const processing = ref(false)
const errors = ref({})
const quickFilter = ref('')

// Filters
const filters = reactive({
    search: '',
    user_search: '',
    category_id: '',
    visibility: '',
    status: '',
    sort_by: 'created_at',
    sort_order: 'desc'
})


// Methods
const fetchLists = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            ...filters
        }

        // Apply quick filters
        if (quickFilter.value === 'featured') {
            params.is_featured = true
        } else if (quickFilter.value === 'has_items') {
            params.has_items = true
        } else if (quickFilter.value === 'empty') {
            params.is_empty = true
        }

        const response = await axios.get('/api/admin/lists', { params })
        lists.value = response.data
    } catch (error) {
        console.error('Error fetching lists:', error)
    } finally {
        loading.value = false
    }
}

const fetchListCategories = async () => {
    try {
        const response = await axios.get('/api/admin/data/list-categories', {
            params: { limit: 100 }
        })
        listCategories.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching list categories:', error)
    }
}

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/admin/users', {
            params: { limit: 100 }
        })
        users.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching users:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/lists/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchLists()
}, 300)

const setQuickFilter = (filter) => {
    quickFilter.value = quickFilter.value === filter ? '' : filter
    fetchLists()
}

const clearQuickFilter = () => {
    quickFilter.value = ''
    fetchLists()
}

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedLists.value = lists.value.data.map(list => list.id)
    } else {
        selectedLists.value = []
    }
}


const deleteList = async (list) => {
    if (!confirm(`Are you sure you want to delete "${list.name}"? This action cannot be undone.`)) return

    try {
        await axios.delete(`/api/admin/lists/${list.id}`)
        fetchLists()
        fetchStats()
    } catch (error) {
        alert('Error deleting list: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

const bulkUpdateVisibility = async () => {
    const visibility = prompt('Enter visibility (public or private):')
    if (!visibility || !['public', 'private'].includes(visibility)) return

    try {
        await axios.post('/api/admin/lists/bulk-update', {
            list_ids: selectedLists.value,
            action: 'update_visibility',
            is_public: visibility === 'public'
        })
        selectedLists.value = []
        fetchLists()
        fetchStats()
    } catch (error) {
        alert('Error updating visibility')
    }
}

const bulkToggleFeatured = async () => {
    try {
        await axios.post('/api/admin/lists/bulk-update', {
            list_ids: selectedLists.value,
            action: 'toggle_featured'
        })
        selectedLists.value = []
        fetchLists()
        fetchStats()
    } catch (error) {
        alert('Error toggling featured status')
    }
}

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedLists.value.length} lists?`)) return

    try {
        await axios.post('/api/admin/lists/bulk-update', {
            list_ids: selectedLists.value,
            action: 'delete'
        })
        selectedLists.value = []
        fetchLists()
        fetchStats()
    } catch (error) {
        alert('Error deleting lists')
    }
}

const saveList = async () => {
    processing.value = true
    errors.value = {}

    try {
        if (editingList.value) {
            await axios.put(`/api/admin/lists/${editingList.value.id}`, form)
        } else {
            await axios.post('/api/admin/lists', form)
        }
        closeModal()
        fetchLists()
        fetchStats()
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            alert('Error saving list: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const manageItems = (list) => {
    selectedList.value = list
    showItemsModal.value = true
}


const closeItemsModal = () => {
    showItemsModal.value = false
    selectedList.value = null
}

const getVisibilityBadgeClass = (isPublic) => {
    return isPublic 
        ? 'bg-green-100 text-green-800' 
        : 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'on_hold':
            return 'bg-red-100 text-red-800'
        case 'draft':
            return 'bg-yellow-100 text-yellow-800'
        case 'active':
        default:
            return 'bg-blue-100 text-blue-800'
    }
}

const getStatusLabel = (status) => {
    switch (status) {
        case 'on_hold':
            return 'On Hold'
        case 'draft':
            return 'Draft'
        case 'active':
        default:
            return 'Active'
    }
}

const putOnHold = async (list) => {
    const reason = prompt('Please provide a reason for putting this list on hold (e.g., TOS violation):')
    if (!reason) return

    try {
        await axios.post(`/api/admin/lists/${list.id}/status`, {
            status: 'on_hold',
            reason: reason
        })
        fetchLists()
        alert(`List "${list.name}" has been put on hold.`)
    } catch (error) {
        alert('Error updating list status: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

const reactivateList = async (list) => {
    if (!confirm(`Are you sure you want to reactivate "${list.name}"?`)) return

    try {
        await axios.post(`/api/admin/lists/${list.id}/status`, {
            status: 'active'
        })
        fetchLists()
        alert(`List "${list.name}" has been reactivated.`)
    } catch (error) {
        alert('Error updating list status: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

const bulkPutOnHold = async () => {
    const reason = prompt('Please provide a reason for putting these lists on hold:')
    if (!reason) return

    try {
        await axios.post('/api/admin/lists/bulk-update', {
            list_ids: selectedLists.value,
            action: 'update_status',
            status: 'on_hold',
            reason: reason
        })
        selectedLists.value = []
        fetchLists()
        fetchStats()
        alert(`${selectedLists.value.length} lists have been put on hold.`)
    } catch (error) {
        alert('Error updating lists: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

// Initialize
onMounted(() => {
    fetchLists().then(() => {
        // Debug: Check first list with channel owner
        const channelList = lists.value.data?.find(l => l.owner_type === 'App\\Models\\Channel')
        if (channelList) {
            console.log('Channel list found:', {
                owner_type: channelList.owner_type,
                owner: channelList.owner,
                channel_data: channelList.channel_data,
                user: channelList.user
            })
        } else {
            console.log('No channel lists found in:', lists.value.data?.map(l => ({
                id: l.id,
                name: l.name,
                owner_type: l.owner_type
            })))
        }
    })
    fetchListCategories()
    fetchUsers()
    fetchStats()
})
</script>