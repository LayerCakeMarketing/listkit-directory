<template>
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Claim Your Business</h2>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-500"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Step indicator -->
                <div class="mb-6">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center">
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center', 
                                         currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600']">
                                1
                            </div>
                            <div :class="['w-24 h-1', currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200']"></div>
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center', 
                                         currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600']">
                                2
                            </div>
                            <div :class="['w-24 h-1', currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-200']"></div>
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center', 
                                         currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600']">
                                3
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-600">
                        <span class="flex-1 text-center">Verify</span>
                        <span class="flex-1 text-center">Confirm</span>
                        <span class="flex-1 text-center">Subscribe</span>
                    </div>
                </div>

                <!-- Step 1: Choose Verification Method -->
                <div v-if="currentStep === 1">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Choose Verification Method</h3>
                    <p class="text-gray-600 mb-6">
                        To claim <strong>{{ place.title }}</strong>, please select how you'd like to verify your ownership:
                    </p>

                    <div class="space-y-3">
                        <!-- Email Verification -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="verificationMethod === 'email' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input
                                type="radio"
                                v-model="verificationMethod"
                                value="email"
                                class="mt-1"
                            />
                            <div class="ml-3">
                                <div class="font-medium text-gray-900">Email Verification</div>
                                <div class="text-sm text-gray-600">We'll send a verification code to your business email</div>
                                <input
                                    v-if="verificationMethod === 'email'"
                                    v-model="businessEmail"
                                    type="email"
                                    placeholder="business@example.com"
                                    class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </label>

                        <!-- Phone Verification -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="verificationMethod === 'phone' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input
                                type="radio"
                                v-model="verificationMethod"
                                value="phone"
                                class="mt-1"
                            />
                            <div class="ml-3">
                                <div class="font-medium text-gray-900">Phone Verification</div>
                                <div class="text-sm text-gray-600">We'll send a verification code via SMS</div>
                                <input
                                    v-if="verificationMethod === 'phone'"
                                    v-model="businessPhone"
                                    type="tel"
                                    placeholder="+1 (555) 123-4567"
                                    class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </label>

                        <!-- Document Verification -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="verificationMethod === 'document' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input
                                type="radio"
                                v-model="verificationMethod"
                                value="document"
                                class="mt-1"
                            />
                            <div class="ml-3">
                                <div class="font-medium text-gray-900">Document Verification</div>
                                <div class="text-sm text-gray-600">Upload business documents for manual review</div>
                                <div v-if="verificationMethod === 'document'" class="text-xs text-gray-500 mt-1">
                                    Accepted: Business license, tax documents, utility bills
                                </div>
                            </div>
                        </label>
                    </div>

                    <div v-if="verificationNotes" class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (Optional)</label>
                        <textarea
                            v-model="verificationNotes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Any additional information to help verify your claim..."
                        ></textarea>
                    </div>
                </div>

                <!-- Step 2: Verify -->
                <div v-else-if="currentStep === 2">
                    <!-- OTP Verification -->
                    <div v-if="verificationMethod === 'email' || verificationMethod === 'phone'">
                        <!-- Test Mode Info -->
                        <div v-if="testModeOTP" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                <strong>Test Mode:</strong> Use code <span class="font-mono font-bold">{{ testModeOTP }}</span>
                            </p>
                        </div>
                        
                        <OtpVerification
                            :claim="currentClaim"
                            :verificationType="verificationMethod"
                            :destination="verificationMethod === 'email' ? businessEmail : businessPhone"
                            @verified="handleVerified"
                            @resend="resendCode"
                        />
                    </div>

                    <!-- Document Upload -->
                    <div v-else-if="verificationMethod === 'document'">
                        <DocumentUpload
                            :claim="currentClaim"
                            @uploaded="handleDocumentUploaded"
                        />
                    </div>
                </div>

                <!-- Step 3: Choose Subscription -->
                <div v-else-if="currentStep === 3">
                    <div v-if="!skipSubscription">
                        <SubscriptionSelection
                            :place="place"
                            @selected="handleSubscriptionSelected"
                        />
                        <div class="mt-4 text-center">
                            <button
                                @click="skipToCompletion"
                                class="text-sm text-gray-600 hover:text-gray-800 underline"
                            >
                                Skip for now - I'll choose a plan later
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center">
                        <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Claim Submitted Successfully!</h3>
                        <p class="text-gray-600">Your claim has been verified and approved. You can now manage this business listing.</p>
                        <p class="text-sm text-gray-500 mt-2">You can upgrade to a paid plan anytime from your business dashboard.</p>
                    </div>
                </div>

                <!-- Error message -->
                <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600">{{ error }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between">
                <button
                    v-if="currentStep > 1 && !skipSubscription"
                    @click="currentStep--"
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                    Back
                </button>
                <div v-else></div>
                
                <div class="flex space-x-3">
                    <button
                        v-if="!skipSubscription"
                        @click="$emit('close')"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        v-if="currentStep === 1"
                        @click="initiateClaim"
                        :disabled="!canProceed || loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ loading ? 'Processing...' : 'Continue' }}
                    </button>
                    <button
                        v-if="skipSubscription"
                        @click="$emit('close')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import OtpVerification from './OtpVerification.vue'
import DocumentUpload from './DocumentUpload.vue'
import SubscriptionSelection from './SubscriptionSelection.vue'

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'claimed'])

// State
const currentStep = ref(1)
const verificationMethod = ref('')
const businessEmail = ref('')
const businessPhone = ref('')
const verificationNotes = ref('')
const currentClaim = ref(null)
const loading = ref(false)
const error = ref('')
const skipSubscription = ref(false)
const testModeOTP = ref('')

// Computed
const canProceed = computed(() => {
    if (!verificationMethod.value) return false
    
    if (verificationMethod.value === 'email' && !businessEmail.value) return false
    if (verificationMethod.value === 'phone' && !businessPhone.value) return false
    
    return true
})

// Methods
const initiateClaim = async () => {
    loading.value = true
    error.value = ''
    
    try {
        const response = await axios.post(`/api/claims/places/${props.place.id}/initiate`, {
            verification_method: verificationMethod.value,
            business_email: businessEmail.value,
            business_phone: businessPhone.value,
            verification_notes: verificationNotes.value
        })
        
        currentClaim.value = response.data.claim
        currentStep.value = 2
        
        // Show verification code in development/test mode
        if (response.data.claim.verification_code) {
            console.log('Verification code:', response.data.claim.verification_code)
            // In test mode, show the code in the UI
            if (import.meta.env.DEV || import.meta.env.VITE_CLAIMING_TEST_MODE === 'true') {
                testModeOTP.value = response.data.claim.verification_code
            }
        }
    } catch (err) {
        console.error('Claim initiation error:', err.response?.data)
        
        // Handle specific SQL errors
        if (err.response?.status === 500 && err.response?.data?.message?.includes('SQLSTATE')) {
            error.value = 'Database error: Please contact support. The claims system may need to be configured.'
        } else {
            error.value = err.response?.data?.message || 'Failed to initiate claim'
        }
    } finally {
        loading.value = false
    }
}

const resendCode = async () => {
    try {
        await axios.post(`/api/claims/${currentClaim.value.id}/resend-code`)
        // Show success message
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to resend code'
    }
}

const handleVerified = () => {
    currentStep.value = 3
}

const handleDocumentUploaded = () => {
    // For document verification, we skip to subscription after upload
    currentStep.value = 3
}

const handleSubscriptionSelected = (subscription) => {
    emit('claimed', {
        claim: currentClaim.value,
        subscription
    })
}

const skipToCompletion = () => {
    skipSubscription.value = true
    // Emit claimed event without subscription
    emit('claimed', {
        claim: currentClaim.value,
        subscription: null
    })
}
</script>