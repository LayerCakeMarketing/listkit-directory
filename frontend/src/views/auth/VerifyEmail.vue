<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const verifying = ref(true)
const success = ref(false)
const error = ref('')

onMounted(async () => {
    const { id, hash } = route.params
    const { expires, signature } = route.query

    if (!id || !hash || !expires || !signature) {
        error.value = 'Invalid verification link.'
        verifying.value = false
        return
    }

    try {
        // Call the API to verify the email
        const response = await axios.get(`/api/email/verify/${id}/${hash}`, {
            params: { expires, signature }
        })

        success.value = true
        verifying.value = false

        // Update the user's verification status in the auth store if logged in
        const authStore = useAuthStore()
        if (authStore.user) {
            authStore.user.email_verified_at = new Date().toISOString()
        }

        // Redirect to login or profile after 3 seconds
        setTimeout(() => {
            if (authStore.isAuthenticated) {
                router.push('/profile/edit')
            } else {
                router.push({ name: 'Login', query: { verified: 'true' } })
            }
        }, 3000)
    } catch (err) {
        console.error('Verification error:', err)
        error.value = err.response?.data?.message || 'Failed to verify email. The link may have expired.'
        verifying.value = false
    }
})

// Import auth store
import { useAuthStore } from '@/stores/auth'
</script>

<template>
    <GuestLayout>
        <div class="text-center">
            <!-- Verifying State -->
            <div v-if="verifying" class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Verifying your email...</h2>
                <p class="text-gray-600">Please wait while we verify your email address.</p>
            </div>

            <!-- Success State -->
            <div v-else-if="success" class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100">
                    <CheckCircleIcon class="h-10 w-10 text-green-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Email Verified!</h2>
                <p class="text-gray-600">Your email has been successfully verified.</p>
                <p class="text-sm text-gray-500">Redirecting you in a few seconds...</p>
            </div>

            <!-- Error State -->
            <div v-else class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100">
                    <XCircleIcon class="h-10 w-10 text-red-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Verification Failed</h2>
                <p class="text-gray-600">{{ error }}</p>
                <div class="mt-6 space-y-2">
                    <router-link 
                        to="/login" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Go to Login
                    </router-link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>