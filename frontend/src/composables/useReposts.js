import { ref } from 'vue'
import axios from 'axios'

export function useReposts() {
  const loading = ref(false)
  const error = ref(null)

  const createRepost = async (type, id, comment = null) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/reposts', {
        type,
        id: parseInt(id),
        comment
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to repost'
      throw err
    } finally {
      loading.value = false
    }
  }

  const removeRepost = async (type, id) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.delete('/api/reposts', {
        data: {
          type,
          id: parseInt(id)
        }
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to remove repost'
      throw err
    } finally {
      loading.value = false
    }
  }

  const checkRepost = async (type, id) => {
    try {
      const response = await axios.get('/api/reposts/check', {
        params: { type, id: parseInt(id) }
      })
      
      return response.data
    } catch (err) {
      console.error('Failed to check repost status:', err)
      return { reposted: false, reposts_count: 0 }
    }
  }

  const getReposts = async (type, id, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/reposts', {
        params: {
          type,
          id: parseInt(id),
          page,
          per_page: perPage
        }
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load reposts'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getUserReposts = async (type = null, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const params = { page, per_page: perPage }
      if (type) params.type = type
      
      const response = await axios.get('/api/reposts/my-reposts', { params })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load user reposts'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    createRepost,
    removeRepost,
    checkRepost,
    getReposts,
    getUserReposts
  }
}