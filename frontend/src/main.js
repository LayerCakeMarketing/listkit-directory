import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'
import App from './App.vue'
// import Toast from 'vue-toastification'
// import 'vue-toastification/dist/index.css'
import './assets/css/app.css'

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true
// In development, use relative URLs so requests go through Vite proxy
// In production, use the configured API URL
axios.defaults.baseURL = import.meta.env.VITE_API_URL || ''

// Handle CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
} else {
  // In SPA mode, we'll get the CSRF token from the cookie
  const getCookie = (name) => {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(';').shift()
  }
  
  // Laravel sets the XSRF-TOKEN cookie after calling /sanctum/csrf-cookie
  // We'll set up an interceptor to automatically use it
  axios.interceptors.request.use(config => {
    const xsrfToken = getCookie('XSRF-TOKEN')
    if (xsrfToken) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken)
    }
    
    // Debug logging (temporarily disabled)
    // console.log('Request:', config.method.toUpperCase(), config.url)
    // console.log('Headers:', config.headers)
    // console.log('Cookies:', document.cookie)
    
    return config
  })
}

// Add response interceptor to handle CSRF token expiration
let isRefreshing = false
let failedQueue = []

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error)
    } else {
      prom.resolve(token)
    }
  })
  
  failedQueue = []
}

axios.interceptors.response.use(
  response => {
    // console.log('Response:', response.config.method.toUpperCase(), response.config.url, 'Status:', response.status)
    return response
  },
  async error => {
    // console.log('Response Error:', error.config?.method?.toUpperCase(), error.config?.url, 'Status:', error.response?.status)
    // console.log('Error details:', error.response?.data)
    const originalRequest = error.config

    if (error.response?.status === 419 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        }).then(() => {
          return axios(originalRequest)
        }).catch(err => {
          return Promise.reject(err)
        })
      }

      originalRequest._retry = true
      isRefreshing = true

      return new Promise((resolve, reject) => {
        axios.get('/sanctum/csrf-cookie')
          .then(() => {
            processQueue(null)
            resolve(axios(originalRequest))
          })
          .catch((err) => {
            processQueue(err)
            reject(err)
          })
          .finally(() => {
            isRefreshing = false
          })
      })
    }

    // Handle 401 Unauthorized specifically
    if (error.response?.status === 401 && originalRequest.url !== '/api/user' && !originalRequest._skipAuthRetry) {
      console.log('401 Unauthorized detected, may need to re-authenticate')
    }

    return Promise.reject(error)
  }
)

// Create Vue app
const app = createApp(App)

// Use Pinia for state management
const pinia = createPinia()
app.use(pinia)

// Use Vue Router
app.use(router)

// Use Toast
// const toastOptions = {
//   position: 'top-right',
//   timeout: 3000,
//   closeOnClick: true,
//   pauseOnFocusLoss: true,
//   pauseOnHover: true,
//   draggable: true,
//   draggablePercent: 0.6,
//   showCloseButtonOnHover: false,
//   hideProgressBar: false,
//   closeButton: 'button',
//   icon: true,
//   rtl: false
// }
// app.use(Toast, toastOptions)

// Global properties
app.config.globalProperties.$axios = axios

// Mount the app
app.mount('#app')

// Register service worker for caching
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
      .then(registration => {
        console.log('Service Worker registered:', registration)
      })
      .catch(error => {
        console.error('Service Worker registration failed:', error)
      })
  })
}