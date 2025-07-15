<script setup>
import { reactive, onMounted } from 'vue'
import GuestLayout from '@/components/layouts/GuestLayout.vue'
import InputError from '@/components/InputError.vue'
import InputLabel from '@/components/InputLabel.vue'
import PrimaryButton from '@/components/PrimaryButton.vue'
import TextInput from '@/components/TextInput.vue'
import axios from 'axios'

const form = reactive({
    email: '',
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
        
        // Request password reset link
        const response = await axios.post('/forgot-password', {
            email: form.email
        })
        
        form.status = response.data.status || 'We have emailed your password reset link.'
    } catch (error) {
        if (error.response?.status === 422) {
            form.errors = error.response.data.errors || {}
        } else {
            form.errors = { email: ['Unable to send password reset link. Please try again.'] }
        }
    } finally {
        form.processing = false
    }
}

onMounted(() => {
    document.title = 'Forgot Password - ' + (import.meta.env.VITE_APP_NAME || 'Laravel')
})
</script>

<template>
    <GuestLayout>
        <div class="mb-4 text-sm text-gray-600">
            Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
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
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email?.[0]" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Email Password Reset Link
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>