<template>
  <div>
    <!-- Show Category view if it's a category -->
    <Category v-if="type === 'category'" :category-slug="slug" />
    
    <!-- Show State view if it's a state -->
    <BrowseState v-else-if="type === 'state'" :state="slug" />
    
    <!-- Loading -->
    <div v-else-if="loading" class="flex justify-center items-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Error -->
    <div v-else class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Not Found</h1>
        <p class="text-gray-600 mb-6">The page you're looking for doesn't exist.</p>
        <router-link to="/places" class="text-blue-600 hover:text-blue-700">
          Browse all places
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import Category from './Category.vue'
import BrowseState from './BrowseState.vue'

const route = useRoute()
const props = defineProps({
  slug: {
    type: String,
    required: true
  }
})

const loading = ref(true)
const type = ref(null)

const determineType = async () => {
  loading.value = true
  type.value = null
  
  try {
    // First try as category
    try {
      const response = await axios.get(`/api/places/category/${props.slug}`)
      if (response.data.category) {
        type.value = 'category'
        loading.value = false
        return
      }
    } catch (error) {
      // Not a category, continue
    }
    
    // Then try as state
    try {
      const response = await axios.get(`/api/places/${props.slug}`)
      if (response.data.region) {
        type.value = 'state'
        loading.value = false
        return
      }
    } catch (error) {
      // Not a state either
    }
    
    // Neither category nor state
    loading.value = false
  } catch (error) {
    console.error('Error determining type:', error)
    loading.value = false
  }
}

watch(() => props.slug, () => {
  determineType()
})

onMounted(() => {
  determineType()
})
</script>