<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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
const authStore = useAuthStore()

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
    document.title = 'Log in - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})
</script>

<template>
    <div class="flex min-h-screen flex-1">
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                  
                    <Logo to="/" imgClassName="h-10 w-auto" />
            
                    <h2 class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900">Sign in to your account</h2>
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