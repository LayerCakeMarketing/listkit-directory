<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Authentication Debug Test</h1>
    
    <div class="space-y-4">
      <div class="bg-gray-100 p-4 rounded">
        <h2 class="font-bold mb-2">Current Auth State</h2>
        <pre>{{ JSON.stringify(authState, null, 2) }}</pre>
      </div>
      
      <div class="space-x-2">
        <button @click="checkSession" class="px-4 py-2 bg-blue-500 text-white rounded">
          Check Session
        </button>
        <button @click="fetchUser" class="px-4 py-2 bg-green-500 text-white rounded">
          Fetch User
        </button>
        <button @click="getCsrfToken" class="px-4 py-2 bg-purple-500 text-white rounded">
          Get CSRF Token
        </button>
      </div>
      
      <div v-if="sessionInfo" class="bg-gray-100 p-4 rounded">
        <h2 class="font-bold mb-2">Session Info</h2>
        <pre>{{ JSON.stringify(sessionInfo, null, 2) }}</pre>
      </div>
      
      <div v-if="error" class="bg-red-100 p-4 rounded text-red-700">
        <h2 class="font-bold mb-2">Error</h2>
        <pre>{{ error }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const authStore = useAuthStore()
const sessionInfo = ref(null)
const error = ref(null)

const authState = computed(() => ({
  isAuthenticated: authStore.isAuthenticated,
  user: authStore.user,
  loading: authStore.loading,
  localStorage: localStorage.getItem('isAuthenticated'),
  cookies: document.cookie
}))

const checkSession = async () => {
  try {
    error.value = null
    const response = await axios.get('/api/debug/session')
    sessionInfo.value = response.data
  } catch (err) {
    error.value = err.message + '\n' + JSON.stringify(err.response?.data, null, 2)
  }
}

const fetchUser = async () => {
  try {
    error.value = null
    await authStore.fetchUser()
  } catch (err) {
    error.value = err.message
  }
}

const getCsrfToken = async () => {
  try {
    error.value = null
    await axios.get('/sanctum/csrf-cookie')
    console.log('CSRF token fetched, cookies:', document.cookie)
  } catch (err) {
    error.value = err.message
  }
}

onMounted(() => {
  checkSession()
})
</script>