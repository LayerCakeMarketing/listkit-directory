<template>
    <div>
        <!-- Claim Button -->
        <router-link
            v-if="!place.is_claimed && canClaim"
            :to="`/places/${place.slug}/claim`"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center flex items-center justify-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Claim This Business
        </router-link>
        
        <!-- Login Required -->
        <button
            v-else-if="!place.is_claimed && !authStore.isAuthenticated"
            @click="redirectToLogin"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center flex items-center justify-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Claim This Business
        </button>

        <!-- Already Claimed Badge -->
        <div v-else-if="place.is_claimed" class="bg-green-50 border border-green-200 rounded-md p-3 text-center">
            <div class="flex items-center justify-center text-green-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="font-medium">Verified Business</span>
            </div>
            <p v-if="place.subscription_tier_label && place.subscription_tier !== 'free'" class="text-sm text-green-700 mt-1">
                {{ place.subscription_tier_label }} Plan
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
})

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const canClaim = computed(() => {
    // Must be logged in to claim
    return authStore.isAuthenticated
})

const redirectToLogin = () => {
    router.push({
        name: 'Login',
        query: { redirect: `/places/${props.place.slug}/claim` }
    })
}
</script>