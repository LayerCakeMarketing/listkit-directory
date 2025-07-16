<script setup>
import { reactive, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import InputError from '@/components/InputError.vue'
import InputLabel from '@/components/InputLabel.vue'
import PrimaryButton from '@/components/PrimaryButton.vue'
import TextInput from '@/components/TextInput.vue'
import Logo from '@/components/Logo.vue'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const registrationEnabled = ref(true)
const checkingStatus = ref(true)
const waitlistSuccess = ref(false)

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    processing: false,
    errors: {}
})

const waitlistForm = reactive({
    name: '',
    email: '',
    message: '',
    processing: false,
    errors: {}
})

const submit = async () => {
    form.processing = true
    form.errors = {}
    
    try {
        // Use the auth store register method
        const response = await authStore.register({
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation
        })
        
        // Redirect to home
        router.push(response.data.redirect || '/home')
    } catch (error) {
        if (error.response?.status === 422) {
            form.errors = error.response.data.errors || {}
        } else {
            form.errors = { email: ['Registration failed. Please try again.'] }
        }
    } finally {
        form.processing = false
    }
}

const checkRegistrationStatus = async () => {
    try {
        const response = await axios.get('/api/registration/status')
        registrationEnabled.value = response.data.registration_enabled
    } catch (error) {
        console.error('Failed to check registration status:', error)
        // Default to enabled if check fails
        registrationEnabled.value = true
    } finally {
        checkingStatus.value = false
    }
}

const submitWaitlist = async () => {
    waitlistForm.processing = true
    waitlistForm.errors = {}
    
    try {
        await axios.post('/api/registration/waitlist', {
            name: waitlistForm.name,
            email: waitlistForm.email,
            message: waitlistForm.message
        })
        
        waitlistSuccess.value = true
        // Clear form
        waitlistForm.name = ''
        waitlistForm.email = ''
        waitlistForm.message = ''
    } catch (error) {
        if (error.response?.status === 422) {
            waitlistForm.errors = error.response.data.errors || {}
        } else {
            waitlistForm.errors = { email: ['Failed to join waitlist. Please try again.'] }
        }
    } finally {
        waitlistForm.processing = false
    }
}

onMounted(async () => {
    document.title = 'Register - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
    await checkRegistrationStatus()
})
</script>

<template>
    <div class="flex min-h-screen flex-1">
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <Logo to="/" imgClassName="h-10 w-auto" />
                    <h2 v-if="registrationEnabled" class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900">Create your account</h2>
                    <h2 v-else class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900">Registration is Currently Closed</h2>
                    <p v-if="registrationEnabled" class="mt-2 text-sm/6 text-gray-500">
                        Already have an account?
                        {{ ' ' }}
                        <router-link to="/login" class="font-semibold text-indigo-600 hover:text-indigo-500">
                            Sign in here
                        </router-link>
                    </p>
                    <p v-else class="mt-2 text-sm/6 text-gray-500">
                        Join our waitlist and we'll notify you when registration opens.
                    </p>
                </div>

                <div v-if="checkingStatus" class="mt-10">
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>
                </div>

                <div v-else-if="!registrationEnabled && waitlistSuccess" class="mt-10">
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Success!</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>You've been added to our waitlist. We'll notify you when registration opens.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 text-center">
                        <router-link to="/" class="text-sm/6 font-semibold text-indigo-600 hover:text-indigo-500">
                            ← Return to browse
                        </router-link>
                    </div>
                </div>

                <div v-else-if="!registrationEnabled" class="mt-10">
                    <form @submit.prevent="submitWaitlist" class="space-y-6">
                        <div>
                            <label for="waitlist-name" class="block text-sm/6 font-medium text-gray-900">Name (optional)</label>
                            <div class="mt-2">
                                <input 
                                    type="text" 
                                    name="waitlist-name" 
                                    id="waitlist-name" 
                                    v-model="waitlistForm.name"
                                    autocomplete="name" 
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="waitlistForm.errors.name?.[0]" />
                            </div>
                        </div>

                        <div>
                            <label for="waitlist-email" class="block text-sm/6 font-medium text-gray-900">Email address</label>
                            <div class="mt-2">
                                <input 
                                    type="email" 
                                    name="waitlist-email" 
                                    id="waitlist-email" 
                                    v-model="waitlistForm.email"
                                    autocomplete="email" 
                                    required
                                    autofocus
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="waitlistForm.errors.email?.[0]" />
                            </div>
                        </div>

                        <div>
                            <label for="waitlist-message" class="block text-sm/6 font-medium text-gray-900">Message (optional)</label>
                            <div class="mt-2">
                                <textarea 
                                    name="waitlist-message" 
                                    id="waitlist-message" 
                                    v-model="waitlistForm.message"
                                    rows="3"
                                    placeholder="Tell us why you're interested in joining... And tell us how you heard about Listerino."
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="waitlistForm.errors.message?.[0]" />
                            </div>
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                :disabled="waitlistForm.processing"
                                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ waitlistForm.processing ? 'Joining waitlist...' : 'Join Waitlist' }}
                            </button>
                        </div>

                        <div class="text-center">
                            <router-link to="/" class="text-sm/6 font-semibold text-indigo-600 hover:text-indigo-500">
                                ← Return to browse
                            </router-link>
                        </div>
                    </form>
                </div>

                <div v-else class="mt-10">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm/6 font-medium text-gray-900">Name</label>
                            <div class="mt-2">
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="name" 
                                    v-model="form.name"
                                    autocomplete="name" 
                                    required
                                    autofocus
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="form.errors.name?.[0]" />
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm/6 font-medium text-gray-900">Email address</label>
                            <div class="mt-2">
                                <input 
                                    type="email" 
                                    name="email" 
                                    id="email" 
                                    v-model="form.email"
                                    autocomplete="email" 
                                    required
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="form.errors.email?.[0]" />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
                            <div class="mt-2">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    v-model="form.password"
                                    autocomplete="new-password" 
                                    required
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="form.errors.password?.[0]" />
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm/6 font-medium text-gray-900">Confirm Password</label>
                            <div class="mt-2">
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    id="password_confirmation" 
                                    v-model="form.password_confirmation"
                                    autocomplete="new-password" 
                                    required
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="form.errors.password_confirmation?.[0]" />
                            </div>
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Creating account...' : 'Register' }}
                            </button>
                        </div>

                        <div class="text-center">
                            <router-link to="/" class="text-sm/6 font-semibold text-indigo-600 hover:text-indigo-500">
                                ← Return to browse
                            </router-link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="relative hidden w-0 flex-1 lg:block">
            <img class="absolute inset-0 size-full object-cover" src="https://images.unsplash.com/photo-1496917756835-20cb06e75b4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1908&q=80" alt="" />
        </div>
    </div>
</template>