<template>
    <div class="cloudflare-image-upload">
        <!-- Trigger slot -->
        <div @click="openUploadDialog" class="cursor-pointer">
            <slot name="trigger">
                <button type="button" class="btn btn-primary">
                    Upload Image
                </button>
            </slot>
        </div>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            multiple
            :disabled="uploading"
            @change="handleFileSelect"
            class="hidden"
        />

        <!-- Upload progress -->
        <div v-if="uploading" class="mt-4">
            <div class="bg-gray-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${uploadProgress}%` }"
                ></div>
            </div>
            <p class="text-sm text-gray-600 mt-2">Uploading... {{ uploadProgress }}%</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    entityType: {
        type: String,
        required: true
    },
    entityId: {
        type: [String, Number],
        required: true
    },
    maxImages: {
        type: Number,
        default: 1
    },
    maxSizeMb: {
        type: Number,
        default: 10
    }
})

const emit = defineEmits(['image-uploaded', 'upload-error'])

const fileInput = ref(null)
const uploading = ref(false)
const uploadProgress = ref(0)

const openUploadDialog = () => {
    fileInput.value?.click()
}

const handleFileSelect = async (event) => {
    const files = Array.from(event.target.files)
    
    if (files.length === 0) return
    
    if (files.length > props.maxImages) {
        emit('upload-error', `You can only upload ${props.maxImages} image(s) at a time`)
        return
    }
    
    for (const file of files) {
        if (file.size > props.maxSizeMb * 1024 * 1024) {
            emit('upload-error', `File ${file.name} is too large. Maximum size is ${props.maxSizeMb}MB`)
            continue
        }
        
        await uploadFile(file)
    }
    
    // Clear the input
    event.target.value = ''
}

const uploadFile = async (file) => {
    uploading.value = true
    uploadProgress.value = 0
    
    try {
        console.log('Starting upload for file:', file.name)
        // Step 1: Get upload URL from backend
        const uploadUrlResponse = await axios.post('/api/cloudflare/upload-url', {
            context: 'cover',
            entity_type: props.entityType,
            entity_id: props.entityId
        })
        
        console.log('Upload URL response:', uploadUrlResponse.data)
        
        const { uploadURL, imageId } = uploadUrlResponse.data
        
        if (!uploadURL || !imageId) {
            throw new Error('Invalid upload URL response')
        }
        
        // Step 2: Upload to Cloudflare
        const formData = new FormData()
        formData.append('file', file)
        
        console.log('Uploading to Cloudflare...')
        
        const uploadResponse = await axios.post(uploadURL, formData, {
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
            }
        })
        
        console.log('Cloudflare upload response:', uploadResponse.data)
        
        if (uploadResponse.data.success) {
            // Step 3: Confirm upload with backend
            const confirmResponse = await axios.post('/api/cloudflare/confirm-upload', {
                cloudflare_id: imageId,
                filename: file.name,
                context: 'cover',
                entity_type: props.entityType,
                entity_id: props.entityId
            })
            
            emit('image-uploaded', {
                id: imageId,
                url: `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${imageId}/public`,
                variants: uploadResponse.data.result?.variants || []
            })
        } else {
            throw new Error(uploadResponse.data.errors?.message || 'Upload failed')
        }
    } catch (error) {
        console.error('Upload error:', error)
        emit('upload-error', error.message || 'Upload failed')
    } finally {
        uploading.value = false
        uploadProgress.value = 0
    }
}
</script>