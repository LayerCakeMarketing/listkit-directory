<template>
    <div>
        <!-- Breadcrumb -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="flex items-center space-x-2 py-3 text-sm">
                    <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                    <span class="text-gray-400">/</span>
                    <router-link
                        v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'manager'"
                        to="/admin/places"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Admin Places
                    </router-link>
                    <router-link
                        v-else
                        to="/places"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Directory
                    </router-link>
                    <span class="text-gray-400">/</span>
                    <a :href="getEntryViewUrl()" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">{{ entry?.title }}</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">Edit</span>
                </div>
            </div>
        </nav>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="loadError" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ errorTitle }}</h3>
                        <p class="text-sm text-red-700 mt-1">{{ loadError }}</p>
                        <div class="mt-3 text-sm">
                            <button 
                                @click="router.push('/places')"
                                class="text-red-800 hover:text-red-900 font-medium"
                            >
                                ← Back to Directory
                            </button>
                            <span v-if="errorType === 'auth'" class="mx-2">|</span>
                            <router-link 
                                v-if="errorType === 'auth'"
                                to="/login"
                                class="text-red-800 hover:text-red-900 font-medium"
                            >
                                Login
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else-if="entry" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Directory Entry</h1>
                            <p class="text-gray-600 mt-1">Update information for {{ entry.title }}</p>
                        </div>
                        <a
                            :href="getEntryViewUrl()"
                            target="_blank"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </a>
                    </div>
                    
                    <!-- Success Message -->
                    <div v-if="showSuccessMessage" class="mt-4 bg-green-50 border border-green-200 rounded-md p-3">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-green-800 text-sm font-medium">Entry updated successfully!</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Basic Information Section -->
                    <AccordionSection title="Basic Information" :default-open="true">
                        <div class="space-y-4">
                        
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
                    </AccordionSection>

                    <!-- Contact + Social Section -->
                    <AccordionSection title="Contact + Social">
                        <div class="space-y-4">
                            <!-- Existing contact fields -->
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

                            <!-- Social Media Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook URL</label>
                                    <input
                                        v-model="form.facebook_url"
                                        type="url"
                                        id="facebook_url"
                                        placeholder="https://facebook.com/yourpage"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="instagram_handle" class="block text-sm font-medium text-gray-700">Instagram Handle</label>
                                    <input
                                        v-model="form.instagram_handle"
                                        type="text"
                                        id="instagram_handle"
                                        placeholder="@yourusername"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="twitter_handle" class="block text-sm font-medium text-gray-700">Twitter Handle</label>
                                    <input
                                        v-model="form.twitter_handle"
                                        type="text"
                                        id="twitter_handle"
                                        placeholder="@yourusername"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="youtube_channel" class="block text-sm font-medium text-gray-700">YouTube Channel</label>
                                    <input
                                        v-model="form.youtube_channel"
                                        type="url"
                                        id="youtube_channel"
                                        placeholder="https://youtube.com/c/yourchannel"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </AccordionSection>

                    <!-- Media Section -->
                    <AccordionSection title="Media">
                        <div class="space-y-4">
                        
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
                                :entity-id="entry.id"
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
                                :entity-id="entry.id"
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
                                :entity-id="entry.id"
                                @upload-success="handleGalleryUpload"
                                @upload-error="handleUploadError"
                            />
                            
                            <!-- Upload Results for Gallery -->
                            <DraggableImageGallery 
                                v-model:images="galleryImages"
                                title="New Gallery Images"
                                @remove="handleNewGalleryRemove"
                                @reorder="updateNewGalleryOrder"
                            />
                            
                            <!-- Existing Gallery Images -->
                            <DraggableImageGallery 
                                v-if="existingGalleryImages.length > 0"
                                v-model:images="existingGalleryImages"
                                title="Current Gallery Images"
                                @remove="handleExistingGalleryRemove"
                                @reorder="updateExistingGalleryOrder"
                            />
                        </div>
                        </div>
                    </AccordionSection>

                    <!-- Location/Address Section -->
                    <AccordionSection 
                        title="Address / Location" 
                        v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                    >
                        <div class="space-y-4">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="address_line1" class="block text-sm font-medium text-gray-700">Address *</label>
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
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors['location.city']" class="mt-1 text-sm text-red-600">{{ errors['location.city'] }}</div>
                            </div>

                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State *</label>
                                <select
                                    v-model="form.location.state"
                                    id="state"
                                    :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
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
                                    {{ geocoding ? 'Finding coordinates...' : 'Update Coordinates' }}
                                </button>
                                <p class="text-sm text-gray-500 mt-1">Click to automatically update latitude and longitude</p>
                            </div>

                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude *</label>
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
                                <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude *</label>
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
                    </AccordionSection>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <router-link
                            :to="authStore.user?.role === 'admin' || authStore.user?.role === 'manager' ? '/admin/entries' : entry.url"
                            class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </router-link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ processing ? 'Updating...' : 'Update Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import AccordionSection from '@/components/AccordionSection.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import { usStates } from '@/data/usStates'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const loadError = ref(null)
const errorTitle = ref('Error loading entry')
const errorType = ref('general')
const entry = ref(null)
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
    
    // Social Media
    facebook_url: '',
    instagram_handle: '',
    twitter_handle: '',
    youtube_channel: '',
    messenger_contact: '',
    
    // Location
    location: {
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        zip_code: '',
        country: 'US',
        latitude: '',
        longitude: '',
        cross_streets: '',
        neighborhood: '',
    }
})

const errors = ref({})
const processing = ref(false)
const geocoding = ref(false)
const showSuccessMessage = ref(false)

// Image upload state
const logoImages = ref([])
const coverImages = ref([])
const galleryImages = ref([])
const existingGalleryImages = ref([])

// Computed
const groupedCategories = computed(() => {
    const groups = {}
    
    // First, add parent categories
    categories.value.forEach(category => {
        if (!category.parent_id) {
            groups[category.name] = {
                parent: category.name,
                children: []
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
const getEntryViewUrl = () => {
    if (!entry.value) return '#'
    
    // Use canonical URL structure: /places/state/city/category/entry-slug-id
    if (entry.value.state_region && entry.value.city_region && entry.value.category) {
        const state = entry.value.state_region.slug
        const city = entry.value.city_region.slug
        const category = entry.value.category.slug
        const entrySlug = `${entry.value.slug}-${entry.value.id}`
        return `/places/${state}/${city}/${category}/${entrySlug}`
    }
    
    // Fallback for entries without complete regional data
    return `/places/entry/${entry.value.id}`
}

const fetchEntryData = async () => {
    loading.value = true
    loadError.value = null

    // Check if user is authenticated first
    if (!authStore.user) {
        console.log('User not authenticated, waiting for auth check...')
        // Wait a bit for auth to load
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        if (!authStore.user) {
            errorTitle.value = 'Authentication required'
            errorType.value = 'auth'
            loadError.value = 'You must be logged in to edit entries.'
            loading.value = false
            return
        }
    }

    try {
        // Fetch entry data using admin endpoint for full edit access
        const entryResponse = await axios.get(`/api/admin/places/${route.params.id}`)
        console.log('Entry API response:', entryResponse.data)
        
        // Admin endpoint returns the entry directly
        entry.value = entryResponse.data
        
        // Fetch categories
        const categoriesResponse = await axios.get('/api/categories')
        console.log('Categories API response:', categoriesResponse.data)
        categories.value = categoriesResponse.data.categories || categoriesResponse.data || []
        
        // Initialize form with entry data
        console.log('About to initialize form with entry:', entry.value)
        initializeForm()
        
        console.log('Form after initialization:', form)
        document.title = `Edit ${entry.value.title}`
    } catch (err) {
        console.error('Error fetching entry:', err)
        
        if (err.response?.status === 404) {
            errorTitle.value = 'Entry not found'
            errorType.value = 'notfound'
            loadError.value = 'The entry you are trying to edit could not be found. It may have been deleted or you may not have permission to view it.'
        } else if (err.response?.status === 401) {
            errorTitle.value = 'Authentication required'
            errorType.value = 'auth'
            loadError.value = 'You must be logged in to edit entries. Please login and try again.'
        } else if (err.response?.status === 403) {
            errorTitle.value = 'Permission denied'
            errorType.value = 'permission'
            loadError.value = 'You do not have permission to edit this entry. Only admins, managers, and entry owners can edit entries.'
        } else {
            errorTitle.value = 'Error loading entry'
            errorType.value = 'general'
            loadError.value = err.response?.data?.message || 'An unexpected error occurred while loading the entry. Please try again later.'
        }
        
        // Log detailed error info for debugging
        console.log('Edit page error details:', {
            status: err.response?.status,
            message: err.response?.data?.message,
            user: authStore.user,
            entryId: route.params.id
        })
    } finally {
        loading.value = false
    }
}

const initializeForm = () => {
    if (!entry.value) return
    
    form.title = entry.value.title || ''
    form.description = entry.value.description || ''
    form.type = entry.value.type || ''
    form.category_id = entry.value.category_id || ''
    form.email = entry.value.email || ''
    form.phone = entry.value.phone || ''
    form.website_url = entry.value.website_url || ''
    form.logo_url = entry.value.logo_url || ''
    form.cover_image_url = entry.value.cover_image_url || ''
    form.gallery_images = entry.value.gallery_images || []
    
    // Initialize existing gallery images for drag-and-drop component
    if (entry.value.gallery_images && entry.value.gallery_images.length > 0) {
        existingGalleryImages.value = entry.value.gallery_images.map((url, index) => ({
            id: `existing-${index}`,
            url: url,
            filename: `Gallery Image ${index + 1}`
        }))
    }
    
    // Social Media
    form.facebook_url = entry.value.facebook_url || ''
    form.instagram_handle = entry.value.instagram_handle || ''
    form.twitter_handle = entry.value.twitter_handle || ''
    form.youtube_channel = entry.value.youtube_channel || ''
    form.messenger_contact = entry.value.messenger_contact || ''
    
    if (entry.value.location) {
        form.location.address_line1 = entry.value.location.address_line1 || ''
        form.location.address_line2 = entry.value.location.address_line2 || ''
        form.location.city = entry.value.location.city || ''
        form.location.state = entry.value.location.state || ''
        form.location.zip_code = entry.value.location.zip_code || ''
        form.location.country = entry.value.location.country || 'US'
        form.location.latitude = entry.value.location.latitude || ''
        form.location.longitude = entry.value.location.longitude || ''
        form.location.cross_streets = entry.value.location.cross_streets || ''
        form.location.neighborhood = entry.value.location.neighborhood || ''
    }
}

// Handle upload completions
const handleLogoUpload = (uploadResult) => {
    logoImages.value.push(uploadResult)
    form.logo_url = uploadResult.url
}

const handleCoverUpload = (uploadResult) => {
    coverImages.value.push(uploadResult)
    form.cover_image_url = uploadResult.url
}

const handleGalleryUpload = (uploadResult) => {
    galleryImages.value.push(uploadResult)
    form.gallery_images.push(uploadResult.url)
}

const handleNewGalleryRemove = (index, removedImage) => {
    const urlIndex = form.gallery_images.indexOf(removedImage.url)
    if (urlIndex > -1) {
        form.gallery_images.splice(urlIndex, 1)
    }
}

const updateNewGalleryOrder = (reorderedImages) => {
    const newUrls = reorderedImages.map(img => img.url)
    const existingUrls = existingGalleryImages.value.map(img => img.url)
    form.gallery_images = [...existingUrls, ...newUrls]
}

const handleExistingGalleryRemove = (index, removedImage) => {
    const urlIndex = form.gallery_images.indexOf(removedImage.url)
    if (urlIndex > -1) {
        form.gallery_images.splice(urlIndex, 1)
    }
}

const updateExistingGalleryOrder = (reorderedImages) => {
    const existingUrls = reorderedImages.map(img => img.url)
    const newUrls = galleryImages.value.map(img => img.url)
    form.gallery_images = [...existingUrls, ...newUrls]
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
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
        const response = await axios.put(`/api/places/${entry.value.id}`, form)
        
        if (response.data) {
            showSuccessMessage.value = true
            
            // Hide success message after 5 seconds
            setTimeout(() => {
                showSuccessMessage.value = false
            }, 5000)
        }
    } catch (error) {
        console.error('Form submission error:', error)
        
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        } else if (error.response?.status === 401) {
            router.push('/login')
        } else {
            alert('Error updating entry: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

// Watch for route changes
watch(() => route.params.id, (newId) => {
    if (newId) {
        fetchEntryData()
    }
})

// Initialize
onMounted(() => {
    fetchEntryData()
})
</script>