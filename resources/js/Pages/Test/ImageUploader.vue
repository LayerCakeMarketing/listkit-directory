<template>
    <Head title="Image Uploader Test" />
    
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Drag & Drop Image Uploader Test</h1>
                
                <CloudflareDragDropUploader
                    :max-files="10"
                    :max-file-size="14680064"
                    accepted-types="image/*"
                    @upload-success="handleUploadSuccess"
                    @upload-error="handleUploadError"
                    @upload-progress="handleUploadProgress"
                />
                
                <!-- Upload Results -->
                <div v-if="uploadResults.length > 0" class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Results:</h3>
                    <div class="space-y-4">
                        <div
                            v-for="(result, index) in uploadResults"
                            :key="index"
                            class="bg-gray-50 rounded-lg p-4"
                        >
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <img
                                        :src="result.url"
                                        :alt="result.filename"
                                        class="w-16 h-16 object-cover rounded-lg"
                                    />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">{{ result.filename }}</h4>
                                    <p class="text-sm text-gray-600">ID: {{ result.id }}</p>
                                    <p class="text-sm text-gray-600">URL: {{ result.url }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✓ Uploaded
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Errors -->
                <div v-if="uploadErrors.length > 0" class="mt-8">
                    <h3 class="text-lg font-semibold text-red-900 mb-4">Upload Errors:</h3>
                    <div class="space-y-2">
                        <div
                            v-for="(error, index) in uploadErrors"
                            :key="index"
                            class="bg-red-50 border border-red-200 rounded-lg p-4"
                        >
                            <p class="text-sm text-red-800">{{ error.filename }}: {{ error.message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'

const uploadResults = ref([])
const uploadErrors = ref([])

const handleUploadSuccess = (result) => {
    uploadResults.value.push(result)
}

const handleUploadError = (error) => {
    uploadErrors.value.push(error)
}

const handleUploadProgress = (progress) => {
    console.log('Upload progress:', progress)
}
</script>