<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Places</h1>
        <p class="mt-2 text-gray-600">Manage your business listings and claimed places</p>
      </div>

      <!-- Pending Claims Alert -->
      <div v-if="pendingClaimsCount > 0" class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
              You have {{ pendingClaimsCount }} pending claim{{ pendingClaimsCount === 1 ? '' : 's' }}
            </h3>
            <p class="mt-1 text-sm text-yellow-700">
              {{ pendingClaimsCount === 1 ? 'Your claim is' : 'Your claims are' }} being reviewed. You'll receive an email once {{ pendingClaimsCount === 1 ? 'it\'s' : 'they\'re' }} processed.
            </p>
          </div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Filter Tabs -->
          <div class="flex space-x-1">
            <button
              v-for="filter in typeFilters"
              :key="filter.value"
              @click="selectedType = filter.value"
              class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
              :class="{
                'bg-indigo-600 text-white': selectedType === filter.value,
                'text-gray-700 hover:bg-gray-100': selectedType !== filter.value
              }"
            >
              {{ filter.label }}
              <span 
                v-if="filter.count !== undefined" 
                class="ml-2 text-xs"
                :class="{
                  'text-indigo-200': selectedType === filter.value,
                  'text-gray-500': selectedType !== filter.value
                }"
              >
                ({{ filter.count }})
              </span>
            </button>
          </div>

          <!-- Search and Create -->
          <div class="flex items-center gap-4">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search places..."
                class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            </div>
            <router-link
              to="/places/create"
              class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700"
            >
              <PlusIcon class="h-5 w-5 mr-2" />
              Add Place
            </router-link>
          </div>
        </div>

        <!-- Status Filter -->
        <div class="mt-4 flex items-center space-x-4">
          <span class="text-sm text-gray-700">Status:</span>
          <select
            v-model="selectedStatus"
            class="mt-1 block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
          >
            <option value="all">All Statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="pending_review">Pending Review</option>
          </select>
        </div>
      </div>

      <!-- Places List -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="mt-2 text-gray-600">Loading your places...</p>
      </div>

      <div v-else-if="places.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
        <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-lg font-medium text-gray-900">No places found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try adjusting your search.' : 'Get started by creating a new place or claiming an existing one.' }}
        </p>
        <div class="mt-6">
          <router-link
            to="/places/create"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700"
          >
            <PlusIcon class="h-5 w-5 mr-2" />
            Add Your First Place
          </router-link>
        </div>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="place in places"
          :key="place.id"
          class="bg-white rounded-lg shadow hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center">
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ place.name }}
                  </h3>
                  
                  <!-- Ownership Badge -->
                  <span
                    class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-blue-100 text-blue-800': place.ownership_type === 'created',
                      'bg-green-100 text-green-800': place.ownership_type === 'claimed'
                    }"
                  >
                    {{ place.ownership_type === 'created' ? 'Created' : 'Claimed' }}
                  </span>

                  <!-- Tier Badge (for claimed businesses) -->
                  <span
                    v-if="place.claim_tier"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-gray-100 text-gray-800': place.claim_tier === 'free',
                      'bg-purple-100 text-purple-800': place.claim_tier === 'tier1',
                      'bg-gold-100 text-gold-800': place.claim_tier === 'tier2'
                    }"
                  >
                    {{ getTierLabel(place.claim_tier) }}
                  </span>
                </div>

                <div class="mt-1 flex items-center text-sm text-gray-500">
                  <MapPinIcon class="h-4 w-4 mr-1" />
                  <span v-if="place.cityRegion">{{ place.cityRegion.name }}, </span>
                  <span v-if="place.stateRegion">{{ place.stateRegion.name }}</span>
                  <span v-if="place.category" class="ml-3">
                    <TagIcon class="inline h-4 w-4 mr-1" />
                    {{ place.category.name }}
                  </span>
                </div>

                <!-- Status -->
                <div class="mt-2">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800': place.status === 'published',
                      'bg-gray-100 text-gray-800': place.status === 'draft',
                      'bg-yellow-100 text-yellow-800': place.status === 'pending_review'
                    }"
                  >
                    {{ getStatusLabel(place.status) }}
                  </span>
                  
                  <!-- Claim Status (if applicable) -->
                  <span
                    v-if="place.claim_status"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-yellow-100 text-yellow-800': place.claim_status === 'pending',
                      'bg-green-100 text-green-800': place.claim_status === 'approved',
                      'bg-red-100 text-red-800': place.claim_status === 'rejected',
                      'bg-gray-100 text-gray-800': place.claim_status === 'expired'
                    }"
                  >
                    {{ getClaimStatusMessage(place) }}
                  </span>
                  
                  <!-- Verification Fee Status -->
                  <span
                    v-if="place.claim_status === 'pending' && place.verification_fee_paid"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                  >
                    <CheckCircleIcon class="h-3 w-3 mr-1" />
                    Fee Paid
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="ml-4 flex items-center space-x-2">
                <router-link
                  v-if="place.status === 'published'"
                  :to="`/places/${place.slug}`"
                  class="p-2 text-gray-400 hover:text-gray-600"
                  title="View"
                >
                  <EyeIcon class="h-5 w-5" />
                </router-link>
                <router-link
                  v-if="canEdit(place)"
                  :to="`/places/${place.id}/edit`"
                  class="p-2 text-gray-400 hover:text-gray-600"
                  title="Edit"
                >
                  <PencilIcon class="h-5 w-5" />
                </router-link>
                <button
                  v-else-if="place.ownership_type === 'claimed' && place.claim_status === 'pending'"
                  class="p-2 text-gray-300 cursor-not-allowed"
                  title="Edit available after claim approval"
                  disabled
                >
                  <PencilIcon class="h-5 w-5" />
                </button>
                <button
                  v-if="place.ownership_type === 'claimed' && place.claim_status === 'approved' && place.claim_tier !== 'tier2'"
                  @click="upgradeTier(place)"
                  class="p-2 text-indigo-600 hover:text-indigo-800"
                  title="Upgrade"
                >
                  <ArrowUpCircleIcon class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- Quick Stats -->
            <div v-if="place.status === 'published'" class="mt-4 grid grid-cols-4 gap-4 text-center">
              <div class="text-sm">
                <p class="text-gray-500">Views</p>
                <p class="font-semibold">{{ place.view_count || 0 }}</p>
              </div>
              <div class="text-sm">
                <p class="text-gray-500">Saves</p>
                <p class="font-semibold">{{ place.save_count || 0 }}</p>
              </div>
              <div class="text-sm">
                <p class="text-gray-500">Reviews</p>
                <p class="font-semibold">{{ place.review_count || 0 }}</p>
              </div>
              <div class="text-sm">
                <p class="text-gray-500">Rating</p>
                <p class="font-semibold">{{ place.average_rating || 'N/A' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ChevronLeftIcon class="h-5 w-5" />
          </button>
          
          <button
            v-for="page in displayedPages"
            :key="page"
            @click="currentPage = page"
            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
            :class="{
              'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': currentPage === page,
              'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': currentPage !== page
            }"
          >
            {{ page }}
          </button>
          
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ChevronRightIcon class="h-5 w-5" />
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { 
  MagnifyingGlassIcon, 
  PlusIcon, 
  BuildingOfficeIcon,
  MapPinIcon,
  TagIcon,
  EyeIcon,
  PencilIcon,
  ArrowUpCircleIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

// State
const places = ref([])
const loading = ref(true)
const searchQuery = ref('')
const selectedType = ref('all')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const totalCount = ref(0)

// Filter counts
const createdCount = ref(0)
const claimedCount = ref(0)

// Computed
const typeFilters = computed(() => [
  { label: 'All Places', value: 'all', count: totalCount.value },
  { label: 'Created', value: 'created', count: createdCount.value },
  { label: 'Claimed', value: 'claimed', count: claimedCount.value }
])

const displayedPages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const pendingClaimsCount = computed(() => {
  return places.value.filter(p => p.claim_status === 'pending').length
})

// Methods
const fetchPlaces = async () => {
  try {
    loading.value = true
    
    const params = {
      type: selectedType.value,
      status: selectedStatus.value,
      page: currentPage.value
    }
    
    if (searchQuery.value) {
      params.q = searchQuery.value
    }
    
    const response = await axios.get('/api/my-places', { params })
    
    places.value = response.data.data
    totalPages.value = response.data.last_page
    totalCount.value = response.data.total
    
    // Update counts (would need separate API calls or response to include these)
    // For now, we'll estimate based on current results
    updateCounts()
  } catch (error) {
    console.error('Error fetching places:', error)
  } finally {
    loading.value = false
  }
}

const updateCounts = () => {
  // In a real implementation, these counts would come from the API
  // For now, we'll count from the current results
  createdCount.value = places.value.filter(p => p.ownership_type === 'created').length
  claimedCount.value = places.value.filter(p => p.ownership_type === 'claimed').length
}

const canEdit = (place) => {
  // Can edit if user created it or has an approved claim
  if (place.ownership_type === 'created') return true
  if (place.ownership_type === 'claimed' && place.claim_status === 'approved') return true
  return false
}

const getStatusLabel = (status) => {
  const labels = {
    'published': 'Published',
    'draft': 'Draft',
    'pending_review': 'Pending Review'
  }
  return labels[status] || status
}

const getClaimStatusLabel = (status) => {
  const labels = {
    'pending': 'Pending',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'expired': 'Expired'
  }
  return labels[status] || status
}

const getClaimStatusMessage = (place) => {
  switch(place.claim_status) {
    case 'pending':
      if (place.verified_at) {
        return 'Claim pending review'
      } else {
        return 'Verification in progress'
      }
    case 'approved':
      return 'Claim approved'
    case 'rejected':
      return 'Claim rejected'
    case 'expired':
      return 'Claim expired'
    default:
      return 'Claim ' + place.claim_status
  }
}

const getTierLabel = (tier) => {
  const labels = {
    'free': 'Free',
    'tier1': 'Professional',
    'tier2': 'Premium'
  }
  return labels[tier] || tier
}

const upgradeTier = (place) => {
  // Navigate to upgrade page
  router.push(`/places/${place.slug}/upgrade`)
}

// Watchers
watch([searchQuery, selectedType, selectedStatus, currentPage], () => {
  fetchPlaces()
})

// Lifecycle
onMounted(() => {
  if (!authStore.user) {
    router.push('/login')
    return
  }
  
  fetchPlaces()
})
</script>