import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
  const add = (options) => {
    const id = nextId++
    const toast = {
      id,
      type: 'info',
      duration: 5000,
      ...options
    }
    
    toasts.value.push(toast)
    
    if (toast.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, toast.duration)
    }
    
    return id
  }
  
  const remove = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const success = (title, message, options = {}) => {
    return add({ ...options, type: 'success', title, message })
  }
  
  const error = (title, message, options = {}) => {
    return add({ ...options, type: 'error', title, message })
  }
  
  const warning = (title, message, options = {}) => {
    return add({ ...options, type: 'warning', title, message })
  }
  
  const info = (title, message, options = {}) => {
    return add({ ...options, type: 'info', title, message })
  }
  
  return {
    toasts,
    add,
    remove,
    success,
    error,
    warning,
    info
  }
}