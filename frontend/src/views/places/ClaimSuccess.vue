<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Success Card -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Success Header -->
        <div class="bg-green-500 px-6 py-8 text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-600 mb-4">
            <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-white mb-2">Claim Submitted Successfully!</h1>
          <p class="text-green-100 text-lg">Your business claim has been received</p>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
          <!-- What happens next -->
          <div class="bg-blue-50 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">What happens next?</h3>
            <ul class="space-y-2 text-sm text-blue-800">
              <li class="flex items-start">
                <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>We'll review your claim within 24-48 hours</span>
              </li>
              <li class="flex items-start">
                <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>You'll receive an email once your claim is approved</span>
              </li>
              <li class="flex items-start" v-if="claim?.verification_fee_paid && !claim?.fee_kept">
                <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Your $5.99 verification fee will be refunded upon approval</span>
              </li>
              <li class="flex items-start">
                <svg class="h-5 w-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Once approved, you'll have full access to manage your business listing</span>
              </li>
            </ul>
          </div>

          <!-- Claim Details -->
          <div v-if="claim" class="border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Claim Details</h3>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between">
                <dt class="text-gray-600">Business:</dt>
                <dd class="font-medium text-gray-900">{{ claim.place?.name }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-600">Claim ID:</dt>
                <dd class="font-medium text-gray-900">#{{ claim.id }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-600">Selected Tier:</dt>
                <dd class="font-medium text-gray-900">
                  <span v-if="claim.tier === 'free'" class="text-gray-700">Free</span>
                  <span v-else-if="claim.tier === 'tier1'" class="text-indigo-600">Professional ($9.99/mo)</span>
                  <span v-else-if="claim.tier === 'tier2'" class="text-purple-600">Premium ($19.99/mo)</span>
                </dd>
              </div>
              <div v-if="claim.verification_fee_paid" class="flex justify-between">
                <dt class="text-gray-600">Verification Fee:</dt>
                <dd class="font-medium">
                  <span v-if="claim.fee_kept" class="text-green-600">$5.99 (Thank you for your support! ☕)</span>
                  <span v-else class="text-gray-900">$5.99 (Refundable)</span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Thank you message for fee kept -->
          <div v-if="claim?.fee_kept" class="bg-green-50 rounded-lg p-4 text-center">
            <p class="text-green-800">
              <span class="font-semibold">Thank you for your support!</span> Your contribution helps us maintain and improve our platform. ☕
            </p>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <router-link 
              :to="{ name: 'PlaceCanonical', params: { state: place.region?.state_slug, city: place.region?.city_slug, category: place.category?.slug, entry: place.slug } }"
              class="flex-1 text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
            >
              View Your Business Listing
            </router-link>
            <router-link 
              :to="{ name: 'MyPlaces' }"
              class="flex-1 text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
            >
              Go to My Places
            </router-link>
          </div>
        </div>
      </div>

      <!-- Additional Information -->
      <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-3">Need Help?</h3>
        <p class="text-gray-600 text-sm mb-4">
          If you have any questions about your claim or need assistance, please don't hesitate to contact us.
        </p>
        <a href="mailto:support@listerino.com" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
          support@listerino.com
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const claim = ref(null)
const place = ref(null)
const loading = ref(true)
const error = ref(null)

// Get claim ID from query params
const claimId = computed(() => route.query.claim_id)

onMounted(async () => {
  if (!claimId.value) {
    // If no claim ID, redirect to home
    router.push({ name: 'Home' })
    return
  }

  try {
    // Fetch claim details
    const response = await axios.get(`/api/claims/${claimId.value}`)
    claim.value = response.data.data
    
    // Fetch place details
    if (claim.value.place_id) {
      const placeResponse = await axios.get(`/api/places/${claim.value.place_id}`)
      place.value = placeResponse.data.data
    }
  } catch (err) {
    console.error('Error fetching claim details:', err)
    error.value = 'Unable to load claim details'
  } finally {
    loading.value = false
  }
})
</script>