<script setup>
import { reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import InputError from '@/components/InputError.vue'
import InputLabel from '@/components/InputLabel.vue'
import PrimaryButton from '@/components/PrimaryButton.vue'
import TextInput from '@/components/TextInput.vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
    processing: false,
    errors: {},
    status: null
})

const submit = async () => {
    form.processing = true
    form.errors = {}
    form.status = null
    
    try {
        // Get CSRF token
        await axios.get('/sanctum/csrf-cookie')
        
        // Reset password
        const response = await axios.post('/api/reset-password', {
            token: form.token,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation
        })
        
        form.status = 'Your password has been reset successfully! Redirecting to login...'
        
        // Redirect to login after 2 seconds
        setTimeout(() => {
            router.push({ name: 'Login', query: { reset: 'success' } })
        }, 2000)
    } catch (error) {
        if (error.response?.status === 422) {
            form.errors = error.response.data.errors || {}
        } else {
            form.errors = { email: ['Unable to reset password. The link may have expired.'] }
        }
    } finally {
        form.processing = false
    }
}

onMounted(() => {
    // Get token from URL params
    form.token = route.params.token || ''
    
    // Get email from query params
    form.email = route.query.email || ''
    
    document.title = 'Reset Password - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})
</script>

<template>
    <GuestLayout>
        <div class="mb-4 text-sm text-gray-600">
            Enter your new password below to reset your account password.
        </div>

        <div v-if="form.status" class="mb-4 text-sm font-medium text-green-600">
            {{ form.status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email?.[0]" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="New Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password?.[0]" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation?.[0]" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>