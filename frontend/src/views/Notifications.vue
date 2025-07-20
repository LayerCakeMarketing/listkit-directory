<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
          <div class="flex items-center space-x-4">
            <button
              v-if="hasUnread"
              @click="markAllAsRead"
              class="text-sm text-indigo-600 hover:text-indigo-800"
            >
              Mark all as read
            </button>
            <button
              v-if="hasRead"
              @click="clearRead"
              class="text-sm text-red-600 hover:text-red-800"
            >
              Clear read
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Notifications List -->
      <div v-else-if="notifications.length > 0" class="space-y-4">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'bg-white rounded-lg shadow-sm border transition-all duration-200',
            notification.is_read ? 'border-gray-200' : 'border-indigo-200 bg-indigo-50'
          ]"
        >
          <div class="p-4">
            <div class="flex items-start space-x-3">
              <!-- Avatar -->
              <div class="flex-shrink-0">
                <router-link
                  v-if="notification.data?.follower_username"
                  :to="`/up/@${notification.data.follower_custom_url || notification.data.follower_username}`"
                  class="block"
                >
                  <img
                    :src="notification.data.follower_avatar || getDefaultAvatar(notification.data.follower_name)"
                    :alt="notification.data.follower_name"
                    class="h-10 w-10 rounded-full"
                  />
                </router-link>
                <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">
                      {{ notification.title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                      <template v-if="notification.type === 'user_follow'">
                        <router-link
                          :to="`/up/@${notification.data.follower_custom_url || notification.data.follower_username}`"
                          class="font-medium text-indigo-600 hover:text-indigo-800"
                        >
                          @{{ notification.data.follower_username }}
                        </router-link>
                        followed you
                      </template>
                      <template v-else-if="notification.type === 'channel_follow'">
                        <router-link
                          :to="`/up/@${notification.data.follower_custom_url || notification.data.follower_username}`"
                          class="font-medium text-indigo-600 hover:text-indigo-800"
                        >
                          @{{ notification.data.follower_username }}
                        </router-link>
                        followed your channel
                        <router-link
                          :to="`/@${notification.data.channel_slug}`"
                          class="font-medium text-indigo-600 hover:text-indigo-800"
                        >
                          "{{ notification.data.channel_name }}"
                        </router-link>
                      </template>
                      <template v-else>
                        {{ notification.message }}
                      </template>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                      {{ formatDate(notification.created_at) }}
                    </p>
                  </div>

                  <!-- Actions -->
                  <div class="ml-4 flex-shrink-0 flex items-center space-x-2">
                    <button
                      v-if="!notification.is_read"
                      @click="markAsRead(notification)"
                      class="text-indigo-600 hover:text-indigo-800"
                      title="Mark as read"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                    </button>
                    <button
                      @click="deleteNotification(notification)"
                      class="text-gray-400 hover:text-red-600"
                      title="Delete"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
        <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="mt-8 text-center">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          {{ loadingMore ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

const { showSuccess, showError } = useNotification()

const notifications = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

const hasUnread = computed(() => notifications.value.some(n => !n.is_read))
const hasRead = computed(() => notifications.value.some(n => n.is_read))
const hasMore = computed(() => currentPage.value < lastPage.value)

const fetchNotifications = async (page = 1) => {
  try {
    const response = await axios.get('/api/notifications', {
      params: { page, per_page: 20 }
    })
    
    if (page === 1) {
      notifications.value = response.data.data
    } else {
      notifications.value.push(...response.data.data)
    }
    
    currentPage.value = response.data.current_page
    lastPage.value = response.data.last_page
  } catch (error) {
    console.error('Error fetching notifications:', error)
    showError('Failed to load notifications')
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMore = () => {
  loadingMore.value = true
  fetchNotifications(currentPage.value + 1)
}

const markAsRead = async (notification) => {
  try {
    await axios.post(`/api/notifications/${notification.id}/read`)
    notification.is_read = true
    notification.read_at = new Date()
  } catch (error) {
    console.error('Error marking notification as read:', error)
    showError('Failed to mark notification as read')
  }
}

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/mark-all-read')
    notifications.value.forEach(n => {
      n.is_read = true
      n.read_at = new Date()
    })
    showSuccess('All notifications marked as read')
  } catch (error) {
    console.error('Error marking all as read:', error)
    showError('Failed to mark all as read')
  }
}

const deleteNotification = async (notification) => {
  try {
    await axios.delete(`/api/notifications/${notification.id}`)
    const index = notifications.value.findIndex(n => n.id === notification.id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Error deleting notification:', error)
    showError('Failed to delete notification')
  }
}

const clearRead = async () => {
  if (!confirm('Are you sure you want to clear all read notifications?')) {
    return
  }
  
  try {
    await axios.delete('/api/notifications/clear-read')
    notifications.value = notifications.value.filter(n => !n.is_read)
    showSuccess('Read notifications cleared')
  } catch (error) {
    console.error('Error clearing read notifications:', error)
    showError('Failed to clear read notifications')
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffMinutes < 1) {
    return 'just now'
  } else if (diffMinutes < 60) {
    return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`
  } else if (diffHours < 24) {
    return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`
  } else if (diffDays < 7) {
    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`
  } else {
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

const getDefaultAvatar = (name) => {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=128`
}

onMounted(() => {
  fetchNotifications()
})
</script>