<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-xl font-semibold text-gray-900">Registration Waitlist</h1>
                            <p class="mt-2 text-sm text-gray-700">
                                Manage users waiting for registration access.
                            </p>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                            <button
                                @click="inviteSelected"
                                :disabled="selectedEntries.length === 0"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Invite Selected
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Waitlist</dt>
                                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.total }}</dd>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.pending }}</dd>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <dt class="text-sm font-medium text-gray-500 truncate">Invited</dt>
                                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.invited }}</dd>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="mt-8 flex flex-col">
                        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="relative w-12 px-6 sm:w-16 sm:px-8">
                                                    <input
                                                        type="checkbox"
                                                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                                                        :checked="selectedEntries.length === entries.length && entries.length > 0"
                                                        :indeterminate="selectedEntries.length > 0 && selectedEntries.length < entries.length"
                                                        @change="toggleAll"
                                                    />
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                    <span class="sr-only">Actions</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="entry in entries" :key="entry.id">
                                                <td class="relative w-12 px-6 sm:w-16 sm:px-8">
                                                    <div v-if="selectedEntries.includes(entry.id)" class="absolute inset-y-0 left-0 w-0.5 bg-indigo-600"></div>
                                                    <input
                                                        type="checkbox"
                                                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                                                        :value="entry.id"
                                                        v-model="selectedEntries"
                                                    />
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ entry.email }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ entry.name || '-' }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                    <span :class="getStatusClass(entry.status)" class="inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                                        {{ entry.status }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ formatDate(entry.created_at) }}</td>
                                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                    <button
                                                        v-if="entry.status === 'pending'"
                                                        @click="inviteOne(entry)"
                                                        class="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        Invite
                                                    </button>
                                                    <button
                                                        v-if="entry.message"
                                                        @click="showMessage(entry)"
                                                        class="ml-4 text-gray-600 hover:text-gray-900"
                                                    >
                                                        View Message
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.total > pagination.per_page" class="mt-6">
                        <nav class="flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <button
                                    @click="changePage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page === 1"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Previous
                                </button>
                                <button
                                    @click="changePage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page === pagination.last_page"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Next
                                </button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing
                                        <span class="font-medium">{{ pagination.from }}</span>
                                        to
                                        <span class="font-medium">{{ pagination.to }}</span>
                                        of
                                        <span class="font-medium">{{ pagination.total }}</span>
                                        results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <button
                                            @click="changePage(pagination.current_page - 1)"
                                            :disabled="pagination.current_page === 1"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="changePage(pagination.current_page + 1)"
                                            :disabled="pagination.current_page === pagination.last_page"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Modal -->
        <div v-if="showMessageModal" class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showMessageModal = false"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Message from {{ selectedEntry.name || selectedEntry.email }}</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">{{ selectedEntry.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="button"
                            @click="showMessageModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// State
const entries = ref([])
const selectedEntries = ref([])
const stats = ref({ total: 0, pending: 0, invited: 0 })
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0
})
const showMessageModal = ref(false)
const selectedEntry = ref(null)

// Methods
const loadWaitlist = async (page = 1) => {
    try {
        const response = await axios.get(`/api/admin/waitlist?page=${page}`)
        entries.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to
        }
        
        // Load stats
        const statsResponse = await axios.get('/api/admin/waitlist/stats')
        stats.value = statsResponse.data
    } catch (error) {
        console.error('Failed to load waitlist:', error)
        alert('Failed to load waitlist. Please try again.')
    }
}

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedEntries.value = entries.value.map(e => e.id)
    } else {
        selectedEntries.value = []
    }
}

const inviteOne = async (entry) => {
    if (!confirm(`Send invitation to ${entry.email}?`)) {
        return
    }
    
    try {
        await axios.post(`/api/admin/waitlist/${entry.id}/invite`)
        await loadWaitlist(pagination.value.current_page)
        alert('Invitation sent successfully!')
    } catch (error) {
        console.error('Failed to send invitation:', error)
        alert('Failed to send invitation. Please try again.')
    }
}

const inviteSelected = async () => {
    if (selectedEntries.value.length === 0) {
        return
    }
    
    if (!confirm(`Send invitations to ${selectedEntries.value.length} selected entries?`)) {
        return
    }
    
    try {
        await axios.post('/api/admin/waitlist/invite-bulk', {
            ids: selectedEntries.value
        })
        selectedEntries.value = []
        await loadWaitlist(pagination.value.current_page)
        alert('Invitations sent successfully!')
    } catch (error) {
        console.error('Failed to send invitations:', error)
        alert('Failed to send invitations. Please try again.')
    }
}

const showMessage = (entry) => {
    selectedEntry.value = entry
    showMessageModal.value = true
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadWaitlist(page)
    }
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        invited: 'bg-blue-100 text-blue-800',
        registered: 'bg-green-100 text-green-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
    loadWaitlist()
})
</script>