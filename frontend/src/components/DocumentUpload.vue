<template>
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Verification Documents</h3>
        <p class="text-gray-600 mb-6">
            Please upload documents that verify your ownership of this business.
        </p>

        <!-- Document Type Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
            <select
                v-model="documentType"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
                <option value="">Select document type</option>
                <option value="business_license">Business License</option>
                <option value="tax_document">Tax Document</option>
                <option value="utility_bill">Utility Bill</option>
                <option value="incorporation">Articles of Incorporation</option>
                <option value="other">Other</option>
            </select>
        </div>

        <!-- File Upload Area -->
        <div
            @drop="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            @dragleave="isDragging = false"
            :class="[
                'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
                isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
            ]"
            @click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                @change="handleFileSelect"
                accept=".pdf,.jpg,.jpeg,.png"
                class="hidden"
            />
            
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            
            <p class="mt-2 text-sm text-gray-600">
                Drop files here or click to upload
            </p>
            <p class="text-xs text-gray-500 mt-1">
                PDF, JPG, PNG up to 10MB
            </p>
        </div>

        <!-- Uploaded Documents -->
        <div v-if="uploadedDocuments.length > 0" class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Uploaded Documents</h4>
            <div class="space-y-2">
                <div
                    v-for="doc in uploadedDocuments"
                    :key="doc.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ doc.file_name }}</p>
                            <p class="text-xs text-gray-500">{{ doc.document_type_label }} • {{ doc.human_file_size }}</p>
                        </div>
                    </div>
                    <span class="text-xs text-green-600 font-medium">Uploaded</span>
                </div>
            </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="mt-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: uploadProgress + '%' }"
                        ></div>
                    </div>
                </div>
                <span class="ml-3 text-sm text-gray-600">{{ uploadProgress }}%</span>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Continue Button -->
        <div class="mt-6 flex justify-end">
            <button
                @click="continueToNext"
                :disabled="uploadedDocuments.length === 0"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Continue
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    claim: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['uploaded'])

// State
const documentType = ref('')
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const uploadedDocuments = ref([])

// Document type labels
const documentTypeLabels = {
    business_license: 'Business License',
    tax_document: 'Tax Document',
    utility_bill: 'Utility Bill',
    incorporation: 'Articles of Incorporation',
    other: 'Other'
}

// Methods
const handleDrop = (e) => {
    e.preventDefault()
    isDragging.value = false
    
    const files = Array.from(e.dataTransfer.files)
    if (files.length > 0) {
        uploadFile(files[0])
    }
}

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files)
    if (files.length > 0) {
        uploadFile(files[0])
    }
}

const uploadFile = async (file) => {
    // Validate document type
    if (!documentType.value) {
        error.value = 'Please select a document type first'
        return
    }
    
    // Validate file type
    const validTypes = ['application/pdf', 'image/jpeg', 'image/png']
    if (!validTypes.includes(file.type)) {
        error.value = 'Please upload a PDF, JPG, or PNG file'
        return
    }
    
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
        error.value = 'File size must be less than 10MB'
        return
    }
    
    uploading.value = true
    uploadProgress.value = 0
    error.value = ''
    
    const formData = new FormData()
    formData.append('document', file)
    formData.append('document_type', documentType.value)
    
    try {
        const response = await axios.post(
            `/api/claims/${props.claim.id}/documents`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                }
            }
        )
        
        const uploadedDoc = response.data.document
        uploadedDoc.document_type_label = documentTypeLabels[uploadedDoc.document_type]
        uploadedDoc.human_file_size = formatFileSize(file.size)
        
        uploadedDocuments.value.push(uploadedDoc)
        
        // Reset form
        documentType.value = ''
        uploadProgress.value = 0
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to upload document'
    } finally {
        uploading.value = false
    }
}

const formatFileSize = (bytes) => {
    const units = ['B', 'KB', 'MB', 'GB']
    let size = bytes
    let unitIndex = 0
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`
}

const continueToNext = () => {
    emit('uploaded', uploadedDocuments.value)
}
</script>