<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Bulk Import/Export Data</h2>
    
    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'places'"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === 'places' 
              ? 'border-indigo-500 text-indigo-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          Places
        </button>
        <button
          @click="activeTab = 'regions'"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === 'regions' 
              ? 'border-indigo-500 text-indigo-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          Regions
        </button>
      </nav>
    </div>

    <!-- Content -->
    <div v-if="activeTab === 'places'" class="space-y-6">
      <!-- Export Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium mb-4">Export Places</h3>
        <p class="text-sm text-gray-600 mb-4">
          Export all places to CSV format. This will include all place data, categories, regions, and location information.
        </p>
        <button
          @click="exportData('places')"
          :disabled="exporting.places"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <svg v-if="!exporting.places" class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ exporting.places ? 'Exporting...' : 'Export Places' }}
        </button>
      </div>

      <!-- Import Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium mb-4">Import Places</h3>
        <p class="text-sm text-gray-600 mb-4">
          Import places from a CSV file. Download the template to see the required format.
        </p>
        
        <div class="flex items-center space-x-4 mb-4">
          <button
            @click="downloadTemplate('places')"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download Template
          </button>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            CSV File
          </label>
          <input
            type="file"
            accept=".csv"
            @change="handleFileSelect('places', $event)"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
          />
        </div>

        <div class="mb-4">
          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="updateExisting.places"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-600">
              Update existing places (uncheck to only create new places)
            </span>
          </label>
        </div>

        <button
          @click="importData('places')"
          :disabled="!selectedFiles.places || importing.places"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <svg v-if="!importing.places" class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ importing.places ? 'Importing...' : 'Import Places' }}
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'regions'" class="space-y-6">
      <!-- Export Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium mb-4">Export Regions</h3>
        <p class="text-sm text-gray-600 mb-4">
          Export all regions to CSV format. This will include the hierarchy and location data.
        </p>
        <button
          @click="exportData('regions')"
          :disabled="exporting.regions"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <svg v-if="!exporting.regions" class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ exporting.regions ? 'Exporting...' : 'Export Regions' }}
        </button>
      </div>

      <!-- Import Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium mb-4">Import Regions</h3>
        <p class="text-sm text-gray-600 mb-4">
          Import regions from a CSV file. Download the template to see the required format.
        </p>
        
        <div class="flex items-center space-x-4 mb-4">
          <button
            @click="downloadTemplate('regions')"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download Template
          </button>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            CSV File
          </label>
          <input
            type="file"
            accept=".csv"
            @change="handleFileSelect('regions', $event)"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
          />
        </div>

        <div class="mb-4">
          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="updateExisting.regions"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-600">
              Update existing regions (uncheck to only create new regions)
            </span>
          </label>
        </div>

        <button
          @click="importData('regions')"
          :disabled="!selectedFiles.regions || importing.regions"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <svg v-if="!importing.regions" class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ importing.regions ? 'Importing...' : 'Import Regions' }}
        </button>
      </div>
    </div>

    <!-- Results/Errors Display -->
    <div v-if="importResults" class="mt-6">
      <div :class="[
        'rounded-md p-4',
        importResults.success ? 'bg-green-50' : 'bg-red-50'
      ]">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg v-if="importResults.success" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 :class="[
              'text-sm font-medium',
              importResults.success ? 'text-green-800' : 'text-red-800'
            ]">
              {{ importResults.success ? 'Import Completed' : 'Import Failed' }}
            </h3>
            <div v-if="importResults.results" class="mt-2 text-sm text-gray-700">
              <p>Total rows: {{ importResults.results.total }}</p>
              <p v-if="importResults.results.created > 0">Created: {{ importResults.results.created }}</p>
              <p v-if="importResults.results.updated > 0">Updated: {{ importResults.results.updated }}</p>
              <div v-if="importResults.results.errors && importResults.results.errors.length > 0" class="mt-2">
                <p class="font-medium text-red-800">Errors:</p>
                <ul class="mt-1 list-disc list-inside text-red-700">
                  <li v-for="(error, index) in importResults.results.errors" :key="index">
                    {{ error }}
                  </li>
                </ul>
              </div>
            </div>
            <div v-else-if="importResults.message" class="mt-2 text-sm text-red-700">
              {{ importResults.message }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const activeTab = ref('places')
const selectedFiles = ref({
  places: null,
  regions: null
})
const updateExisting = ref({
  places: true,
  regions: true
})
const exporting = ref({
  places: false,
  regions: false
})
const importing = ref({
  places: false,
  regions: false
})
const importResults = ref(null)

const handleFileSelect = (type, event) => {
  const file = event.target.files[0]
  if (file && file.type === 'text/csv') {
    selectedFiles.value[type] = file
    importResults.value = null
  } else {
    alert('Please select a valid CSV file')
    event.target.value = ''
    selectedFiles.value[type] = null
  }
}

const exportData = async (type) => {
  exporting.value[type] = true
  importResults.value = null
  
  try {
    const response = await axios.get(`/api/admin/bulk-data/export/${type}`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type}_export_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Export failed:', error)
    alert('Failed to export data. Please try again.')
  } finally {
    exporting.value[type] = false
  }
}

const importData = async (type) => {
  if (!selectedFiles.value[type]) {
    alert('Please select a file to import')
    return
  }
  
  importing.value[type] = true
  importResults.value = null
  
  const formData = new FormData()
  formData.append('file', selectedFiles.value[type])
  formData.append('update_existing', updateExisting.value[type])
  
  try {
    const response = await axios.post(`/api/admin/bulk-data/import/${type}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    importResults.value = response.data
    
    // Clear file input on success
    if (response.data.success) {
      selectedFiles.value[type] = null
      const fileInput = document.querySelector(`input[type="file"]`)
      if (fileInput) fileInput.value = ''
    }
  } catch (error) {
    console.error('Import failed:', error)
    importResults.value = {
      success: false,
      message: error.response?.data?.message || 'Failed to import data. Please check your file format and try again.'
    }
  } finally {
    importing.value[type] = false
  }
}

const downloadTemplate = async (type) => {
  try {
    const response = await axios.get(`/api/admin/bulk-data/template/${type}`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type}_import_template.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Template download failed:', error)
    alert('Failed to download template. Please try again.')
  }
}
</script>