<template>
    <Head title="Directory Entries" />

    <AdminDashboardLayout>
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
                        <div class="flex space-x-2">
                            <Link
                                href="/admin/bulk-import"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Bulk Import
                            </Link>
                            <Link
                                href="/places/create"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-block"
                            >
                                Add New Entry
                            </Link>
                        </div>
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

                        <div v-if="props.entries.data.length === 0" class="text-center py-4 text-gray-500">
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
                                                <div>{{ entry.location.city }}, {{ entry.location.state }}</div>
                                                <div v-if="entry.location.neighborhood" class="text-xs text-gray-400">
                                                    {{ entry.location.neighborhood }}
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-400">N/A</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="relative inline-block text-left">
                                                <button
                                                    @click="toggleDropdown(entry.id)"
                                                    type="button"
                                                    class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500"
                                                >
                                                    Actions
                                                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>

                                                <div
                                                    v-if="openDropdownId === entry.id"
                                                    class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                                >
                                                    <div class="py-1">
                                                        <a
                                                            :href="getEntryViewUrl(entry)"
                                                            target="_blank"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        >
                                                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            View Entry
                                                        </a>
                                                        <Link
                                                            :href="`/places/${entry.id}/edit`"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        >
                                                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Edit Entry
                                                        </Link>
                                                        <button
                                                            @click="deleteEntry(entry)"
                                                            class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-gray-100"
                                                        >
                                                            <svg class="mr-3 h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Delete Entry
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4" v-if="props.entries.links">
                            <Pagination :links="props.entries" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    entries: Object,
    stats: Object,
    categories: Array,
    filters: Object
})

// UI State
const selectedEntries = ref([])
const openDropdownId = ref(null)

// Local filters that sync with props
const filters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    category_id: props.filters?.category_id || '',
    status: props.filters?.status || '',
})

// Computed
const isAllSelected = computed(() => {
    return props.entries.data.length > 0 && 
           selectedEntries.value.length === props.entries.data.length
})


// Methods
const applyFilters = () => {
    router.get('/admin/entries', filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

const debouncedFilter = debounce(applyFilters, 300)

// Watch for filter changes
watch(() => filters.value.search, () => {
    debouncedFilter()
})

watch([() => filters.value.type, () => filters.value.status, () => filters.value.category_id], () => {
    applyFilters()
})


const deleteEntry = (entry) => {
    if (!confirm(`Are you sure you want to delete "${entry.title}"?`)) return
    
    router.delete(`/admin-data/entries/${entry.id}`, {
        onSuccess: () => {
            alert('Entry deleted successfully')
        },
        onError: (errors) => {
            alert('Error deleting entry: ' + (errors.message || 'Unknown error'))
        }
    })
}

const bulkAction = async (action) => {
    if (!confirm(`Are you sure you want to ${action} ${selectedEntries.value.length} entries?`)) return
    
    try {
        await window.axios.post('/admin-data/entries/bulk-update', {
            entry_ids: selectedEntries.value,
            action: action
        })
        
        selectedEntries.value = []
        router.reload()
        alert(`Entries ${action}ed successfully`)
    } catch (error) {
        alert('Error performing bulk action: ' + error.response?.data?.message)
    }
}


const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedEntries.value = []
    } else {
        selectedEntries.value = props.entries.data.map(e => e.id)
    }
}

const toggleDropdown = (entryId) => {
    if (openDropdownId.value === entryId) {
        openDropdownId.value = null
    } else {
        openDropdownId.value = entryId
    }
}

const getEntryViewUrl = (entry) => {
    // If entry has a custom URL, use it
    if (entry.url) {
        return entry.url
    }
    
    // If entry has category with parent, build hierarchical URL
    if (entry.category && entry.category.parent_id && entry.category.parent) {
        const parentSlug = entry.category.parent.slug
        const childSlug = entry.category.slug
        return `/${parentSlug}/${childSlug}/${entry.slug}`
    }
    
    // Default fallback
    return `/places/entry/${entry.slug}`
}

// Close dropdown when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative.inline-block.text-left')) {
            openDropdownId.value = null
        }
    })
})

onUnmounted(() => {
    document.removeEventListener('click', () => {})
})


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

</script>