<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Business Claims</h1>
            <p class="text-gray-600 mt-1">Review and manage business ownership claims</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Claims</p>
                        <p class="text-2xl font-bold text-gray-900">{{ statistics.total_claims || 0 }}</p>
                    </div>
                    <div class="p-3 bg-gray-100 rounded-full">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ statistics.pending_claims || 0 }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-green-600">{{ statistics.approved_claims || 0 }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-red-600">{{ statistics.rejected_claims || 0 }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="filters.status" @change="fetchClaims" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Verification Method</label>
                    <select v-model="filters.verification_method" @change="fetchClaims" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Methods</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone</option>
                        <option value="document">Document</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        v-model="filters.search"
                        @input="debounceSearch"
                        type="text"
                        placeholder="Search by business name or user email..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
            </div>
        </div>

        <!-- Claims Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="claim in claims" :key="claim.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ claim.place?.title }}</div>
                                <div class="text-sm text-gray-500">{{ claim.place?.location?.city }}, {{ claim.place?.location?.state }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ claim.user?.name || claim.user?.email }}</div>
                                <div class="text-sm text-gray-500">{{ claim.user?.email }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="getMethodBadgeClass(claim.verification_method)">
                                {{ claim.verification_method }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="getStatusBadgeClass(claim.status)">
                                {{ claim.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(claim.created_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                @click="viewClaim(claim)"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Review
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Loading State -->
            <div v-if="loading" class="p-6 text-center">
                <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <!-- Empty State -->
            <div v-else-if="claims.length === 0" class="p-6 text-center text-gray-500">
                No claims found matching your criteria.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4 flex justify-center">
            <nav class="flex items-center space-x-1">
                <button
                    @click="currentPage = currentPage - 1"
                    :disabled="currentPage === 1"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </button>
                <span class="px-3 py-2 text-sm text-gray-700">
                    Page {{ currentPage }} of {{ totalPages }}
                </span>
                <button
                    @click="currentPage = currentPage + 1"
                    :disabled="currentPage === totalPages"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
            </nav>
        </div>

        <!-- Claim Review Modal -->
        <ClaimReviewModal
            v-if="selectedClaim"
            :claim="selectedClaim"
            @close="selectedClaim = null"
            @updated="handleClaimUpdated"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import ClaimReviewModal from '@/components/admin/ClaimReviewModal.vue'

const router = useRouter()

// State
const claims = ref([])
const statistics = ref({})
const loading = ref(false)
const selectedClaim = ref(null)
const currentPage = ref(1)
const totalItems = ref(0)

// Filters
const filters = ref({
    status: '',
    verification_method: '',
    search: ''
})

// Computed
const totalPages = computed(() => Math.ceil(totalItems.value / 20))

// Methods
const fetchStatistics = async () => {
    try {
        const response = await axios.get('/api/admin/claims/statistics')
        statistics.value = response.data
    } catch (error) {
        console.error('Failed to fetch statistics:', error)
    }
}

const fetchClaims = async () => {
    loading.value = true
    
    try {
        const params = {
            page: currentPage.value,
            ...filters.value
        }
        
        const response = await axios.get('/api/admin/claims', { params })
        claims.value = response.data.data
        totalItems.value = response.data.total || 0
    } catch (error) {
        console.error('Failed to fetch claims:', error)
    } finally {
        loading.value = false
    }
}

const debounceSearch = (() => {
    let timeout
    return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
            currentPage.value = 1
            fetchClaims()
        }, 300)
    }
})()

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getMethodBadgeClass = (method) => {
    const classes = {
        email: 'bg-blue-100 text-blue-800',
        phone: 'bg-purple-100 text-purple-800',
        document: 'bg-indigo-100 text-indigo-800',
        manual: 'bg-gray-100 text-gray-800'
    }
    return classes[method] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const viewClaim = (claim) => {
    selectedClaim.value = claim
}

const handleClaimUpdated = () => {
    selectedClaim.value = null
    fetchClaims()
    fetchStatistics()
}

// Watch for page changes
watch(currentPage, fetchClaims)

// Initialize
onMounted(() => {
    fetchStatistics()
    fetchClaims()
})
</script>