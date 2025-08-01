import { ref, computed } from 'vue'
import axios from 'axios'

// Global state
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)

export function useNotifications() {
  // Fetch unread notifications
  const fetchUnread = async () => {
    loading.value = true
    try {
      const response = await axios.get('/api/app-notifications/unread')
      notifications.value = response.data.notifications.map(n => ({
        ...n,
        isUnread: true
      }))
      unreadCount.value = response.data.count
    } catch (error) {
      console.error('Failed to fetch notifications:', error)
    } finally {
      loading.value = false
    }
  }

  // Mark notification as read
  const markAsRead = async (notificationId) => {
    try {
      await axios.post(`/api/app-notifications/${notificationId}/read`)
      
      // Update local state
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.isUnread = false
      }
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  // Mark all notifications as read
  const markAllAsRead = async () => {
    try {
      await axios.post('/api/app-notifications/read-all')
      
      // Update local state
      notifications.value.forEach(n => {
        n.isUnread = false
      })
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all as read:', error)
    }
  }

  // Delete notification
  const deleteNotification = async (notificationId) => {
    try {
      await axios.delete(`/api/app-notifications/${notificationId}`)
      
      // Update local state
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index > -1) {
        if (notifications.value[index].isUnread) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch (error) {
      console.error('Failed to delete notification:', error)
    }
  }

  // Get notification statistics
  const fetchStatistics = async () => {
    try {
      const response = await axios.get('/api/app-notifications/statistics')
      return response.data
    } catch (error) {
      console.error('Failed to fetch statistics:', error)
      return null
    }
  }

  return {
    notifications: computed(() => notifications.value),
    unreadCount: computed(() => unreadCount.value),
    loading: computed(() => loading.value),
    fetchUnread,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    fetchStatistics
  }
}