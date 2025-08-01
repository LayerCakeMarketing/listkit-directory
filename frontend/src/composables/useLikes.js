import { ref } from 'vue'
import axios from 'axios'

export function useLikes() {
  const loading = ref(false)
  const error = ref(null)

  const toggleLike = async (type, id) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/likes/toggle', {
        type,
        id: parseInt(id)
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to toggle like'
      throw err
    } finally {
      loading.value = false
    }
  }

  const checkLike = async (type, id) => {
    try {
      const response = await axios.get('/api/likes/check', {
        params: { type, id: parseInt(id) }
      })
      
      return response.data
    } catch (err) {
      console.error('Failed to check like status:', err)
      return { liked: false, likes_count: 0 }
    }
  }

  const getLikes = async (type, id, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/likes', {
        params: {
          type,
          id: parseInt(id),
          page,
          per_page: perPage
        }
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load likes'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getUserLikes = async (type = null, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const params = { page, per_page: perPage }
      if (type) params.type = type
      
      const response = await axios.get('/api/likes/my-likes', { params })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load user likes'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    toggleLike,
    checkLike,
    getLikes,
    getUserLikes
  }
}