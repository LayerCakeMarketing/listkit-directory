<template>
    <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Pending Places</h1>
            <p class="mt-2 text-gray-600">Review and approve places submitted by users</p>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">Pending Review</p>
                        <p class="text-2xl font-bold text-yellow-900">{{ stats.pending || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">Approved Today</p>
                        <p class="text-2xl font-bold text-green-900">{{ stats.approvedToday || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Rejected Today</p>
                        <p class="text-2xl font-bold text-red-900">{{ stats.rejectedToday || 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input 
                            v-model="filters.search" 
                            @input="debouncedSearch"
                            type="text" 
                            placeholder="Search places or submitters..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    <div class="flex gap-2">
                        <select 
                            v-model="filters.sort_by"
                            @change="fetchPendingPlaces"
                            class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="created_at">Date Created</option>
                            <option value="title">Place Name</option>
                            <option value="created_by">Submitted By</option>
                        </select>
                        <select 
                            v-model="filters.sort_order"
                            @change="fetchPendingPlaces"
                            class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="desc">Newest First</option>
                            <option value="asc">Oldest First</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && places.length === 0" class="bg-white shadow rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No pending places</h3>
            <p class="mt-1 text-sm text-gray-500">All places have been reviewed.</p>
        </div>

        <!-- Places List -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Place
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Submitted By
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date Created
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="place in places" :key="place.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ place.title }}</div>
                                <div class="text-sm text-gray-500">{{ place.category?.name || 'Uncategorized' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ place.location_display }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ place.submitted_by }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(place.created_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <button
                                    @click="viewPlace(place)"
                                    class="text-gray-600 hover:text-gray-900"
                                    title="View"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <router-link
                                    :to="`/places/${place.id}/edit`"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Edit"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </router-link>
                                <button
                                    @click="showApproveModal(place)"
                                    class="text-green-600 hover:text-green-900"
                                    title="Approve"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                                <button
                                    @click="showRejectModal(place)"
                                    class="text-red-600 hover:text-red-900"
                                    title="Reject"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="flex space-x-2">
                <button
                    @click="previousPage"
                    :disabled="!pagination.prev_page_url"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </button>
                <button
                    @click="nextPage"
                    :disabled="!pagination.next_page_url"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModalState" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeRejectModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Reject Place
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to reject "{{ selectedPlace?.title }}"?
                        </p>
                        <div>
                            <label for="rejection_reason" class="block text-sm font-medium text-gray-700">
                                Rejection Reason (optional)
                            </label>
                            <textarea
                                v-model="rejectionReason"
                                id="rejection_reason"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Provide a reason for rejection..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="rejectPlace"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Reject
                        </button>
                        <button
                            @click="closeRejectModal"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Approve Modal -->
        <div v-if="showApproveModalState" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeApproveModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Approve Place
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">
                            You are about to approve "{{ selectedPlace?.title }}".
                        </p>
                        <div>
                            <label for="approval_notes" class="block text-sm font-medium text-gray-700">
                                Approval Notes (optional)
                            </label>
                            <textarea
                                v-model="approvalNotes"
                                id="approval_notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Add any notes about the approval..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="approvePlace"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Approve
                        </button>
                        <button
                            @click="closeApproveModal"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

const router = useRouter()

const loading = ref(true)
const places = ref([])
const stats = ref({})
const showRejectModalState = ref(false)
const showApproveModalState = ref(false)
const selectedPlace = ref(null)
const rejectionReason = ref('')
const approvalNotes = ref('')

const filters = ref({
    search: '',
    sort_by: 'created_at',
    sort_order: 'desc',
    limit: 20
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
    next_page_url: null,
    prev_page_url: null
})

const fetchPendingPlaces = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/places/pending', {
            params: {
                ...filters.value,
                page
            }
        })
        
        places.value = response.data.data
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
            next_page_url: response.data.next_page_url,
            prev_page_url: response.data.prev_page_url
        }
    } catch (error) {
        console.error('Error fetching pending places:', error)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/places/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchPendingPlaces()
}, 300)

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const viewPlace = (place) => {
    // For pending places, always open the edit page
    router.push(`/places/${place.id}/edit`)
}

const showApproveModal = (place) => {
    selectedPlace.value = place
    approvalNotes.value = ''
    showApproveModalState.value = true
}

const closeApproveModal = () => {
    showApproveModalState.value = false
    selectedPlace.value = null
    approvalNotes.value = ''
}

const approvePlace = async () => {
    if (!selectedPlace.value) return

    try {
        await axios.post(`/api/admin/places/${selectedPlace.value.id}/approve`, {
            notes: approvalNotes.value
        })
        
        // Remove from list
        places.value = places.value.filter(p => p.id !== selectedPlace.value.id)
        
        // Update stats
        fetchStats()
        
        // Close modal
        closeApproveModal()
        
        // Show success message
        alert('Place approved successfully!')
    } catch (error) {
        console.error('Error approving place:', error)
        alert('Failed to approve place. Please try again.')
    }
}

const showRejectModal = (place) => {
    selectedPlace.value = place
    rejectionReason.value = ''
    showRejectModalState.value = true
}

const closeRejectModal = () => {
    showRejectModalState.value = false
    selectedPlace.value = null
    rejectionReason.value = ''
}

const rejectPlace = async () => {
    if (!selectedPlace.value) return

    try {
        await axios.post(`/api/admin/places/${selectedPlace.value.id}/reject`, {
            reason: rejectionReason.value
        })
        
        // Remove from list
        places.value = places.value.filter(p => p.id !== selectedPlace.value.id)
        
        // Update stats
        fetchStats()
        
        // Close modal
        closeRejectModal()
        
        // Show success message
        alert('Place rejected.')
    } catch (error) {
        console.error('Error rejecting place:', error)
        alert('Failed to reject place. Please try again.')
    }
}

const nextPage = () => {
    if (pagination.value.next_page_url) {
        fetchPendingPlaces(pagination.value.current_page + 1)
    }
}

const previousPage = () => {
    if (pagination.value.prev_page_url) {
        fetchPendingPlaces(pagination.value.current_page - 1)
    }
}

onMounted(() => {
    fetchPendingPlaces()
    fetchStats()
})
</script>