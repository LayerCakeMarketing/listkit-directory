<template>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 py-16">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Directory Entry</h2>
                        <p class="text-gray-600 text-sm mt-1">Add a new business, service, or location to the directory</p>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                        <span class="text-gray-400">/</span>
                        <router-link
                            v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'manager'"
                            to="/admin/entries"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            Admin Entries
                        </router-link>
                        <router-link
                            v-else
                            to="/places"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            Directory
                        </router-link>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">Create Entry</span>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="loadError" class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading form</h3>
                        <p class="text-sm text-red-700 mt-1">{{ loadError }}</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div v-else class="bg-white rounded-lg shadow-md">
                <!-- Info Message -->
                <div class="bg-blue-50 border-b border-blue-200 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Create your place as a draft</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Your place will be saved as a draft. You can edit it as many times as you need to make it perfect. When you're ready, submit it for review from the edit page.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    id="title"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</div>
                            </div>

                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type *</label>
                                <select
                                    v-model="form.type"
                                    id="type"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select a type</option>
                                    <option value="business_b2b">Business B2B</option>
                                    <option value="business_b2c">Business B2C</option>
                                    <option value="religious_org">Church, Synagogue, Temple or Other</option>
                                    <option value="point_of_interest">Point of Interest</option>
                                    <option value="area_of_interest">Area of Interest</option>
                                    <option value="service">Service</option>
                                    <option value="online">Online</option>
                                </select>
                                <div v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</div>
                            </div>
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                            <select
                                v-model="form.category_id"
                                id="category_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select a category</option>
                                <optgroup v-for="group in groupedCategories" :key="group.parent" :label="group.parent">
                                    <option v-for="category in group.children" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </optgroup>
                            </select>
                            <div v-if="errors.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id }}</div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <RichTextEditor
                                v-model="form.description"
                                :placeholder="'Write a detailed description about ' + (form.title || 'this place') + '...'"
                            />
                            <div v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</div>
                        </div>
                    </div>

                    <!-- Images -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Images</h2>
                        
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Logo
                                <span v-if="logoImages.length > 0" class="text-green-600 font-normal"> - {{ logoImages.length }} image(s) uploaded ✓</span>
                            </label>
                            <CloudflareDragDropUploader
                                :max-files="1"
                                :max-file-size="2097152"
                                context="logo"
                                entity-type="App\Models\Place"
                                :entity-id="createdEntryId"
                                @upload-success="handleLogoUpload"
                                @upload-error="handleUploadError"
                            />
                        </div>

                        <!-- Cover Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Image
                                <span v-if="coverImages.length > 0" class="text-green-600 font-normal"> - {{ coverImages.length }} image(s) uploaded ✓</span>
                            </label>
                            <CloudflareDragDropUploader
                                :max-files="1"
                                :max-file-size="5242880"
                                context="cover"
                                entity-type="App\Models\Place"
                                :entity-id="createdEntryId"
                                @upload-success="handleCoverUpload"
                                @upload-error="handleUploadError"
                            />
                        </div>

                        <!-- Gallery Images -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Gallery Images
                                <span v-if="galleryImages.length > 0" class="text-green-600 font-normal"> - {{ galleryImages.length }} image(s) uploaded ✓</span>
                            </label>
                            <CloudflareDragDropUploader
                                :max-files="20"
                                :max-file-size="14680064"
                                context="gallery"
                                entity-type="App\Models\Place"
                                :entity-id="createdEntryId"
                                @upload-success="handleGalleryUpload"
                                @upload-error="handleUploadError"
                            />
                            
                            <!-- Upload Results for Gallery -->
                            <DraggableImageGallery 
                                v-model:images="galleryImages"
                                title="Uploaded Gallery Images"
                                @remove="handleGalleryRemove"
                                @reorder="updateGalleryOrder"
                            />
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    id="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    id="phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</div>
                            </div>
                        </div>

                        <div>
                            <label for="website_url" class="block text-sm font-medium text-gray-700">Website</label>
                            <input
                                v-model="form.website_url"
                                type="url"
                                id="website_url"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="errors.website_url" class="mt-1 text-sm text-red-600">{{ errors.website_url }}</div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Location Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="address_line1" class="block text-sm font-medium text-gray-700">
                                    Address 
                                    <span v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)">*</span>
                                </label>
                                <input
                                    v-model="form.location.address_line1"
                                    type="text"
                                    id="address_line1"
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.address_line1']" class="mt-1 text-sm text-red-600">{{ errors['location.address_line1'] }}</div>
                            </div>

                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700">City *</label>
                                <input
                                    v-model="form.location.city"
                                    type="text"
                                    id="city"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.city']" class="mt-1 text-sm text-red-600">{{ errors['location.city'] }}</div>
                            </div>

                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State *</label>
                                <select
                                    v-model="form.location.state"
                                    id="state"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select a state</option>
                                    <option v-for="state in usStates" :key="state.code" :value="state.code">
                                        {{ state.name }}
                                    </option>
                                </select>
                                <div v-if="errors['location.state']" class="mt-1 text-sm text-red-600">{{ errors['location.state'] }}</div>
                            </div>

                            <div>
                                <label for="zip_code" class="block text-sm font-medium text-gray-700">
                                    ZIP Code 
                                    <span v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)">*</span>
                                </label>
                                <input
                                    v-model="form.location.zip_code"
                                    type="text"
                                    id="zip_code"
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.zip_code']" class="mt-1 text-sm text-red-600">{{ errors['location.zip_code'] }}</div>
                            </div>

                            <div>
                                <label for="neighborhood" class="block text-sm font-medium text-gray-700">Neighborhood <span class="text-gray-500 text-xs">(optional)</span></label>
                                <input
                                    v-model="form.location.neighborhood"
                                    type="text"
                                    id="neighborhood"
                                    placeholder="e.g., Downtown, Midtown, Financial District"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.neighborhood']" class="mt-1 text-sm text-red-600">{{ errors['location.neighborhood'] }}</div>
                            </div>

                            <div class="md:col-span-2">
                                <button
                                    type="button"
                                    @click="geocodeAddress"
                                    :disabled="!canGeocode || geocoding"
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-50"
                                >
                                    {{ geocoding ? 'Finding coordinates...' : 'Find Coordinates' }}
                                </button>
                                <p class="text-sm text-gray-500 mt-1">Click to automatically fill latitude and longitude</p>
                            </div>

                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700">
                                    Latitude 
                                    <span v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)">*</span>
                                </label>
                                <input
                                    v-model="form.location.latitude"
                                    type="number"
                                    step="any"
                                    id="latitude"
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.latitude']" class="mt-1 text-sm text-red-600">{{ errors['location.latitude'] }}</div>
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700">
                                    Longitude 
                                    <span v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)">*</span>
                                </label>
                                <input
                                    v-model="form.location.longitude"
                                    type="number"
                                    step="any"
                                    id="longitude"
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.longitude']" class="mt-1 text-sm text-red-600">{{ errors['location.longitude'] }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <router-link
                            :to="authStore.user?.role === 'admin' || authStore.user?.role === 'manager' ? '/admin/entries' : '/places'"
                            class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </router-link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ processing ? 'Creating...' : 'Create Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import { usStates } from '@/data/usStates'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const loadError = ref(null)
const categories = ref([])

const form = reactive({
    title: '',
    description: '',
    type: '',
    category_id: '',
    email: '',
    phone: '',
    website_url: '',
    logo_url: '',
    cover_image_url: '',
    gallery_images: [],
    location: {
        address_line1: '',
        city: '',
        state: '',
        zip_code: '',
        neighborhood: '',
        latitude: '',
        longitude: '',
    }
})

const errors = ref({})
const processing = ref(false)
const geocoding = ref(false)

// Image upload state
const logoImages = ref([])
const coverImages = ref([])
const galleryImages = ref([])
const createdEntryId = ref(null)

// Computed
const groupedCategories = computed(() => {
    const groups = {}
    
    // First, add all parent categories
    categories.value.forEach(category => {
        if (!category.parent_id) {
            if (!groups[category.name]) {
                groups[category.name] = {
                    parent: category.name,
                    children: []
                }
            }
        }
    })
    
    // Then, add children to their respective parents
    categories.value.forEach(category => {
        if (category.parent_id && category.parent) {
            const parentName = category.parent.name
            if (!groups[parentName]) {
                groups[parentName] = {
                    parent: parentName,
                    children: []
                }
            }
            groups[parentName].children.push(category)
        }
    })
    
    return Object.values(groups)
})

const canGeocode = computed(() => {
    return form.location.address_line1 && form.location.city && form.location.state
})

// Methods
const fetchCategories = async () => {
    loading.value = true
    loadError.value = null

    try {
        const response = await axios.get('/api/categories')
        categories.value = response.data.categories || []
    } catch (err) {
        loadError.value = err.response?.data?.message || 'Failed to load categories'
        console.error('Error fetching categories:', err)
    } finally {
        loading.value = false
    }
}

// Handle upload completions
const handleLogoUpload = (uploadResult) => {
    logoImages.value.push(uploadResult)
    form.logo_url = uploadResult.url
    console.log('Logo uploaded:', uploadResult)
}

const handleCoverUpload = (uploadResult) => {
    coverImages.value.push(uploadResult)
    form.cover_image_url = uploadResult.url
    console.log('Cover image uploaded:', uploadResult)
}

const handleGalleryUpload = (uploadResult) => {
    galleryImages.value.push(uploadResult)
    form.gallery_images.push(uploadResult.url)
    console.log('Gallery image uploaded:', uploadResult)
}

const handleGalleryRemove = (index, removedImage) => {
    const urlIndex = form.gallery_images.indexOf(removedImage.url)
    if (urlIndex > -1) {
        form.gallery_images.splice(urlIndex, 1)
    }
}

const updateGalleryOrder = (reorderedImages) => {
    form.gallery_images = reorderedImages.map(img => img.url)
    console.log('Gallery order updated:', form.gallery_images)
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    // Could show a toast notification here
}

const geocodeAddress = async () => {
    if (!canGeocode.value) return
    
    geocoding.value = true
    
    const address = `${form.location.address_line1}, ${form.location.city}, ${form.location.state} ${form.location.zip_code}`
    
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
        const data = await response.json()
        
        if (data.length > 0) {
            form.location.latitude = parseFloat(data[0].lat)
            form.location.longitude = parseFloat(data[0].lon)
        } else {
            alert('Could not find coordinates for this address. Please enter them manually.')
        }
    } catch (error) {
        console.error('Geocoding error:', error)
        alert('Error finding coordinates. Please enter them manually.')
    } finally {
        geocoding.value = false
    }
}

const submitForm = async () => {
    processing.value = true
    errors.value = {}
    
    try {
        const response = await axios.post('/api/places', form)
        
        if (response.data) {
            // Store the created entry ID for image uploads
            createdEntryId.value = response.data.id
            
            // Update image tracking with the new entry ID if there were uploaded images
            await updateImageTracking()
            
            // Redirect to the edit page so user can continue working on it
            router.push(`/places/${response.data.id}/edit`)
        }
    } catch (error) {
        console.error('Form submission error:', error)
        
        if (error.response?.status === 422) {
            // Validation errors
            errors.value = error.response.data.errors || {}
        } else if (error.response?.status === 401) {
            // Authentication error - redirect to login
            router.push('/login')
        } else {
            // Other errors
            alert('Error creating entry: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const updateImageTracking = async () => {
    if (!createdEntryId.value) return
    
    const allImages = [...logoImages.value, ...coverImages.value, ...galleryImages.value]
    
    for (const image of allImages) {
        try {
            await axios.post('/api/cloudflare/update-tracking', {
                cloudflare_id: image.id,
                entity_type: 'App\\Models\\DirectoryEntry',
                entity_id: createdEntryId.value
            })
        } catch (error) {
            console.warn('Failed to update image tracking:', error)
        }
    }
}

// Initialize
onMounted(() => {
    document.title = 'Create Directory Entry'
    fetchCategories()
})
</script>