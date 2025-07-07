<template>
    <GuestLayout>
        <Head title="Registration Closed" />

        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Registration is Currently Closed
            </h2>
            
            <p class="mt-2 text-sm text-gray-600">
                We're not accepting new registrations at this time.
            </p>
            
            <p class="mt-4 text-sm text-gray-600">
                If you already have an account, you can
                <Link :href="route('login')" class="font-medium text-indigo-600 hover:text-indigo-500">
                    sign in here
                </Link>.
            </p>
            
            <div class="mt-6">
                <Link
                    :href="route('home')"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Return to Homepage
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>