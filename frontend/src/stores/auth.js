import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: localStorage.getItem('isAuthenticated') === 'true',
    loading: false,
    initialized: false
  }),

  getters: {
    isAdmin: (state) => state.user?.role === 'admin',
    isManager: (state) => state.user?.role === 'manager' || state.user?.role === 'admin',
    username: (state) => state.user?.custom_url || state.user?.username
  },

  actions: {
    async fetchUser() {
      console.log('fetchUser called')
      this.loading = true
      try {
        // First ensure we have CSRF token
        console.log('Getting CSRF token...')
        await axios.get('/sanctum/csrf-cookie')
        
        // Then fetch user
        console.log('Fetching user data...')
        const response = await axios.get('/api/user', {
          _skipAuthRetry: true,
          headers: {
            'Accept': 'application/json'
          }
        })
        console.log('User data received:', response.data)
        this.user = response.data
        this.isAuthenticated = !!this.user
        
        // Store auth state in localStorage for persistence
        if (this.user) {
          localStorage.setItem('isAuthenticated', 'true')
        }
      } catch (error) {
        console.log('fetchUser error:', error.response?.status, error.message)
        // Only log error if it's not a 401 (which is expected when not logged in)
        if (error.response?.status !== 401) {
          console.error('Error fetching user:', error)
        }
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('isAuthenticated')
      } finally {
        this.loading = false
        this.initialized = true
      }
    },

    async login(credentials) {
      await axios.get('/sanctum/csrf-cookie')
      const response = await axios.post('/api/login', credentials)
      this.user = response.data.user
      this.isAuthenticated = true
      localStorage.setItem('isAuthenticated', 'true')
      return response
    },

    async register(userData) {
      await axios.get('/sanctum/csrf-cookie')
      const response = await axios.post('/api/register', userData)
      this.user = response.data.user
      this.isAuthenticated = true
      localStorage.setItem('isAuthenticated', 'true')
      return response
    },

    async logout() {
      try {
        await axios.post('/api/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('isAuthenticated')
      }
    },

    setUser(user) {
      this.user = user
      this.isAuthenticated = !!user
    },
    
    clearAuth() {
      this.user = null
      this.isAuthenticated = false
      localStorage.removeItem('isAuthenticated')
    }
  }
})