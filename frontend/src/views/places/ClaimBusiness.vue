<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb and Progress -->
    <div class="bg-white border-b">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex items-center space-x-4 text-sm">
          <router-link to="/places" class="text-gray-500 hover:text-gray-700">Places</router-link>
          <span class="text-gray-300">/</span>
          <router-link 
            v-if="place" 
            :to="`/places/${place.slug}`" 
            class="text-gray-500 hover:text-gray-700"
          >
            {{ place.name }}
          </router-link>
          <span class="text-gray-300">/</span>
          <span class="text-gray-900">Claim Business</span>
        </nav>
        
        <!-- Progress Indicator -->
        <div class="mt-4">
          <div class="flex items-center">
            <div 
              v-for="(step, index) in visibleSteps" 
              :key="step.id"
              class="flex-1"
            >
              <div class="relative">
                <div 
                  v-if="index < visibleSteps.length - 1"
                  class="absolute top-5 w-full h-0.5"
                  :class="getStepIndex(step.id) <= currentStepIndex ? 'bg-indigo-600' : 'bg-gray-200'"
                  style="left: 50%; right: -50%"
                ></div>
                <div class="relative flex flex-col items-center">
                  <div 
                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium"
                    :class="{
                      'bg-indigo-600 text-white': getStepIndex(step.id) <= currentStepIndex,
                      'bg-gray-200 text-gray-500': getStepIndex(step.id) > currentStepIndex
                    }"
                  >
                    {{ index + 1 }}
                  </div>
                  <span class="mt-2 text-xs text-gray-600">{{ step.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Claim Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow p-6">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
              <p class="mt-2 text-gray-600">Loading business details...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error || !place" class="text-center py-12">
              <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">Unable to Load Business</h3>
              <p class="text-gray-600 mb-4">{{ error || 'Business not found' }}</p>
              <router-link
                to="/places"
                class="inline-block px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
              >
                Back to Places
              </router-link>
            </div>

            <!-- Step 1: Verification Method -->
            <div v-else-if="currentStep === 'verification' && place">
              <div class="mb-6 pb-6 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Claiming</h3>
                <h2 class="text-2xl font-bold text-gray-900">{{ place.name }}</h2>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Verify Your Business</h2>
              <p class="text-gray-600 mb-6">Choose how you'd like to verify ownership</p>
              
              <div class="space-y-4">
                <label 
                  v-for="method in verificationMethods" 
                  :key="method.id"
                  class="relative block cursor-pointer"
                >
                  <input 
                    type="radio" 
                    name="verificationMethod" 
                    :value="method.id"
                    v-model="verificationMethod"
                    class="sr-only"
                  >
                  <div 
                    class="border-2 rounded-lg p-4 transition-all"
                    :class="{
                      'border-indigo-600 bg-indigo-50': verificationMethod === method.id,
                      'border-gray-200 hover:border-gray-300': verificationMethod !== method.id
                    }"
                  >
                    <div class="flex items-center">
                      <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mr-4">
                        <component :is="method.icon" class="w-6 h-6 text-gray-600" />
                      </div>
                      <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">{{ method.name }}</h3>
                        <p class="text-sm text-gray-600">{{ method.description }}</p>
                      </div>
                    </div>
                  </div>
                </label>
              </div>

              <!-- Email Input -->
              <div v-if="verificationMethod === 'email'" class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Business Email Address
                </label>
                <input
                  v-model="businessEmail"
                  type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="business@example.com"
                >
              </div>

              <!-- Phone Input -->
              <div v-if="verificationMethod === 'phone'" class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Business Phone Number
                </label>
                <input
                  v-model="businessPhone"
                  type="tel"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="(555) 123-4567"
                >
              </div>

              <!-- Notes -->
              <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Additional Information (Optional)
                </label>
                <textarea
                  v-model="verificationNotes"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Any additional information to help verify your ownership"
                ></textarea>
              </div>
              
              <div class="mt-6 flex justify-end">
                <button
                  @click="nextStep"
                  :disabled="!canProceedFromVerification"
                  class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  Continue
                </button>
              </div>
            </div>

            <!-- Step 2: OTP Verification -->
            <div v-if="currentStep === 'otp' && place">
              <div class="mb-6 pb-6 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Claiming</h3>
                <h2 class="text-2xl font-bold text-gray-900">{{ place.name }}</h2>
              </div>
              <OtpVerification
                :claim="claim"
                :verification-method="verificationMethod"
                @verified="handleVerified"
                @error="handleError"
              />
              
              <div class="mt-6 flex justify-between">
                <button
                  @click="previousStep"
                  class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300"
                >
                  Back
                </button>
              </div>
            </div>

            <!-- Step 3: Document Upload (if applicable) -->
            <div v-if="currentStep === 'documents' && place">
              <div class="mb-6 pb-6 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Claiming</h3>
                <h2 class="text-2xl font-bold text-gray-900">{{ place.name }}</h2>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Upload Verification Documents</h2>
              <p class="text-gray-600 mb-6">Please upload documents that verify your business ownership</p>
              
              <DocumentUpload
                :claim="claim"
                @uploaded="handleDocumentUploaded"
                @error="handleError"
              />
              
              <div class="mt-6 flex justify-between">
                <button
                  @click="previousStep"
                  class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300"
                >
                  Back
                </button>
                <button
                  @click="nextStep"
                  :disabled="!hasUploadedDocuments"
                  class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  Continue
                </button>
              </div>
            </div>

            <!-- Options Step -->
            <div v-if="currentStep === 'options' && place">
              <div class="mb-6 pb-6 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Claiming</h3>
                <h2 class="text-2xl font-bold text-gray-900">{{ place.name }}</h2>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Choose Your Options</h2>
              <p class="text-gray-600 mb-6">You're almost done! Select the plan that best fits your business needs.</p>
              
              <!-- Horizontal tier cards -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label 
                  v-for="tier in tiers" 
                  :key="tier.id"
                  class="relative block cursor-pointer"
                >
                  <input 
                    type="radio" 
                    name="tier" 
                    :value="tier.id"
                    v-model="selectedTier"
                    class="sr-only"
                  >
                  <div 
                    class="border-2 rounded-lg p-4 h-full transition-all"
                    :class="{
                      'border-indigo-600 bg-indigo-50 shadow-lg': selectedTier === tier.id,
                      'border-gray-200 hover:border-gray-300': selectedTier !== tier.id
                    }"
                  >
                    <!-- Popular badge for tier1 -->
                    <div v-if="tier.id === 'tier1'" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                      <span class="bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                        Most Popular
                      </span>
                    </div>
                    
                    <div class="text-center">
                      <h3 class="text-lg font-semibold text-gray-900">{{ tier.name }}</h3>
                      <p class="text-3xl font-bold text-gray-900 mt-2">
                        {{ tier.price === 0 ? 'Free' : `$${tier.price}/mo` }}
                      </p>
                      <!-- Verification fee note for free tier -->
                      <p v-if="tier.id === 'free'" class="text-xs text-gray-500 mt-1">
                        ${{ verificationFeeAmount }} verification fee
                        <br>
                        <span class="text-green-600">(refundable after verification)</span>
                      </p>
                      <ul class="mt-4 space-y-2 text-left">
                        <li v-for="feature in tier.features" :key="feature" class="flex items-start text-sm">
                          <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                          </svg>
                          <span class="text-gray-600">{{ feature }}</span>
                        </li>
                      </ul>
                    </div>
                  </div>
                </label>
              </div>

              <!-- Keep fee as tip checkbox -->
              <div class="mt-6 bg-indigo-50 rounded-lg p-4">
                <label class="flex items-start cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="keepFeeAsTip"
                    class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-3">
                    <span class="block font-medium text-gray-900">
                      Keep the ${{ verificationFeeAmount }} as a coffee tip ☕
                    </span>
                    <span class="block text-sm text-gray-600 mt-1">
                      Support our platform! It helps us keep verifications running smoothly!
                    </span>
                  </span>
                </label>
              </div>
              
              <div class="mt-8 flex justify-between">
                <button
                  @click="previousStep"
                  class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300"
                >
                  Back
                </button>
                <button
                  @click="confirmOptionsAndProceed"
                  class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700"
                >
                  Continue to Payment
                </button>
              </div>
            </div>

            <!-- Step 5: Payment (for paid tiers) -->
            <div v-if="currentStep === 'payment' && place" class="space-y-6">
              <div class="mb-6 pb-6 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Claiming</h3>
                <h2 class="text-2xl font-bold text-gray-900">{{ place.name }}</h2>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Complete Payment</h2>
              
              <!-- Payment breakdown -->
              <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-gray-900 mb-2">Payment Summary</h3>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">{{ getSelectedTierName }} Plan</span>
                    <span class="font-medium">{{ selectedTier === 'free' ? '$0.00' : `$${getSelectedTierPrice}/mo` }}</span>
                  </div>
                  <div v-if="payVerificationFee" class="flex justify-between">
                    <span class="text-gray-600">
                      Verification Fee
                      <span v-if="!keepFeeAsTip" class="text-green-600">(refundable)</span>
                      <span v-else class="text-indigo-600">(tip - thank you!)</span>
                    </span>
                    <span class="font-medium">${{ verificationFeeAmount }}</span>
                  </div>
                  <div class="border-t pt-1 mt-2">
                    <div class="flex justify-between font-semibold">
                      <span>Total Due Now</span>
                      <span>${{ calculateTotalPayment() }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div id="payment-element" class="min-h-[300px]">
                <!-- Stripe Payment Element will be mounted here -->
              </div>
              
              <div v-if="paymentError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                {{ paymentError }}
              </div>
              
              <div class="mt-6 flex justify-between">
                <button
                  @click="previousStep"
                  :disabled="processingPayment"
                  class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 disabled:opacity-50"
                >
                  Back
                </button>
                <button
                  @click="handlePayment"
                  :disabled="processingPayment || !stripe"
                  class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center"
                >
                  <span v-if="processingPayment" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                  </span>
                  <span v-else>Complete Payment</span>
                </button>
              </div>
            </div>

            <!-- Success State -->
            <div v-if="currentStep === 'success' && place" class="text-center py-12">
              <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ place.name }} Claimed Successfully!</h2>
              <p class="text-gray-600 mb-8">
                {{ selectedTier === 'free' 
                  ? 'Your free claim has been submitted. You can now manage your business listing!' 
                  : 'Your claim has been submitted and payment confirmed. Welcome to ' + getSelectedTierName + '!' 
                }}
              </p>
              <div class="space-x-4">
                <router-link
                  to="/myplaces"
                  class="inline-block px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700"
                >
                  View My Places
                </router-link>
                <router-link
                  :to="`/places/${place.slug}`"
                  class="inline-block px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300"
                >
                  Back to Place
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Marketing Panel -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ marketingPanels[currentStep]?.title || 'Why Claim Your Business?' }}
            </h3>
            <div class="prose prose-sm text-gray-600">
              <div v-html="marketingPanels[currentStep]?.content || defaultMarketingContent"></div>
            </div>
            
            <!-- Trust Badges -->
            <div class="mt-6 pt-6 border-t">
              <div class="flex items-center justify-center space-x-4">
                <div class="text-center">
                  <div class="text-2xl font-bold text-indigo-600">10K+</div>
                  <div class="text-xs text-gray-500">Businesses Claimed</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-indigo-600">4.8</div>
                  <div class="text-xs text-gray-500">Average Rating</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { loadStripe } from '@stripe/stripe-js'
import OtpVerification from '@/components/OtpVerification.vue'
import DocumentUpload from '@/components/DocumentUpload.vue'
import { EnvelopeIcon, PhoneIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props
const props = defineProps({
  slug: String
})

// State
const place = ref(null)
const loading = ref(true)
const error = ref(null)

// Claim form state
const currentStep = ref('verification') // Start with verification instead of tier
const currentStepIndex = computed(() => {
  const index = steps.findIndex(s => s.id === currentStep.value)
  return index >= 0 ? index : 0
})

const selectedTier = ref('free') // Default to free tier
const verificationMethod = ref('email')
const businessEmail = ref('')
const businessPhone = ref('')
const verificationNotes = ref('')
const claim = ref(null)
const hasUploadedDocuments = ref(false)

// Verification fee state
const payVerificationFee = ref(true) // Default to paying fee
const keepFeeAsTip = ref(false)
const feePaymentIntent = ref(null)
const verificationFeeAmount = 5.99

// Payment state
const stripe = ref(null)
const elements = ref(null)
const paymentError = ref(null)
const processingPayment = ref(false)

// Steps configuration - Reordered for soft-sell approach
const steps = [
  { id: 'verification', name: 'Verify Business' },
  { id: 'otp', name: 'Confirm' },
  { id: 'documents', name: 'Documents' },
  { id: 'options', name: 'Select Options' },
  { id: 'payment', name: 'Payment' },
  { id: 'success', name: 'Complete' }
]

// Visible steps based on current flow
const visibleSteps = computed(() => {
  // Hide steps that aren't relevant to the current flow
  return steps.filter(step => {
    if (step.id === 'otp' && verificationMethod.value === 'document') return false
    if (step.id === 'documents' && verificationMethod.value !== 'document') return false
    // Always show payment step since verification fee is required
    return true
  })
})

// Helper to get step index
const getStepIndex = (stepId) => {
  return steps.findIndex(s => s.id === stepId)
}

// Tiers configuration
const tiers = [
  {
    id: 'free',
    name: 'Free',
    price: 0,
    features: [
      'Basic business information',
      'Contact details',
      'Opening hours',
      'Appear in search results'
    ]
  },
  {
    id: 'tier1',
    name: 'Professional',
    price: 9.99,
    features: [
      'Everything in Free',
      'Priority listing',
      'Photo gallery (up to 20)',
      'Customer reviews',
      'Analytics dashboard'
    ]
  },
  {
    id: 'tier2',
    name: 'Premium',
    price: 19.99,
    features: [
      'Everything in Professional',
      'Featured placement',
      'Unlimited photos',
      'Video content',
      'Advanced analytics',
      'Custom promotions'
    ]
  }
]

// Verification methods
const verificationMethods = [
  {
    id: 'email',
    name: 'Email Verification',
    description: 'We\'ll send a code to your business email',
    icon: EnvelopeIcon
  },
  {
    id: 'phone',
    name: 'Phone Verification',
    description: 'We\'ll send a code via SMS',
    icon: PhoneIcon
  },
  {
    id: 'document',
    name: 'Document Upload',
    description: 'Upload business license or other documents',
    icon: DocumentTextIcon
  }
]

// Marketing panels content - Updated for soft-sell approach
const marketingPanels = {
  verification: {
    title: 'Start Managing Your Business',
    content: `
      <p class="text-lg font-medium mb-4">Join thousands of business owners who are already benefiting!</p>
      <ul class="space-y-3">
        <li class="flex items-start">
          <span class="text-green-500 mr-2">✓</span>
          <span><strong>Free tier available</strong> - Start with basic features at no cost</span>
        </li>
        <li class="flex items-start">
          <span class="text-green-500 mr-2">✓</span>
          <span>Update your business info anytime</span>
        </li>
        <li class="flex items-start">
          <span class="text-green-500 mr-2">✓</span>
          <span>Respond to customer reviews</span>
        </li>
        <li class="flex items-start">
          <span class="text-green-500 mr-2">✓</span>
          <span>Get found by more customers</span>
        </li>
      </ul>
      <p class="mt-4 text-sm text-gray-600">Premium features available for businesses ready to grow!</p>
    `
  },
  tier: {
    title: 'Choose What Works for You',
    content: `
      <div class="space-y-4">
        <p class="font-medium">No hidden fees. Cancel anytime.</p>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-green-800 font-medium">🎉 Special Offer</p>
          <p class="text-green-700 text-sm mt-1">Start with our free plan and upgrade anytime as your business grows!</p>
        </div>
        <blockquote class="border-l-4 border-indigo-500 pl-4 italic text-gray-600">
          "Upgrading to Professional helped us get 40% more customer inquiries!" 
          <cite class="block text-sm mt-2 not-italic">- Sarah, Local Bakery Owner</cite>
        </blockquote>
      </div>
    `
  },
  payment: {
    title: 'Secure Payment',
    content: `
      <p>All payments are processed securely through Stripe. We never store your credit card information.</p>
      <p class="mt-4">Cancel anytime with no hidden fees.</p>
    `
  },
  otp: {
    title: 'Almost There!',
    content: `
      <p>Enter the verification code we sent to confirm your ownership.</p>
      <p class="mt-4">This helps protect your business from unauthorized claims.</p>
      <div class="mt-6 p-4 bg-blue-50 rounded-lg">
        <p class="text-blue-800 text-sm"><strong>Did you know?</strong> Verified businesses get 3x more customer engagement!</p>
      </div>
    `
  },
  documents: {
    title: 'Document Verification',
    content: `
      <p>Upload official documents to verify your business ownership. Accepted documents include:</p>
      <ul class="mt-4 space-y-1">
        <li>• Business license</li>
        <li>• Tax documents</li>
        <li>• Utility bills</li>
        <li>• Articles of incorporation</li>
      </ul>
      <div class="mt-6 p-4 bg-green-50 rounded-lg">
        <p class="text-green-800 text-sm"><strong>Tip:</strong> After verification, you'll be able to choose the perfect plan for your business!</p>
      </div>
    `
  },
  success: {
    title: 'Congratulations!',
    content: `
      <p>Your claim has been submitted successfully. Our team will review it within 24-48 hours.</p>
      <p class="mt-4">You'll receive an email notification once your claim is approved.</p>
    `
  }
}

const defaultMarketingContent = marketingPanels.verification.content

// Computed
const canProceedFromVerification = computed(() => {
  if (verificationMethod.value === 'email') {
    return businessEmail.value && businessEmail.value.includes('@')
  }
  if (verificationMethod.value === 'phone') {
    return businessPhone.value && businessPhone.value.length >= 10
  }
  return true // document method doesn't require input at this step
})

const getSelectedTierName = computed(() => {
  const tier = tiers.find(t => t.id === selectedTier.value)
  return tier?.name || ''
})

const getSelectedTierPrice = computed(() => {
  const tier = tiers.find(t => t.id === selectedTier.value)
  return tier?.price || 0
})

// Methods
const fetchPlace = async () => {
  try {
    loading.value = true
    // Use the new claiming-specific endpoint
    const response = await axios.get(`/api/places/${props.slug}/for-claiming`)
    
    place.value = response.data.data
    
    // Check if place can be claimed
    if (place.value && place.value.is_claimed) {
      error.value = 'This business has already been claimed.'
    }
  } catch (err) {
    error.value = 'Failed to load place details. Please make sure the business exists.'
    console.error('Error fetching place:', err)
  } finally {
    loading.value = false
  }
}

const nextStep = async () => {
  if (currentStep.value === 'verification') {
    // Start the claim process without tier selection
    await initiateClaim()
  } else if (currentStep.value === 'otp') {
    if (verificationMethod.value === 'document') {
      currentStep.value = 'documents'
    } else {
      // After OTP verification, go to options
      currentStep.value = 'options'
    }
  } else if (currentStep.value === 'documents') {
    // After documents, go to options
    currentStep.value = 'options'
  } else if (currentStep.value === 'options') {
    // Handle options selection and proceed to payment or complete
    await confirmOptionsAndProceed()
  }
}

const previousStep = () => {
  if (currentStep.value === 'otp') {
    currentStep.value = 'verification'
  } else if (currentStep.value === 'documents') {
    currentStep.value = 'verification'
  } else if (currentStep.value === 'options') {
    if (verificationMethod.value === 'document') {
      currentStep.value = 'documents'
    } else {
      currentStep.value = 'otp'
    }
  } else if (currentStep.value === 'payment') {
    currentStep.value = 'options'
  }
}

const confirmOptionsAndProceed = async () => {
  // Update the claim with the selected tier
  try {
    if (claim.value) {
      await axios.put(`/api/claims/${claim.value.id}/update-tier`, {
        tier: selectedTier.value
      })
    }
    
    // Always require verification fee payment (it's refundable)
    payVerificationFee.value = true
    
    // Move to payment step
    currentStep.value = 'payment'
    // Wait for DOM to update
    await nextTick()
    // Then initialize combined payment
    await initializeCombinedPayment()
  } catch (err) {
    error.value = 'Failed to update selection'
    console.error('Error updating options:', err)
  }
}

const initiateClaim = async () => {
  try {
    loading.value = true
    
    if (!place.value || !place.value.id) {
      error.value = 'Place information not loaded. Please refresh the page.'
      return
    }
    
    const payload = {
      tier: 'free', // Start with free, will update later
      verification_method: verificationMethod.value,
      verification_notes: verificationNotes.value
    }
    
    if (verificationMethod.value === 'email') {
      payload.business_email = businessEmail.value
    } else if (verificationMethod.value === 'phone') {
      payload.business_phone = businessPhone.value
    }
    
    const response = await axios.post(`/api/places/${place.value.id}/claim`, payload)
    claim.value = response.data.claim
    
    // Move to appropriate next step
    if (verificationMethod.value === 'document') {
      currentStep.value = 'documents'
    } else {
      currentStep.value = 'otp'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to initiate claim'
    console.error('Error initiating claim:', err)
  } finally {
    loading.value = false
  }
}

const initializeCombinedPayment = async () => {
  try {
    console.log('Creating combined payment for claim:', claim.value.id)
    
    // Calculate total amount
    const tierPrice = selectedTier.value === 'free' ? 0 : tiers.find(t => t.id === selectedTier.value)?.price || 0
    const feeAmount = payVerificationFee.value ? verificationFeeAmount : 0
    const totalAmount = tierPrice + feeAmount
    
    console.log('Payment breakdown:', { tierPrice, feeAmount, totalAmount })
    
    // Create combined payment intent
    const response = await axios.post(`/api/claims/${claim.value.id}/create-combined-payment`, {
      tier: selectedTier.value,
      include_verification_fee: payVerificationFee.value,
      keep_fee: keepFeeAsTip.value
    })
    
    console.log('Payment intent response:', response.data)
    
    const clientSecret = response.data.client_secret
    
    if (!clientSecret) {
      throw new Error('No client secret received from server')
    }
    
    // Initialize Stripe
    await initializeStripe(clientSecret)
  } catch (err) {
    paymentError.value = err.response?.data?.message || err.message || 'Failed to initialize payment'
    console.error('Payment initialization error:', err)
    console.error('Error response:', err.response?.data)
  }
}

const initializeStripe = async (clientSecret) => {
  try {
    console.log('Initializing Stripe with clientSecret:', clientSecret)
    
    // Load Stripe
    const stripeKey = import.meta.env.VITE_STRIPE_PUBLIC_KEY
    if (!stripeKey) {
      throw new Error('Stripe public key not configured')
    }
    
    console.log('Loading Stripe with key:', stripeKey)
    stripe.value = await loadStripe(stripeKey)
    
    if (!stripe.value) {
      throw new Error('Failed to load Stripe')
    }
    
    // Create elements
    const appearance = {
      theme: 'stripe',
      variables: {
        colorPrimary: '#4f46e5',
      }
    }
    
    console.log('Creating Stripe elements...')
    elements.value = stripe.value.elements({ 
      clientSecret,
      appearance 
    })
    
    // Create and mount payment element
    const paymentElement = elements.value.create('payment')
    
    // Wait for Vue to update the DOM
    await nextTick()
    
    console.log('Looking for payment element container...')
    // Check if the element exists before mounting
    const paymentElementContainer = document.querySelector('#payment-element')
    if (paymentElementContainer) {
      console.log('Found container, mounting payment element')
      paymentElement.mount('#payment-element')
    } else {
      console.log('Container not found, waiting and retrying...')
      // If element doesn't exist yet, wait a bit more and try again
      await new Promise(resolve => setTimeout(resolve, 500))
      const retryContainer = document.querySelector('#payment-element')
      if (retryContainer) {
        console.log('Found container on retry, mounting payment element')
        paymentElement.mount('#payment-element')
      } else {
        console.error('Payment element container still not found')
        throw new Error('Payment element container not found in DOM')
      }
    }
  } catch (err) {
    paymentError.value = err.message || 'Failed to initialize payment form'
    console.error('Stripe initialization error:', err)
  }
}

const handlePayment = async () => {
  if (!stripe.value || !elements.value) return
  
  try {
    processingPayment.value = true
    paymentError.value = null
    
    const { error } = await stripe.value.confirmPayment({
      elements: elements.value,
      confirmParams: {
        return_url: `${window.location.origin}/places/${place.value.slug}/claim-success`,
      },
      redirect: 'if_required'
    })
    
    if (error) {
      // Show error to customer
      if (error.type === 'card_error' || error.type === 'validation_error') {
        paymentError.value = error.message
      } else {
        paymentError.value = 'An unexpected error occurred. Please try again.'
      }
    } else {
      // Payment succeeded, confirm with backend
      await axios.post(`/api/claims/${claim.value.id}/confirm-payment`)
      
      // If verification fee was included, mark it as paid
      if (payVerificationFee.value) {
        claim.value.verification_fee_paid = true
      }
      
      // Proceed to success
      currentStep.value = 'success'
    }
  } catch (err) {
    paymentError.value = err.message || 'Payment failed. Please try again.'
  } finally {
    processingPayment.value = false
  }
}

const completeFreeClaim = async () => {
  try {
    // Mark the claim as complete for free tier
    await axios.post(`/api/claims/${claim.value.id}/confirm-payment`)
    currentStep.value = 'success'
  } catch (err) {
    error.value = 'Failed to complete claim'
    console.error('Error completing claim:', err)
  }
}

const handleVerified = () => {
  // After verification, go to options
  currentStep.value = 'options'
}

const handleDocumentUploaded = () => {
  hasUploadedDocuments.value = true
}

const handleError = (errorMessage) => {
  error.value = errorMessage
}

// Fee handling methods
const handleFeeStep = async () => {
  if (payVerificationFee.value) {
    // Initialize fee payment
    await initializeFeePayment()
  } else {
    // Skip fee, go directly to tier selection
    currentStep.value = 'tier'
  }
}

const initializeFeePayment = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Create verification fee payment intent
    const response = await axios.post(`/api/claims/${claim.value.id}/create-verification-fee-payment`, {
      keep_fee: keepFeeAsTip.value
    })
    
    const clientSecret = response.data.client_secret
    
    if (!clientSecret) {
      throw new Error('No client secret received for verification fee')
    }
    
    // Initialize Stripe for fee payment
    await initializeStripeForFee(clientSecret)
    
    // Show fee payment UI
    currentStep.value = 'fee-payment'
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to initialize verification fee payment'
    console.error('Fee payment initialization error:', err)
  } finally {
    loading.value = false
  }
}

const initializeStripeForFee = async (clientSecret) => {
  try {
    const stripeKey = import.meta.env.VITE_STRIPE_PUBLIC_KEY
    if (!stripeKey) {
      throw new Error('Stripe public key not configured')
    }
    
    stripe.value = await loadStripe(stripeKey)
    
    if (!stripe.value) {
      throw new Error('Failed to load Stripe')
    }
    
    // Create elements for fee payment
    const appearance = {
      theme: 'stripe',
      variables: {
        colorPrimary: '#4f46e5',
      }
    }
    
    elements.value = stripe.value.elements({ 
      clientSecret,
      appearance 
    })
    
    // Create and mount payment element for fee
    const paymentElement = elements.value.create('payment')
    
    // Show the fee payment step
    currentStep.value = 'fee-payment'
    
    // Wait for DOM update
    await nextTick()
    
    // Mount the payment element
    const feePaymentContainer = document.querySelector('#fee-payment-element')
    if (feePaymentContainer) {
      paymentElement.mount('#fee-payment-element')
    } else {
      // Retry after a short delay
      await new Promise(resolve => setTimeout(resolve, 300))
      const retryContainer = document.querySelector('#fee-payment-element')
      if (retryContainer) {
        paymentElement.mount('#fee-payment-element')
      } else {
        throw new Error('Fee payment element container not found')
      }
    }
  } catch (err) {
    error.value = err.message || 'Failed to initialize fee payment form'
    console.error('Stripe fee initialization error:', err)
  }
}

const handleFeePayment = async () => {
  if (!stripe.value || !elements.value) return
  
  try {
    processingPayment.value = true
    paymentError.value = null
    
    const { error: stripeError } = await stripe.value.confirmPayment({
      elements: elements.value,
      confirmParams: {
        return_url: `${window.location.origin}/places/${place.value.slug}/claim?step=fee-confirm`,
      },
      redirect: 'if_required'
    })
    
    if (stripeError) {
      // Show error to customer
      if (stripeError.type === 'card_error' || stripeError.type === 'validation_error') {
        paymentError.value = stripeError.message
      } else {
        paymentError.value = 'An unexpected error occurred. Please try again.'
      }
    } else {
      // Payment succeeded, confirm with backend
      await axios.post(`/api/claims/${claim.value.id}/confirm-verification-fee`)
      
      // Update claim to mark fee as paid
      claim.value.verification_fee_paid = true
      
      // Proceed to tier selection
      currentStep.value = 'tier'
    }
  } catch (err) {
    paymentError.value = err.message || 'Fee payment failed. Please try again.'
  } finally {
    processingPayment.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchPlace()
  
  // Check if returning from fee payment
  if (route.query.step === 'fee-confirm' && route.query.payment_intent) {
    // Handle fee payment confirmation
    confirmFeePaymentReturn()
  }
})

// Handle return from Stripe fee payment
const confirmFeePaymentReturn = async () => {
  try {
    loading.value = true
    
    // Confirm the fee payment with backend
    await axios.post(`/api/claims/${claim.value.id}/confirm-verification-fee`)
    
    // Update claim and proceed
    claim.value.verification_fee_paid = true
    currentStep.value = 'tier'
  } catch (err) {
    error.value = 'Failed to confirm fee payment'
    console.error('Fee confirmation error:', err)
  } finally {
    loading.value = false
  }
}

const calculateTotalPayment = () => {
  const tierPrice = selectedTier.value === 'free' ? 0 : tiers.find(t => t.id === selectedTier.value)?.price || 0
  const feeAmount = payVerificationFee.value ? verificationFeeAmount : 0
  return (tierPrice + feeAmount).toFixed(2)
}

// Watch for authentication changes
watch(() => authStore.user, (newUser) => {
  if (!newUser) {
    router.push({
      name: 'Login',
      query: { redirect: route.fullPath }
    })
  }
})
</script>

<style>
/* Custom styles for Stripe Elements */
#payment-element,
#fee-payment-element {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  background-color: #f9fafb;
  min-height: 300px;
}

/* Loading animation for payment element */
#payment-element:empty::after,
#fee-payment-element:empty::after {
  content: 'Loading payment form...';
  display: block;
  text-align: center;
  color: #6b7280;
  padding: 2rem;
}
</style>