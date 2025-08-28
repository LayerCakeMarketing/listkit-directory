<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import PrimaryButton from '@/components/PrimaryButton.vue'
import { CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const sending = ref(false)
const message = ref('')
const error = ref('')

const isVerified = computed(() => authStore.user?.email_verified_at !== null)

const resendVerificationEmail = async () => {
    sending.value = true
    message.value = ''
    error.value = ''
    
    try {
        // Ensure we have CSRF token
        await axios.get('/sanctum/csrf-cookie')
        
        const response = await axios.post('/api/email/verification-notification')
        message.value = response.data.message || 'Verification email sent! Please check your inbox.'
    } catch (err) {
        console.error('Email verification error:', err)
        error.value = err.response?.data?.message || 'Failed to send verification email.'
    } finally {
        sending.value = false
    }
}
</script>

<template>
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-gray-900">
                        Email Verification
                    </h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500">
                        <div v-if="isVerified" class="flex items-center space-x-2 text-green-600">
                            <CheckCircleIcon class="h-5 w-5" />
                            <span>Your email is verified</span>
                        </div>
                        <div v-else class="flex items-center space-x-2 text-yellow-600">
                            <ExclamationCircleIcon class="h-5 w-5" />
                            <span>Your email is not verified</span>
                        </div>
                    </div>
                </div>
                <div v-if="!isVerified" class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:flex-shrink-0 sm:items-center">
                    <PrimaryButton 
                        @click="resendVerificationEmail" 
                        :disabled="sending"
                        class="w-full sm:w-auto"
                    >
                        {{ sending ? 'Sending...' : 'Resend Verification Email' }}
                    </PrimaryButton>
                </div>
            </div>
            
            <!-- Success Message -->
            <div v-if="message" class="mt-4">
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-5 w-5 text-green-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Error Message -->
            <div v-if="error" class="mt-4">
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>