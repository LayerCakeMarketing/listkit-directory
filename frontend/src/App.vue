<template>
  <div id="app">
    <!-- Admin Layout -->
    <AdminLayout v-if="isAdminRoute">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </AdminLayout>
    
    <!-- Regular Layout -->
    <div v-else>
      <!-- Unified Header (shows loading state while auth is being determined) -->
      <UnifiedHeader v-if="showNavigation" />

      <main :class="{ 'pt-16': showNavigation }">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
    
    <!-- Global Notification Container -->
    <NotificationContainer />
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import UnifiedHeader from '@/components/headers/UnifiedHeader.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import NotificationContainer from '@/components/ui/NotificationContainer.vue'
import ToastContainer from '@/components/ToastContainer.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Get site name from settings or fallback to env/default
const appName = window.siteSettings?.site_name || import.meta.env.VITE_APP_NAME || 'Listerino'

const showNavigation = computed(() => {
  // Hide navigation on auth pages
  return !['Login', 'Register'].includes(route.name)
})

const isAdminRoute = computed(() => {
  const isAdmin = route.path.startsWith('/admin')
  console.log('Route path:', route.path, 'Is admin:', isAdmin)
  return isAdmin
})

// Update page title based on route
watch(() => route.name, (newName) => {
  let title = appName
  
  if (newName && newName !== 'Home') {
    const pageName = newName.replace(/([A-Z])/g, ' $1').trim()
    title = `${pageName} - ${appName}`
  }
  
  document.title = title
}, { immediate: true })

// Watch route params for dynamic titles
watch(() => route.params, () => {
  // Update title for dynamic routes
  if (route.meta?.title) {
    document.title = `${route.meta.title} - ${appName}`
  }
}, { deep: true })

onMounted(() => {
  // Fetch user data if we think we're authenticated but don't have user data yet
  if (authStore.isAuthenticated && !authStore.user && !authStore.loading) {
    authStore.fetchUser()
  }
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Page transitions */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.3s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>