<template>
  <div>
    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">{{ error }}</h1>
        <p class="mt-2 text-gray-600">The page you're looking for doesn't exist.</p>
        <router-link to="/" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
          Go back home
        </router-link>
      </div>
    </div>

    <!-- User Profile -->
    <UserProfile v-else-if="entityType === 'user'" :username="slug" />

    <!-- Channel Profile -->
    <ChannelShow v-else-if="entityType === 'channel'" :channel="entityData" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import UserProfile from './Show.vue'
import ChannelShow from '@/views/channels/Show.vue'

const props = defineProps({
  slug: {
    type: String,
    required: true
  }
})

const route = useRoute()
const loading = ref(true)
const error = ref(null)
const entityType = ref(null)
const entityData = ref(null)

const fetchEntity = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get(`/api/@${props.slug}`)
    
    // Check if response has a data wrapper
    const data = response.data.data || response.data
    
    // Determine entity type based on response
    if (data.username !== undefined || data.custom_url !== undefined) {
      // It's a user
      entityType.value = 'user'
      entityData.value = data
    } else if (data.slug !== undefined && data.user_id !== undefined) {
      // It's a channel
      entityType.value = 'channel'
      entityData.value = data
    } else {
      error.value = 'Unknown entity type'
    }
  } catch (err) {
    console.error('Error fetching entity:', err)
    if (err.response?.status === 404) {
      error.value = 'Page not found'
    } else {
      error.value = 'An error occurred while loading this page'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchEntity()
})

// Watch for route changes
watch(() => props.slug, (newSlug, oldSlug) => {
  if (newSlug !== oldSlug) {
    fetchEntity()
  }
})
</script>