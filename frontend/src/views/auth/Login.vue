<script setup>
import { ref, reactive, onMounted, computed, watchEffect, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import InputError from '@/components/InputError.vue'
import InputLabel from '@/components/InputLabel.vue'
import PrimaryButton from '@/components/PrimaryButton.vue'
import TextInput from '@/components/TextInput.vue'
import Checkbox from '@/components/Checkbox.vue'
import Logo from '@/components/Logo.vue'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Check for verification success message
const verificationSuccess = ref(route.query.verified === 'true')
const passwordResetSuccess = ref(route.query.reset === 'success')

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true
    },
    status: {
        type: String,
    },
})

const form = reactive({
    email: '',
    password: '',
    remember: false,
    processing: false,
    errors: {}
})

const loginSettings = ref({
    welcome_message: null,
    background_image_id: null,
    background_image_url: null,
    custom_css: null,
    social_login_enabled: false
})

const settingsLoaded = ref(false)

const backgroundImageUrl = computed(() => {
    // Don't show any image until settings are loaded to prevent flash
    if (!settingsLoaded.value) {
        return null
    }
    
    if (loginSettings.value.background_image_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${loginSettings.value.background_image_id}/lgformat`
    }
    if (loginSettings.value.background_image_url) {
        return loginSettings.value.background_image_url
    }
    // Default Unsplash image
    return 'https://images.unsplash.com/photo-1496917756835-20cb06e75b4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1908&q=80'
})

const submit = async () => {
    form.processing = true
    form.errors = {}
    
    try {
        // Use the auth store login method which handles everything
        const response = await authStore.login({
            email: form.email,
            password: form.password,
            remember: form.remember,
            redirect: router.currentRoute.value.query.redirect
        })
        
        // Redirect to intended page or home
        const redirectTo = response.data.redirect || router.currentRoute.value.query.redirect || '/home'
        router.push(redirectTo)
    } catch (error) {
        console.error('Login error:', error)
        console.error('Error response:', error.response)
        
        if (error.response?.status === 422) {
            form.errors = error.response.data.errors || {}
        } else if (error.response?.status === 419) {
            // Session expired, try to get a new CSRF token
            console.log('CSRF token expired, refreshing...')
            try {
                await axios.get('/sanctum/csrf-cookie')
                // Retry the login
                const response = await authStore.login({
                    email: form.email,
                    password: form.password,
                    remember: form.remember,
                    redirect: router.currentRoute.value.query.redirect
                })
                const redirectTo = response.data.redirect || router.currentRoute.value.query.redirect || '/home'
                router.push(redirectTo)
            } catch (retryError) {
                console.error('Retry error:', retryError)
                form.errors = { email: ['Session expired. Please refresh the page and try again.'] }
            }
        } else {
            form.errors = { email: [error.response?.data?.message || 'An error occurred. Please try again.'] }
        }
        form.password = ''
    } finally {
        form.processing = false
    }
}

onMounted(async () => {
    // Ensure we have a CSRF token before attempting login
    try {
        console.log('Fetching CSRF token on login page mount...')
        await axios.get('/sanctum/csrf-cookie')
        console.log('CSRF token fetched successfully')
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error)
    }
    
    // Fetch login page settings
    try {
        const response = await axios.get('/api/login-settings')
        loginSettings.value = response.data.data
        settingsLoaded.value = true
    } catch (error) {
        console.error('Failed to fetch login settings:', error)
        settingsLoaded.value = true // Still mark as loaded to show default
    }
    
    document.title = 'Log in - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})

// Handle custom CSS injection
const styleElement = ref(null)

watchEffect(() => {
    if (loginSettings.value.custom_css) {
        // Remove existing style element if it exists
        if (styleElement.value) {
            document.head.removeChild(styleElement.value)
        }
        
        // Create new style element
        styleElement.value = document.createElement('style')
        styleElement.value.setAttribute('data-login-custom-css', 'true')
        styleElement.value.textContent = loginSettings.value.custom_css
        document.head.appendChild(styleElement.value)
    }
})

// Clean up on unmount
onUnmounted(() => {
    if (styleElement.value) {
        document.head.removeChild(styleElement.value)
    }
})
</script>

<template>
    <div class="flex min-h-screen flex-1">
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                  
                    <Logo to="/" imgClassName="h-10 w-auto" />
            
                    <h2 v-if="loginSettings.welcome_message" class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900" v-html="loginSettings.welcome_message"></h2>
                    <h2 v-else class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900">Sign in to your account</h2>
                    <p class="mt-2 text-sm/6 text-gray-500">
                        Don't have an account?
                        {{ ' ' }}
                        <router-link to="/register" class="font-semibold text-indigo-600 hover:text-indigo-500">
                            Register here
                        </router-link>
                    </p>
                </div>

                <div v-if="status" class="mt-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <!-- Success messages -->
                <div v-if="verificationSuccess" class="mt-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Your email has been successfully verified! You can now sign in.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="passwordResetSuccess" class="mt-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Your password has been reset successfully! Please sign in with your new password.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <form @submit.prevent="submit" class="space-y-6">
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
                                    autofocus
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
                                    autocomplete="current-password" 
                                    required
                                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                <InputError class="mt-2" :message="form.errors.password?.[0]" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input 
                                            id="remember-me" 
                                            name="remember-me" 
                                            type="checkbox"
                                            v-model="form.remember" 
                                            class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-indigo-600 checked:bg-indigo-600 indeterminate:border-indigo-600 indeterminate:bg-indigo-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" 
                                        />
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <label for="remember-me" class="block text-sm/6 text-gray-900">Remember me</label>
                            </div>

                            <div class="text-sm/6">
                                <router-link to="/forgot-password" v-if="canResetPassword" class="font-semibold text-indigo-600 hover:text-indigo-500">
                                    Forgot password?
                                </router-link>
                            </div>
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Signing in...' : 'Sign in' }}
                            </button>
                        </div>
                        
                        <!-- Social Login Options -->
                        <div v-if="loginSettings.social_login_enabled" class="mt-6">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300" />
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="bg-white px-2 text-gray-500">Or continue with</span>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <button type="button" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" disabled>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M20 10c0-5.523-4.477-10-10-10S0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.988C16.343 19.128 20 14.991 20 10z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2">Facebook</span>
                                </button>

                                <button type="button" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" disabled>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" />
                                    </svg>
                                    <span class="ml-2">GitHub</span>
                                </button>
                            </div>
                            
                            <p class="mt-4 text-center text-xs text-gray-500">
                                Social login coming soon
                            </p>
                        </div>

                        <div class="text-center mt-6">
                            <router-link to="/" class="text-sm/6 font-semibold text-indigo-600 hover:text-indigo-500">
                                ← Return to browse
                            </router-link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="relative hidden w-0 flex-1 lg:block">
            <!-- Show a loading background while settings load -->
            <div v-if="!settingsLoaded" class="absolute inset-0 bg-gray-100 animate-pulse"></div>
            <!-- Show image once loaded with fade-in transition -->
            <img 
                v-if="backgroundImageUrl" 
                class="absolute inset-0 size-full object-cover transition-opacity duration-500"
                :class="{ 'opacity-0': !settingsLoaded, 'opacity-100': settingsLoaded }"
                :src="backgroundImageUrl" 
                alt="" 
            />
        </div>
    </div>
</template>