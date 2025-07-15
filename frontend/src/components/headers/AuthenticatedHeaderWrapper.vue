<template>
  <AuthenticatedHeader 
    :user="user"
    :canViewAdmin="canViewAdmin"
  />
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthenticatedHeader from './AuthenticatedHeader.vue'

const authStore = useAuthStore()

const user = computed(() => authStore.user)
const canViewAdmin = computed(() => authStore.isAdmin || authStore.isManager)
</script>