<template>
    <div class="max-w-2xl mx-auto p-6 space-y-8">
        <h1 class="text-2xl font-bold text-gray-900">Image Upload Examples</h1>
        
        <!-- Avatar Upload -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Profile Avatar</h2>
            <CloudflareImageUpload
                v-model="avatarImage"
                label="Profile Picture"
                upload-type="avatar"
                :entity-id="user.id"
                :current-image-url="user.avatar_url"
                :max-size="5"
                :async-threshold="3"
                @upload-complete="handleAvatarUpload"
                @upload-error="handleUploadError"
                @remove="handleAvatarRemove"
            />
        </div>

        <!-- Cover Image Upload -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Cover Image</h2>
            <CloudflareImageUpload
                v-model="coverImage"
                label="Cover Photo"
                upload-type="cover"
                :entity-id="user.id"
                :current-image-url="user.cover_image_url"
                :max-size="10"
                :async-threshold="5"
                show-variants
                @upload-complete="handleCoverUpload"
                @upload-error="handleUploadError"
                @remove="handleCoverRemove"
            />
        </div>

        <!-- List Image Upload -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">List Featured Image</h2>
            <CloudflareImageUpload
                v-model="listImage"
                label="List Image"
                upload-type="list_image"
                :entity-id="currentList?.id"
                :max-size="15"
                :async-threshold="8"
                @upload-complete="handleListImageUpload"
                @upload-error="handleUploadError"
            />
        </div>

        <!-- Entry Logo Upload -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Business Logo</h2>
            <CloudflareImageUpload
                v-model="entryLogo"
                label="Business Logo"
                upload-type="entry_logo"
                :entity-id="currentEntry?.id"
                :max-size="10"
                :async-threshold="5"
                @upload-complete="handleEntryLogoUpload"
                @upload-error="handleUploadError"
            />
        </div>

        <!-- Results Display -->
        <div v-if="uploadResults.length" class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Upload Results</h3>
            <div class="space-y-4">
                <div v-for="result in uploadResults" :key="result.id" class="flex items-center space-x-4">
                    <img 
                        :src="result.urls.thumbnail" 
                        :alt="result.type"
                        class="w-16 h-16 object-cover rounded"
                    />
                    <div class="flex-1">
                        <div class="font-medium">{{ result.type }} - {{ result.filename }}</div>
                        <div class="text-sm text-gray-500">ID: {{ result.cloudflare_id }}</div>
                        <div class="text-xs text-gray-400">
                            <a :href="result.urls.original" target="_blank" class="text-blue-600 hover:underline">
                                View Original
                            </a>
                        </div>
                    </div>
                    <button
                        @click="deleteImage(result.id)"
                        class="text-red-600 hover:text-red-800 text-sm"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- My Images Gallery -->
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">My Images</h3>
                <button
                    @click="loadMyImages"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Refresh
                </button>
            </div>
            
            <div v-if="loading" class="text-center py-4">Loading...</div>
            
            <div v-else-if="myImages.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="image in myImages" :key="image.id" class="relative group">
                    <img 
                        :src="image.urls.small" 
                        :alt="image.original_name"
                        class="w-full h-32 object-cover rounded"
                    />
                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded flex items-center justify-center">
                        <button
                            @click="deleteImage(image.id)"
                            class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 truncate">{{ image.type }}</div>
                </div>
            </div>
            
            <div v-else class="text-center py-8 text-gray-500">
                No images uploaded yet
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import CloudflareImageUpload from '../ImageUpload/CloudflareImageUpload.vue'

// Mock data - replace with real data from your app
const user = ref({
    id: 1,
    avatar_url: null,
    cover_image_url: null
})

const currentList = ref({ id: 123 })
const currentEntry = ref({ id: 456 })

// Reactive state
const avatarImage = ref(null)
const coverImage = ref(null)
const listImage = ref(null)
const entryLogo = ref(null)
const uploadResults = ref([])
const myImages = ref([])
const loading = ref(false)

// Event handlers
const handleAvatarUpload = (image) => {
    console.log('Avatar uploaded:', image)
    user.value.avatar_url = image.urls.medium
    uploadResults.value.unshift(image)
    
    // Update user avatar in backend
    updateUserAvatar(image.cloudflare_id)
}

const handleCoverUpload = (image) => {
    console.log('Cover uploaded:', image)
    user.value.cover_image_url = image.urls.large
    uploadResults.value.unshift(image)
    
    // Update user cover in backend
    updateUserCover(image.cloudflare_id)
}

const handleListImageUpload = (image) => {
    console.log('List image uploaded:', image)
    uploadResults.value.unshift(image)
    
    // Update list image in backend if needed
}

const handleEntryLogoUpload = (image) => {
    console.log('Entry logo uploaded:', image)
    uploadResults.value.unshift(image)
    
    // Update entry logo in backend if needed
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Upload failed: ' + error.message)
}

const handleAvatarRemove = () => {
    user.value.avatar_url = null
    // Clear avatar in backend
}

const handleCoverRemove = () => {
    user.value.cover_image_url = null
    // Clear cover in backend
}

// API calls
const loadMyImages = async () => {
    loading.value = true
    try {
        const response = await fetch('/api/images', {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        })
        const result = await response.json()
        
        if (result.success) {
            myImages.value = result.images.data
        }
    } catch (error) {
        console.error('Failed to load images:', error)
    } finally {
        loading.value = false
    }
}

const deleteImage = async (imageId) => {
    if (!confirm('Are you sure you want to delete this image?')) return
    
    try {
        const response = await fetch(`/api/images/${imageId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Remove from local arrays
            uploadResults.value = uploadResults.value.filter(img => img.id !== imageId)
            myImages.value = myImages.value.filter(img => img.id !== imageId)
        } else {
            alert('Failed to delete image')
        }
    } catch (error) {
        console.error('Delete error:', error)
        alert('Failed to delete image')
    }
}

const updateUserAvatar = async (cloudflareId) => {
    try {
        await fetch('/api/users/update-avatar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({ cloudflare_id: cloudflareId })
        })
    } catch (error) {
        console.error('Failed to update avatar:', error)
    }
}

const updateUserCover = async (cloudflareId) => {
    try {
        await fetch('/api/users/update-cover', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({ cloudflare_id: cloudflareId })
        })
    } catch (error) {
        console.error('Failed to update cover:', error)
    }
}

const getAuthToken = () => {
    return document.querySelector('meta[name="api-token"]')?.content || ''
}

// Load images on mount
onMounted(() => {
    loadMyImages()
})
</script>