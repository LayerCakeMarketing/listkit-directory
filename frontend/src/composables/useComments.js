import { ref } from 'vue'
import axios from 'axios'

export function useComments() {
  const loading = ref(false)
  const error = ref(null)

  const getComments = async (type, id, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/comments', {
        params: {
          type,
          id: parseInt(id),
          page,
          per_page: perPage
        }
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load comments'
      throw err
    } finally {
      loading.value = false
    }
  }

  const addComment = async (type, id, content, parentId = null) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/comments', {
        type,
        id: parseInt(id),
        content,
        parent_id: parentId ? parseInt(parentId) : null
      })
      
      return response.data.comment
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to add comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateComment = async (commentId, content) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.put(`/api/comments/${commentId}`, {
        content
      })
      
      return response.data.comment
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteComment = async (commentId) => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete(`/api/comments/${commentId}`)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getReplies = async (commentId, page = 1, perPage = 20) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get(`/api/comments/${commentId}/replies`, {
        params: {
          page,
          per_page: perPage
        }
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load replies'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    getComments,
    addComment,
    updateComment,
    deleteComment,
    getReplies
  }
}