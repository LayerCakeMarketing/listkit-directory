<script setup>
import { reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import InputError from '@/components/InputError.vue'
import InputLabel from '@/components/InputLabel.vue'
import PrimaryButton from '@/components/PrimaryButton.vue'
import TextInput from '@/components/TextInput.vue'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
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

onMounted(() => {
    document.title = 'Register - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})
</script>

<template>
    <div class="flex min-h-screen flex-1">
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                      <Logo to="/" imgClassName="h-10 w-auto" />
                    <h2 class="mt-8 text-2xl/9 font-bold tracking-tight text-gray-900">Create your account</h2>
                    <p class="mt-2 text-sm/6 text-gray-500">
                        Already have an account?
                        {{ ' ' }}
                        <router-link to="/login" class="font-semibold text-indigo-600 hover:text-indigo-500">
                            Sign in here
                        </router-link>
                    </p>
                </div>

                <div class="mt-10">
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