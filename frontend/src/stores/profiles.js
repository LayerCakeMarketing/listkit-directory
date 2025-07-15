import { defineStore } from 'pinia'
import axios from 'axios'

export const useProfilesStore = defineStore('profiles', {
  state: () => ({
    // Cache profiles by custom URL or username
    profileCache: {},
    // Track when profiles were last fetched
    fetchTimestamps: {},
    // Cache duration in milliseconds (5 minutes)
    cacheDuration: 5 * 60 * 1000
  }),

  getters: {
    getCachedProfile: (state) => (customUrl) => {
      const cached = state.profileCache[customUrl]
      const timestamp = state.fetchTimestamps[customUrl]
      
      if (!cached || !timestamp) return null
      
      // Check if cache is still valid
      const now = Date.now()
      if (now - timestamp > state.cacheDuration) {
        // Cache expired
        return null
      }
      
      return cached
    }
  },

  actions: {
    async fetchProfile(customUrl, forceRefresh = false) {
      // Check cache first unless force refresh
      if (!forceRefresh) {
        const cached = this.getCachedProfile(customUrl)
        if (cached) {
          console.log('Using cached profile for:', customUrl)
          return cached
        }
      }

      try {
        console.log('Fetching fresh profile for:', customUrl)
        const response = await axios.get(`/api/@${customUrl}`)
        
        // Store in cache
        this.profileCache[customUrl] = response.data
        this.fetchTimestamps[customUrl] = Date.now()
        
        return response.data
      } catch (error) {
        console.error('Error fetching profile:', error)
        throw error
      }
    },

    // Clear specific profile from cache
    clearProfileCache(customUrl) {
      delete this.profileCache[customUrl]
      delete this.fetchTimestamps[customUrl]
    },

    // Clear all profile caches
    clearAllProfiles() {
      this.profileCache = {}
      this.fetchTimestamps = {}
    },

    // Update specific fields in cached profile
    updateCachedProfile(customUrl, updates) {
      if (this.profileCache[customUrl]) {
        if (updates.user) {
          this.profileCache[customUrl].user = {
            ...this.profileCache[customUrl].user,
            ...updates.user
          }
        }
        if (updates.pinnedLists !== undefined) {
          this.profileCache[customUrl].pinnedLists = updates.pinnedLists
        }
        if (updates.recentLists !== undefined) {
          this.profileCache[customUrl].recentLists = updates.recentLists
        }
      }
    },

    // Preload profiles (useful for navigation)
    async preloadProfiles(customUrls) {
      const promises = customUrls.map(url => {
        // Only fetch if not already cached
        if (!this.getCachedProfile(url)) {
          return this.fetchProfile(url)
        }
        return Promise.resolve()
      })
      
      await Promise.all(promises)
    }
  }
})