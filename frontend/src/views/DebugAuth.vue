<template>
  <div class="p-8 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Authentication Debug</h1>
    
    <div class="space-y-4">
      <!-- Test Buttons -->
      <div class="flex space-x-2">
        <button @click="testCsrfToken" class="px-4 py-2 bg-blue-500 text-white rounded">
          Test CSRF Token
        </button>
        <button @click="testCsrfPost" class="px-4 py-2 bg-green-500 text-white rounded">
          Test CSRF POST
        </button>
        <button @click="testLogin" class="px-4 py-2 bg-purple-500 text-white rounded">
          Test Login
        </button>
      </div>
      
      <!-- Results -->
      <div v-if="results.length > 0" class="space-y-4">
        <div v-for="(result, index) in results" :key="index" class="bg-gray-100 p-4 rounded">
          <h3 class="font-bold">{{ result.title }}</h3>
          <pre class="mt-2 text-sm overflow-auto">{{ JSON.stringify(result.data, null, 2) }}</pre>
        </div>
      </div>
      
      <!-- Errors -->
      <div v-if="error" class="bg-red-100 p-4 rounded text-red-700">
        <h3 class="font-bold">Error</h3>
        <pre class="mt-2 text-sm">{{ error }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const results = ref([])
const error = ref(null)

const addResult = (title, data) => {
  results.value.unshift({ title, data, timestamp: new Date().toISOString() })
}

const testCsrfToken = async () => {
  try {
    error.value = null
    
    // Log current cookies
    addResult('Current Cookies', {
      cookies: document.cookie,
      parsed: document.cookie.split(';').reduce((acc, cookie) => {
        const [key, value] = cookie.trim().split('=')
        acc[key] = value
        return acc
      }, {})
    })
    
    // Fetch CSRF token
    const response = await axios.get('/sanctum/csrf-cookie')
    
    addResult('CSRF Token Response', {
      status: response.status,
      headers: response.headers,
      cookies_after: document.cookie
    })
    
    // Check session
    const sessionResponse = await axios.get('/api/debug/session')
    addResult('Session Check', sessionResponse.data)
    
  } catch (err) {
    error.value = err.message + '\n' + JSON.stringify(err.response?.data, null, 2)
  }
}

const testCsrfPost = async () => {
  try {
    error.value = null
    
    // First get CSRF token
    await axios.get('/sanctum/csrf-cookie')
    
    // Then test POST
    const response = await axios.post('/api/debug/csrf-test', {
      test: 'data',
      timestamp: new Date().toISOString()
    })
    
    addResult('CSRF POST Test', response.data)
    
  } catch (err) {
    error.value = err.message + '\n' + JSON.stringify(err.response?.data, null, 2)
  }
}

const testLogin = async () => {
  try {
    error.value = null
    
    // Test with dummy credentials
    const response = await axios.post('/api/login', {
      email: 'test@example.com',
      password: 'password'
    })
    
    addResult('Login Test Response', response.data)
    
  } catch (err) {
    addResult('Login Test Error', {
      status: err.response?.status,
      data: err.response?.data,
      headers: err.response?.headers
    })
  }
}
</script>