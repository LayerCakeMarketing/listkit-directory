<template>
    <Head title="Create Directory Entry" />
    
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Directory Entry</h2>
                    <p class="text-gray-600 text-sm mt-1">Add a new business, service, or location to the directory</p>
                </div>
                <div class="flex items-center space-x-2 text-sm">
                    <Link href="/" class="text-gray-500 hover:text-gray-700">Home</Link>
                    <span class="text-gray-400">/</span>
                    <Link
                        v-if="$page.props.auth.user?.role === 'admin' || $page.props.auth.user?.role === 'manager'"
                        href="/admin/entries"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Admin Entries
                    </Link>
                    <Link
                        v-else
                        href="/places"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Directory
                    </Link>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">Create Entry</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md">

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
                                    <option value="physical_location">Physical Location</option>
                                    <option value="online_business">Online Business</option>
                                    <option value="service">Service</option>
                                    <option value="event">Event</option>
                                    <option value="resource">Resource</option>
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
                                entity-type="App\Models\DirectoryEntry"
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
                                entity-type="App\Models\DirectoryEntry"
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
                                entity-type="App\Models\DirectoryEntry"
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
                    <div v-if="form.type === 'physical_location' || form.type === 'event'" class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Location Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="address_line1" class="block text-sm font-medium text-gray-700">Address *</label>
                                <input
                                    v-model="form.location.address_line1"
                                    type="text"
                                    id="address_line1"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
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
                                    :required="form.type === 'physical_location' || form.type === 'event'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.city']" class="mt-1 text-sm text-red-600">{{ errors['location.city'] }}</div>
                            </div>

                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State *</label>
                                <select
                                    v-model="form.location.state"
                                    id="state"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
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
                                <label for="zip_code" class="block text-sm font-medium text-gray-700">ZIP Code *</label>
                                <input
                                    v-model="form.location.zip_code"
                                    type="text"
                                    id="zip_code"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
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
                                <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude *</label>
                                <input
                                    v-model="form.location.latitude"
                                    type="number"
                                    step="any"
                                    id="latitude"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.latitude']" class="mt-1 text-sm text-red-600">{{ errors['location.latitude'] }}</div>
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude *</label>
                                <input
                                    v-model="form.location.longitude"
                                    type="number"
                                    step="any"
                                    id="longitude"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.longitude']" class="mt-1 text-sm text-red-600">{{ errors['location.longitude'] }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <Link
                            :href="$page.props.auth.user?.role === 'admin' || $page.props.auth.user?.role === 'manager' ? '/admin/entries' : '/places'"
                            class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </Link>
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
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, reactive } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'
import AccordionSection from '@/Components/AccordionSection.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import DraggableImageGallery from '@/Components/DraggableImageGallery.vue'
import { usStates } from '@/Data/usStates'

const props = defineProps({
    categories: Array,
})

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

const groupedCategories = computed(() => {
    const groups = {}
    props.categories.forEach(category => {
        const parentName = category.parent.name
        if (!groups[parentName]) {
            groups[parentName] = {
                parent: parentName,
                children: []
            }
        }
        groups[parentName].children.push(category)
    })
    return Object.values(groups)
})

const canGeocode = computed(() => {
    return form.location.address_line1 && form.location.city && form.location.state
})

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
        const response = await window.axios.post('/api/entries', form)
        
        if (response.data) {
            // Store the created entry ID for image uploads
            createdEntryId.value = response.data.id
            
            // Update image tracking with the new entry ID if there were uploaded images
            await updateImageTracking()
            
            // Success - redirect based on user role
            const user = usePage().props.auth?.user
            const redirectUrl = user?.role === 'admin' || user?.role === 'manager' 
                ? '/admin/entries' 
                : '/places'
            
            router.visit(redirectUrl)
        }
    } catch (error) {
        console.error('Form submission error:', error)
        
        if (error.response?.status === 422) {
            // Validation errors
            errors.value = error.response.data.errors || {}
        } else if (error.response?.status === 401) {
            // Authentication error - redirect to login
            router.visit('/login')
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
            await window.axios.post('/api/cloudflare/update-tracking', {
                cloudflare_id: image.id,
                entity_type: 'App\\Models\\DirectoryEntry',
                entity_id: createdEntryId.value
            })
        } catch (error) {
            console.warn('Failed to update image tracking:', error)
        }
    }
}
</script>