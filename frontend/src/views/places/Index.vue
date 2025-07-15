<template>
    <div>
        <component :is="authStore.isAuthenticated ? AuthenticatedIndex : PublicIndex" />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthenticatedIndex from './AuthenticatedIndex.vue'
import PublicIndex from './PublicIndex.vue'

const authStore = useAuthStore()
</script>