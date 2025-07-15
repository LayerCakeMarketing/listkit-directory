import { defineStore } from 'pinia'
import axios from 'axios'

export const useRegionsStore = defineStore('regions', {
  state: () => ({
    regions: [],
    currentRegion: null,
    loading: false,
    error: null
  }),

  getters: {
    states: (state) => state.regions.filter(r => r.level === 1),
    citiesByState: (state) => (stateId) => state.regions.filter(r => r.level === 2 && r.parent_id === stateId),
    neighborhoodsByCity: (state) => (cityId) => state.regions.filter(r => r.level === 3 && r.parent_id === cityId)
  },

  actions: {
    async fetchRegions() {
      this.loading = true
      try {
        const response = await axios.get('/api/regions')
        this.regions = response.data.data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchRegionBySlug(state, city = null, neighborhood = null) {
      this.loading = true
      try {
        let url = `/api/regions/by-slug/${state}`
        if (city) url += `/${city}`
        if (neighborhood) url += `/${neighborhood}`
        
        const response = await axios.get(url)
        this.currentRegion = response.data.data
        return this.currentRegion
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchRegionEntries(regionId, params = {}) {
      try {
        const response = await axios.get(`/api/regions/${regionId}/entries`, { params })
        return response.data
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    async fetchRegionChildren(regionId) {
      try {
        const response = await axios.get(`/api/regions/${regionId}/children`)
        return response.data.data
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    async fetchFeaturedEntries(regionId) {
      try {
        const response = await axios.get(`/api/regions/${regionId}/featured`)
        return response.data.data
      } catch (error) {
        this.error = error.message
        throw error
      }
    }
  }
})