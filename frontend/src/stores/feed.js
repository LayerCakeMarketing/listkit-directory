import { defineStore } from 'pinia'
import axios from 'axios'

export const useFeedStore = defineStore('feed', {
  state: () => ({
    posts: [],
    lastFetched: null,
    currentPage: 1,
    hasMore: true,
    isLoading: false,
    isLoadingMore: false,
    error: null,
    // Cache configuration
    cacheTimeout: 5 * 60 * 1000, // 5 minutes
    postsPerPage: 20
  }),

  getters: {
    isCacheFresh: (state) => {
      if (!state.lastFetched) return false
      const now = Date.now()
      return (now - state.lastFetched) < state.cacheTimeout
    },
    
    sortedPosts: (state) => {
      // Ensure posts are sorted by creation date
      return [...state.posts].sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
      )
    }
  },

  actions: {
    async loadFeed(forceRefresh = false) {
      // Use cached data if fresh and not forcing refresh
      if (!forceRefresh && this.isCacheFresh && this.posts.length > 0) {
        console.log('Using cached feed data')
        return this.posts
      }

      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get('/api/home', {
          params: {
            page: 1,
            per_page: this.postsPerPage
          }
        })

        console.log('API Response:', response.data)

        // Handle both paginated and direct array responses
        // The API returns feedItems as a paginated object with data property
        if (response.data.feedItems) {
          this.posts = response.data.feedItems.data || []
          this.hasMore = response.data.feedItems.next_page_url !== null
        } else {
          // Fallback for non-paginated response
          this.posts = response.data.data || []
          this.hasMore = false
        }
        
        this.currentPage = 1
        this.lastFetched = Date.now()

        // Prefetch top 5 images
        this.prefetchImages(this.posts.slice(0, 5))

        return this.posts
      } catch (error) {
        console.error('Error loading feed:', error)
        this.error = error.message
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async loadMore() {
      if (this.isLoadingMore || !this.hasMore) return

      this.isLoadingMore = true
      this.error = null

      try {
        const nextPage = this.currentPage + 1
        const response = await axios.get('/api/home', {
          params: {
            page: nextPage,
            per_page: this.postsPerPage
          }
        })

        let newPosts = []
        
        if (response.data.feedItems) {
          newPosts = response.data.feedItems.data || []
          this.hasMore = response.data.feedItems.next_page_url !== null
        } else {
          newPosts = response.data.data || []
          this.hasMore = false
        }
        
        // Append new posts, avoiding duplicates
        const existingIds = new Set(this.posts.map(p => `${p.feed_type}-${p.id}`))
        const uniqueNewPosts = newPosts.filter(p => !existingIds.has(`${p.feed_type}-${p.id}`))
        
        this.posts = [...this.posts, ...uniqueNewPosts]
        this.currentPage = nextPage

        // Prefetch images for new posts
        this.prefetchImages(uniqueNewPosts.slice(0, 3))

        return uniqueNewPosts
      } catch (error) {
        console.error('Error loading more posts:', error)
        this.error = error.message
        throw error
      } finally {
        this.isLoadingMore = false
      }
    },

    prefetchImages(posts) {
      posts.forEach(post => {
        // Prefetch main image
        if (post.image_url) {
          this.prefetchImage(post.image_url)
        }
        
        // Prefetch user avatar
        if (post.user?.avatar_url) {
          this.prefetchImage(post.user.avatar_url)
        }
        
        // Prefetch place images if it's a place post
        if (post.place?.logo_url) {
          this.prefetchImage(post.place.logo_url)
        }
      })
    },

    prefetchImage(url) {
      if (!url) return
      
      // Use link prefetch for better browser optimization
      const link = document.createElement('link')
      link.rel = 'prefetch'
      link.as = 'image'
      link.href = url
      
      // Remove after prefetch to avoid memory leaks
      link.onload = () => {
        setTimeout(() => link.remove(), 1000)
      }
      
      document.head.appendChild(link)
    },

    // Add a new post to the beginning (for real-time updates)
    prependPost(post) {
      const exists = this.posts.some(p => p.id === post.id)
      if (!exists) {
        this.posts = [post, ...this.posts]
      }
    },

    // Update an existing post
    updatePost(postId, updates) {
      const index = this.posts.findIndex(p => p.id === postId)
      if (index !== -1) {
        this.posts[index] = { ...this.posts[index], ...updates }
      }
    },

    // Remove a post
    removePost(postId) {
      this.posts = this.posts.filter(p => !(p.feed_type === 'post' && p.id === postId))
    },

    // Clear cache and reset state
    clearCache() {
      this.posts = []
      this.lastFetched = null
      this.currentPage = 1
      this.hasMore = true
      this.error = null
    }
  }
})