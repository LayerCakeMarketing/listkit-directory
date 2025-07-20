import { ref } from 'vue'

// Global notification state
const notifications = ref([])
let notificationId = 0

export function useNotification() {
  const showSuccess = (title = 'Saved', message = '', duration = 3000) => {
    const id = ++notificationId
    const notification = {
      id,
      type: 'success',
      title,
      message,
      duration,
      visible: true
    }
    
    notifications.value.push(notification)
    
    // Auto remove after duration + animation time
    if (duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, duration + 500)
    }
    
    return id
  }
  
  const showError = (title = 'Error', message = '', duration = 5000) => {
    const id = ++notificationId
    const notification = {
      id,
      type: 'error',
      title,
      message,
      duration,
      visible: true
    }
    
    notifications.value.push(notification)
    
    // Auto remove after duration + animation time
    if (duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, duration + 500)
    }
    
    return id
  }
  
  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  const closeNotification = (id) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.visible = false
      // Remove after animation
      setTimeout(() => {
        removeNotification(id)
      }, 300)
    }
  }
  
  return {
    notifications,
    showSuccess,
    showError,
    closeNotification
  }
}