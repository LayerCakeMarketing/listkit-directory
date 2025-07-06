<template>
    <Head title="Bulk Import Directory Entries" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <Logo href="/" imgClassName="h-8 w-auto" />
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <Link
                            href="/admin/entries"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            Back to Entries
                        </Link>
                        <Link
                            href="/dashboard"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Dashboard
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-900">Bulk Import Directory Entries</h1>
                    <p class="text-gray-600 mt-1">Upload a CSV file to import multiple directory entries at once</p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">📋 Instructions</h3>
                        <ol class="text-sm text-blue-800 space-y-1">
                            <li>1. Download the CSV template below</li>
                            <li>2. Fill in your directory entry data</li>
                            <li>3. Upload your images to a folder in storage/app/public/ (e.g., "bulk-import-images")</li>
                            <li>4. Reference image filenames in the CSV (logo_filename, cover_image_filename, gallery_filenames)</li>
                            <li>5. Upload your completed CSV file</li>
                        </ol>
                    </div>

                    <!-- Download Template -->
                    <div class="border border-gray-200 rounded-md p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">1. Download CSV Template</h3>
                        <p class="text-gray-600 mb-4">Get the template with all required columns and an example row.</p>
                        <button
                            @click="downloadTemplate"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Template
                        </button>
                    </div>

                    <!-- Upload Form -->
                    <form @submit.prevent="submitUpload" class="space-y-6">
                        <!-- Image Folder Input -->
                        <div class="border border-gray-200 rounded-md p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">2. Specify Image Folder</h3>
                            <p class="text-gray-600 mb-4">Enter the path to your images folder relative to storage/app/public/</p>
                            <div>
                                <label for="image_folder" class="block text-sm font-medium text-gray-700">Image Folder Path</label>
                                <input
                                    v-model="form.image_folder"
                                    type="text"
                                    id="image_folder"
                                    placeholder="e.g., bulk-import-images"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Full path: storage/app/public/{{ form.image_folder || 'your-folder-name' }}
                                </p>
                                <div v-if="errors.image_folder" class="mt-1 text-sm text-red-600">{{ errors.image_folder }}</div>
                            </div>
                        </div>

                        <!-- CSV File Upload -->
                        <div class="border border-gray-200 rounded-md p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">3. Upload CSV File</h3>
                            <p class="text-gray-600 mb-4">Select your completed CSV file to import.</p>
                            
                            <div 
                                @click="triggerFileInput"
                                @dragover.prevent
                                @drop.prevent="handleDrop"
                                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors"
                                :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                            >
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload CSV</span>
                                        or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">CSV files only, up to 10MB</p>
                                </div>
                                
                                <div v-if="selectedFile" class="mt-4 p-3 bg-gray-100 rounded-md">
                                    <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                                </div>
                            </div>

                            <input
                                ref="fileInput"
                                type="file"
                                accept=".csv,.txt"
                                @change="handleFileSelect"
                                class="hidden"
                            />
                            <div v-if="errors.csv_file" class="mt-2 text-sm text-red-600">{{ errors.csv_file }}</div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="processing || !selectedFile"
                                class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                            >
                                <svg v-if="processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ processing ? 'Importing...' : 'Start Import' }}
                            </button>
                        </div>
                    </form>

                    <!-- Results -->
                    <div v-if="results" class="border border-gray-200 rounded-md p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Import Results</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="bg-blue-50 p-3 rounded-md text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ results.total }}</div>
                                <div class="text-sm text-blue-800">Total Rows</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-md text-center">
                                <div class="text-2xl font-bold text-green-600">{{ results.successful }}</div>
                                <div class="text-sm text-green-800">Successful</div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-md text-center">
                                <div class="text-2xl font-bold text-red-600">{{ results.failed }}</div>
                                <div class="text-sm text-red-800">Failed</div>
                            </div>
                        </div>

                        <!-- Error Details -->
                        <div v-if="results.errors && results.errors.length > 0" class="mt-4">
                            <h4 class="font-semibold text-red-900 mb-2">Errors:</h4>
                            <div class="max-h-48 overflow-y-auto">
                                <div v-for="error in results.errors" :key="error.row" class="text-sm bg-red-50 border border-red-200 rounded p-2 mb-2">
                                    <strong>Row {{ error.row }} ({{ error.title }}):</strong> {{ error.error }}
                                </div>
                            </div>
                        </div>

                        <!-- Success Message -->
                        <div v-if="results.successful > 0" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-green-800">
                                ✅ Successfully imported {{ results.successful }} directory entries!
                                <Link href="/admin/entries" class="ml-2 font-medium text-green-700 hover:text-green-600">
                                    View entries →
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import axios from 'axios'
import Logo from '@/Components/Logo.vue'

const form = reactive({
    image_folder: ''
})

const selectedFile = ref(null)
const processing = ref(false)
const errors = ref({})
const results = ref(null)
const isDragging = ref(false)
const fileInput = ref(null)

const downloadTemplate = () => {
    // Use a simple window.open approach to download the file
    // This will work with the session authentication
    window.open('/admin/bulk-import/template', '_blank')
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        selectedFile.value = file
    }
}

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
        selectedFile.value = file
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const submitUpload = async () => {
    if (!selectedFile.value) {
        alert('Please select a CSV file')
        return
    }

    processing.value = true
    errors.value = {}
    results.value = null

    const formData = new FormData()
    formData.append('csv_file', selectedFile.value)
    formData.append('image_folder', form.image_folder)

    try {
        const response = await axios.post('/admin/bulk-import/csv', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

        if (response.data.success) {
            results.value = response.data.results
        } else {
            alert('Import failed: ' + response.data.message)
        }
    } catch (error) {
        console.error('Upload error:', error)
        
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        } else {
            alert('Import failed: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

// Drag and drop events
document.addEventListener('dragenter', (e) => {
    e.preventDefault()
    isDragging.value = true
})

document.addEventListener('dragover', (e) => {
    e.preventDefault()
})

document.addEventListener('dragleave', (e) => {
    if (!e.relatedTarget) {
        isDragging.value = false
    }
})

document.addEventListener('drop', (e) => {
    e.preventDefault()
    isDragging.value = false
})
</script>