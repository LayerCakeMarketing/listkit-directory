<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Choose Your Plan</h3>
        <p class="text-gray-600 mb-6">
            Your claim has been verified! Select a subscription plan to manage your business listing.
        </p>

        <!-- Plans -->
        <div class="space-y-4">
            <!-- Free Plan -->
            <label
                class="relative flex flex-col p-6 border-2 rounded-lg cursor-pointer hover:shadow-md transition-shadow"
                :class="selectedPlan === 'free' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
            >
                <input
                    type="radio"
                    v-model="selectedPlan"
                    value="free"
                    class="sr-only"
                />
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Free</h4>
                        <p class="text-2xl font-bold text-gray-900">$0<span class="text-sm font-normal text-gray-600">/month</span></p>
                    </div>
                    <div v-if="selectedPlan === 'free'" class="text-blue-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Basic business information edits
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Respond to reviews
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Update business hours
                    </li>
                </ul>
            </label>

            <!-- Professional Plan -->
            <label
                class="relative flex flex-col p-6 border-2 rounded-lg cursor-pointer hover:shadow-md transition-shadow"
                :class="selectedPlan === 'tier1' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
            >
                <input
                    type="radio"
                    v-model="selectedPlan"
                    value="tier1"
                    class="sr-only"
                />
                <div class="absolute -top-3 left-6 px-2 bg-green-500 text-white text-xs font-semibold rounded-full">
                    RECOMMENDED
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Professional</h4>
                        <p class="text-2xl font-bold text-gray-900">$9.99<span class="text-sm font-normal text-gray-600">/month</span></p>
                    </div>
                    <div v-if="selectedPlan === 'tier1'" class="text-blue-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Everything in Free
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Business analytics dashboard
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Verified badge
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Priority customer support
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Remove competitor ads
                    </li>
                </ul>
            </label>

            <!-- Premium Plan -->
            <label
                class="relative flex flex-col p-6 border-2 rounded-lg cursor-pointer hover:shadow-md transition-shadow"
                :class="selectedPlan === 'tier2' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
            >
                <input
                    type="radio"
                    v-model="selectedPlan"
                    value="tier2"
                    class="sr-only"
                />
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Premium</h4>
                        <p class="text-2xl font-bold text-gray-900">$19.99<span class="text-sm font-normal text-gray-600">/month</span></p>
                    </div>
                    <div v-if="selectedPlan === 'tier2'" class="text-blue-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Everything in Professional
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Multi-location management
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Team member access
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Custom branding options
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        API access
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Dedicated account manager
                    </li>
                </ul>
            </label>
        </div>

        <!-- Payment Section (for paid plans) -->
        <div v-if="selectedPlan !== 'free'" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Payment Information</h4>
            <div id="card-element" class="p-3 border border-gray-300 rounded-md bg-white">
                <!-- Stripe Card Element will be mounted here -->
            </div>
            <p v-if="cardError" class="mt-2 text-sm text-red-600">{{ cardError }}</p>
        </div>

        <!-- Continue Button -->
        <div class="mt-6 flex justify-end space-x-3">
            <button
                v-if="selectedPlan === 'free'"
                @click="selectFreePlan"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                Continue with Free Plan
            </button>
            <button
                v-else
                @click="subscribe"
                :disabled="!stripe || subscribing"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ subscribing ? 'Processing...' : `Subscribe to ${selectedPlan === 'tier1' ? 'Professional' : 'Premium'}` }}
            </button>
        </div>

        <!-- Error message -->
        <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-600">{{ error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['selected'])

// State
const selectedPlan = ref('tier1') // Default to recommended plan
const stripe = ref(null)
const elements = ref(null)
const cardElement = ref(null)
const cardError = ref('')
const subscribing = ref(false)
const error = ref('')

// Load Stripe from CDN
const loadStripeFromCDN = () => {
    return new Promise((resolve) => {
        if (window.Stripe) {
            resolve(window.Stripe)
            return
        }
        
        const script = document.createElement('script')
        script.src = 'https://js.stripe.com/v3/'
        script.onload = () => resolve(window.Stripe)
        document.head.appendChild(script)
    })
}

// Load Stripe
onMounted(async () => {
    if (import.meta.env.VITE_STRIPE_KEY) {
        const Stripe = await loadStripeFromCDN()
        stripe.value = Stripe(import.meta.env.VITE_STRIPE_KEY)
        elements.value = stripe.value.elements()
        
        // Create card element
        cardElement.value = elements.value.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                }
            }
        })
        
        // Mount card element
        if (selectedPlan.value !== 'free') {
            cardElement.value.mount('#card-element')
        }
        
        // Listen for card errors
        cardElement.value.on('change', (event) => {
            cardError.value = event.error ? event.error.message : ''
        })
    }
})

// Watch for plan changes to mount/unmount card element
watch(selectedPlan, (newPlan, oldPlan) => {
    if (newPlan !== 'free' && !cardElement.value._isMounted) {
        cardElement.value.mount('#card-element')
    } else if (newPlan === 'free' && cardElement.value._isMounted) {
        cardElement.value.unmount()
    }
})

// Methods
const selectFreePlan = () => {
    emit('selected', {
        tier: 'free',
        subscription: null
    })
}

const subscribe = async () => {
    if (!stripe.value || !cardElement.value) return
    
    subscribing.value = true
    error.value = ''
    
    try {
        // Create payment method
        const { error: pmError, paymentMethod } = await stripe.value.createPaymentMethod({
            type: 'card',
            card: cardElement.value
        })
        
        if (pmError) {
            error.value = pmError.message
            return
        }
        
        // Create subscription
        const response = await axios.post(`/api/subscriptions/places/${props.place.id}/subscribe`, {
            tier: selectedPlan.value,
            payment_method: paymentMethod.id
        })
        
        const subscription = response.data.subscription
        
        // Handle 3D Secure if required
        if (response.data.payment_intent && response.data.client_secret) {
            const { error: confirmError } = await stripe.value.confirmCardPayment(
                response.data.client_secret
            )
            
            if (confirmError) {
                error.value = confirmError.message
                return
            }
        }
        
        emit('selected', {
            tier: selectedPlan.value,
            subscription
        })
        
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create subscription'
    } finally {
        subscribing.value = false
    }
}
</script>

<style scoped>
#card-element {
    min-height: 40px;
}
</style>