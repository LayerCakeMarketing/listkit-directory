<template>
    <Head title="Create New List" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="createList">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">List Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                ></textarea>
                            </div>

                            <div>
                                <CategorySelect
                                    v-model="form.category_id"
                                    label="Category *"
                                    placeholder="Select a category..."
                                    help-text="Choose the category that best describes your list"
                                />
                            </div>

                            <div>
                                <TagInput
                                    v-model="form.tags"
                                    label="Tags"
                                    placeholder="Search or create tags..."
                                    help-text="Add tags to help people discover your list"
                                    :allow-create="true"
                                    :max-tags="10"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Photos
                                    <span v-if="photos.length > 0" class="text-green-600 font-normal"> - {{ photos.length }} photo(s) uploaded ✓</span>
                                </label>
                                <CloudflareDragDropUploader
                                    :max-files="10"
                                    :max-file-size="14680064"
                                    context="cover"
                                    entity-type="App\Models\UserList"
                                    :entity-id="createdListId"
                                    @upload-success="handlePhotoUpload"
                                    @upload-error="handleUploadError"
                                />
                                
                                <!-- Upload Results with Drag & Drop Reordering -->
                                <DraggableImageGallery 
                                    v-model:images="photos"
                                    title="Uploaded Photos"
                                    :show-primary="true"
                                    @remove="handleGalleryRemove"
                                    @reorder="updateImageOrder"
                                />
                                
                                <p class="text-xs text-gray-500 mt-1">Add photos for your list. The first photo will be used as the featured image. Drag to reorder them.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                                <div class="space-y-2">
                                    <label class="flex items-start">
                                        <input
                                            v-model="form.visibility"
                                            value="public"
                                            type="radio"
                                            class="mt-0.5 rounded-full border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-700">Public</span>
                                            <span class="block text-xs text-gray-500">Visible to all logged-in users and appears in feeds</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-start">
                                        <input
                                            v-model="form.visibility"
                                            value="unlisted"
                                            type="radio"
                                            class="mt-0.5 rounded-full border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-700">Unlisted</span>
                                            <span class="block text-xs text-gray-500">Only accessible via direct URL</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-start">
                                        <input
                                            v-model="form.visibility"
                                            value="private"
                                            type="radio"
                                            class="mt-0.5 rounded-full border-gray-300"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-700">Private</span>
                                            <span class="block text-xs text-gray-500">Only visible to you and people you share with</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Publishing Options</label>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_draft"
                                            type="checkbox"
                                            class="rounded border-gray-300"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Save as draft</span>
                                    </label>
                                    
                                    <div v-if="!form.is_draft">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Schedule for later</label>
                                        <input
                                            v-model="form.scheduled_for"
                                            type="datetime-local"
                                            class="w-full rounded-md border-gray-300"
                                            :min="new Date().toISOString().slice(0, 16)"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <Link
                                :href="route('lists.my')"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ processing ? 'Creating...' : 'Create List' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'
import CategorySelect from '@/Components/UI/CategorySelect.vue'
import TagInput from '@/Components/UI/TagInput.vue'
import DraggableImageGallery from '@/Components/DraggableImageGallery.vue'

const axios = window.axios
const page = usePage()

const form = reactive({
    name: '',
    description: '',
    visibility: 'public',
    is_draft: false,
    scheduled_for: '',
    featured_image: null,
    featured_image_cloudflare_id: null,
    gallery_images: [],
    category_id: '',
    tags: []
})

const processing = ref(false)

// Image upload state
const photos = ref([])
const createdListId = ref(null)

// Handle photo upload
const handlePhotoUpload = (uploadResult) => {
    photos.value.push(uploadResult)
    updateGalleryImages()
    console.log('Photo uploaded:', uploadResult)
}

const handleGalleryRemove = (index, removedImage) => {
    updateGalleryImages()
}

const updateImageOrder = () => {
    updateGalleryImages()
}

const updateGalleryImages = () => {
    // Update gallery_images array with all photos
    form.gallery_images = photos.value.map(photo => ({
        id: photo.id,
        url: photo.url,
        filename: photo.filename
    }))
    
    // Set first image as featured_image for backward compatibility
    if (photos.value.length > 0) {
        form.featured_image = photos.value[0].url
        form.featured_image_cloudflare_id = photos.value[0].id
    } else {
        form.featured_image = null
        form.featured_image_cloudflare_id = null
    }
    
    console.log('Gallery images updated:', form.gallery_images)
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    // Could show a toast notification here
}

const updateImageTracking = async () => {
    if (!createdListId.value) return
    
    const allImages = [...photos.value]
    
    for (const image of allImages) {
        try {
            await window.axios.post('/api/cloudflare/update-tracking', {
                cloudflare_id: image.id,
                entity_type: 'App\Models\UserList',
                entity_id: createdListId.value
            })
        } catch (error) {
            console.warn('Failed to update image tracking:', error)
        }
    }
}

const createList = async () => {
    // Validate required fields
    if (!form.category_id) {
        alert('Please select a category for your list.')
        return
    }

    processing.value = true
    try {
        const response = await axios.post('/data/lists', form)
        
        if (response.data) {
            // Store the created list ID for image uploads
            createdListId.value = response.data.list.id
            
            // Update image tracking with the new list ID if there were uploaded images
            await updateImageTracking()
            
            router.visit(route('lists.edit', [page.props.auth.user.username, response.data.list.slug]))
        }
    } catch (error) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'An unknown error occurred'
        alert('Error creating list: ' + errorMessage)
        processing.value = false
    }
}
</script>