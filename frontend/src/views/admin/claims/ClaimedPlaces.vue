<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Business Claims Management
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Review and manage business ownership claims
          </p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="p-3 bg-yellow-100 rounded-md">
                  <ClockIcon class="h-6 w-6 text-yellow-600" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                  <dd class="text-lg font-semibold text-gray-900">{{ statistics.pending_claims || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="p-3 bg-green-100 rounded-md">
                  <CheckCircleIcon class="h-6 w-6 text-green-600" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                  <dd class="text-lg font-semibold text-gray-900">{{ statistics.approved_claims || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="p-3 bg-red-100 rounded-md">
                  <XCircleIcon class="h-6 w-6 text-red-600" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                  <dd class="text-lg font-semibold text-gray-900">{{ statistics.rejected_claims || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="p-3 bg-indigo-100 rounded-md">
                  <CurrencyDollarIcon class="h-6 w-6 text-indigo-600" />
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Fees Collected</dt>
                  <dd class="text-lg font-semibold text-gray-900">${{ statistics.verification_fees?.total_amount || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white shadow rounded-lg mb-6 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <!-- Search -->
          <div class="sm:col-span-2">
            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                id="search"
                v-model="filters.search"
                @input="debouncedFetch"
                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                placeholder="Search by business name or email..."
              >
            </div>
          </div>

          <!-- Status Filter -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select
              id="status"
              v-model="filters.status"
              @change="fetchClaims"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="expired">Expired</option>
            </select>
          </div>

          <!-- Verification Method Filter -->
          <div>
            <label for="method" class="block text-sm font-medium text-gray-700">Method</label>
            <select
              id="method"
              v-model="filters.verification_method"
              @change="fetchClaims"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Methods</option>
              <option value="email">Email</option>
              <option value="phone">Phone</option>
              <option value="document">Document</option>
            </select>
          </div>

          <!-- Payment Status Filter -->
          <div>
            <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment Status</label>
            <select
              id="payment_status"
              v-model="filters.payment_status"
              @change="fetchClaims"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Claims</option>
              <option value="paid">Paid Only</option>
              <option value="unpaid">Unpaid Only</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Payment Filter Notice -->
      <div v-if="filters.payment_status === 'paid'" class="mb-4 bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-700">
              Showing only claims with verified payments (verification fee paid or active subscription).
            </p>
          </div>
        </div>
      </div>

      <!-- Claims Table -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-flex items-center">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2">Loading claims...</span>
          </div>
        </div>

        <ul v-else-if="claims.length > 0" class="divide-y divide-gray-200">
          <li v-for="claim in claims" :key="claim.id">
            <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <BuildingStorefrontIcon class="h-6 w-6 text-gray-500" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ claim.place.title }}
                    </div>
                    <div class="text-sm text-gray-500">
                      Claimed by {{ claim.user.firstname }} {{ claim.user.lastname }} ({{ claim.user.email }})
                    </div>
                    <div class="mt-1 flex items-center text-xs text-gray-500">
                      <span>{{ formatDate(claim.created_at) }}</span>
                      <span class="mx-1">•</span>
                      <span>{{ claim.verification_method }}</span>
                      <span class="mx-1">•</span>
                      <span :class="{
                        'text-gray-600': claim.tier === 'free',
                        'text-indigo-600': claim.tier === 'tier1',
                        'text-purple-600': claim.tier === 'tier2'
                      }">
                        {{ getTierLabel(claim.tier) }}
                      </span>
                      <span v-if="claim.verification_fee_paid" class="mx-1">•</span>
                      <span v-if="claim.verification_fee_paid" class="text-green-600">
                        ${{ claim.verification_fee_amount || '5.99' }} fee{{ claim.fee_kept ? ' (tipped ☕)' : '' }}
                      </span>
                      <span v-if="claim.fee_refunded_at" class="mx-1">•</span>
                      <span v-if="claim.fee_refunded_at" class="text-blue-600">
                        Fee refunded
                      </span>
                    </div>
                  </div>
                </div>
                <div class="ml-2 flex-shrink-0 flex items-center space-x-2">
                  <!-- Status Badge -->
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-yellow-100 text-yellow-800': claim.status === 'pending',
                      'bg-green-100 text-green-800': claim.status === 'approved',
                      'bg-red-100 text-red-800': claim.status === 'rejected',
                      'bg-gray-100 text-gray-800': claim.status === 'expired'
                    }"
                  >
                    {{ claim.status }}
                  </span>
                  
                  <!-- Actions -->
                  <button
                    @click="viewClaim(claim)"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                  >
                    Review
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else class="text-center py-12">
          <BuildingStorefrontIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No claims found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ filters.search || filters.status || filters.verification_method ? 'Try adjusting your filters' : 'No business claims have been submitted yet' }}
          </p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing <span class="font-medium">{{ (currentPage - 1) * 20 + 1 }}</span> to 
                <span class="font-medium">{{ Math.min(currentPage * 20, total) }}</span> of 
                <span class="font-medium">{{ total }}</span> results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="prevPage"
                  :disabled="currentPage === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                >
                  <ChevronLeftIcon class="h-5 w-5" />
                </button>
                <button
                  @click="nextPage"
                  :disabled="currentPage === totalPages"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                >
                  <ChevronRightIcon class="h-5 w-5" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <ClaimReviewModal
      v-if="selectedClaim"
      :claim="selectedClaim"
      @close="selectedClaim = null"
      @approved="handleApproval"
      @rejected="handleRejection"
      @unclaimed="handleUnclaim"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import ClaimReviewModal from '@/components/admin/ClaimReviewModal.vue'
import { 
  BuildingStorefrontIcon, 
  ClockIcon, 
  CheckCircleIcon, 
  XCircleIcon, 
  CurrencyDollarIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
const router = useRouter()

// Simple debounce function to replace lodash dependency
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// State
const claims = ref([])
const statistics = ref({})
const loading = ref(true)
const selectedClaim = ref(null)
const currentPage = ref(1)
const total = ref(0)
const totalPages = ref(0)

// Filters
const filters = ref({
  search: '',
  status: '',
  payment_status: '',
  verification_method: ''
})

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
  try {
    loading.value = true
    const params = {
      page: currentPage.value,
      ...filters.value
    }

    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (!params[key]) delete params[key]
    })

    const response = await axios.get('/api/admin/claims', { params })
    claims.value = response.data.data
    total.value = response.data.total
    totalPages.value = response.data.last_page
  } catch (error) {
    console.error('Failed to fetch claims:', error)
  } finally {
    loading.value = false
  }
}

const debouncedFetch = debounce(() => {
  currentPage.value = 1
  fetchClaims()
}, 300)

const viewClaim = (claim) => {
  selectedClaim.value = claim
}

const handleApproval = async () => {
  // Refresh data
  await Promise.all([
    fetchClaims(),
    fetchStatistics()
  ])
  
  // Show success message
  showSuccessToast('Claim approved successfully!')
  
  // Close modal
  selectedClaim.value = null
}

const handleRejection = async () => {
  // Refresh data
  await Promise.all([
    fetchClaims(),
    fetchStatistics()
  ])
  
  // Show success message
  showSuccessToast('Claim rejected.')
  
  // Close modal
  selectedClaim.value = null
}

const handleUnclaim = async () => {
  // Refresh data
  await Promise.all([
    fetchClaims(),
    fetchStatistics()
  ])
  
  // Show success message
  showSuccessToast('Claim removed successfully.')
  
  // Close modal
  selectedClaim.value = null
}

// Toast notification helper
const showSuccessToast = (message) => {
  // Create a simple toast notification
  const toast = document.createElement('div')
  toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2 transition-all duration-300 transform translate-x-0'
  toast.innerHTML = `
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <span>${message}</span>
  `
  
  document.body.appendChild(toast)
  
  // Auto remove after 3 seconds
  setTimeout(() => {
    toast.classList.add('translate-x-full', 'opacity-0')
    setTimeout(() => {
      document.body.removeChild(toast)
    }, 300)
  }, 3000)
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    fetchClaims()
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    fetchClaims()
  }
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

const getTierLabel = (tier) => {
  const labels = {
    'free': 'Free',
    'tier1': 'Professional',
    'tier2': 'Premium'
  }
  return labels[tier] || tier
}

// Lifecycle
onMounted(() => {
  fetchStatistics()
  fetchClaims()
})
</script>