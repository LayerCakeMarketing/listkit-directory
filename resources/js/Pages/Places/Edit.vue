<template>
    <Head :title="`Edit ${entry.title}`" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <Link href="/" class="text-2xl font-bold text-gray-900 hover:text-blue-600">
                            ListKit Directory
                        </Link>
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <Link
                            href="/places"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            Browse Places
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

        <!-- Breadcrumb -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-3 text-sm">
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
                    <Link :href="getEntryViewUrl()" class="text-blue-600 hover:text-blue-800 font-medium">{{ entry.title }}</Link>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">Edit</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Directory Entry</h1>
                            <p class="text-gray-600 mt-1">Update information for {{ entry.title }}</p>
                        </div>
                        <Link
                            :href="getEntryViewUrl()"
                            target="_blank"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </Link>
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
                                Logo (400x400px max, 2MB)
                                <span v-if="form.logo_url" class="text-green-600 font-normal"> - Image uploaded ✓</span>
                            </label>
                            <ImageUpload
                                type="logo"
                                :current-image="form.logo_url"
                                @uploaded="handleImageUpload"
                                @removed="handleImageRemove"
                            />
                        </div>

                        <!-- Cover Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Image (1920x1080px max, 5MB)
                                <span v-if="form.cover_image_url" class="text-green-600 font-normal"> - Image uploaded ✓</span>
                            </label>
                            <ImageUpload
                                type="cover"
                                :current-image="form.cover_image_url"
                                @uploaded="handleImageUpload"
                                @removed="handleImageRemove"
                            />
                        </div>

                        <!-- Gallery Images -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Gallery Images (1200x800px max, 3MB each)
                                <span v-if="form.gallery_images.filter(img => img).length > 0" class="text-green-600 font-normal"> 
                                    - {{ form.gallery_images.filter(img => img).length }} image(s) uploaded ✓
                                </span>
                            </label>
                            <div class="space-y-2">
                                <ImageUpload
                                    v-for="(image, index) in form.gallery_images"
                                    :key="`gallery-${index}`"
                                    type="gallery"
                                    :current-image="image"
                                    @uploaded="(data) => handleGalleryUpload(data, index)"
                                    @removed="() => handleGalleryRemove(index)"
                                />
                                <button
                                    v-if="form.gallery_images.length < 10"
                                    type="button"
                                    @click="addGallerySlot"
                                    class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    + Add Gallery Image
                                </button>
                            </div>
                        </div>
                        </div>
                    </AccordionSection>

                    <!-- Location/Address Section -->
                    <AccordionSection 
                        title="Address / Location" 
                        v-if="form.type === 'physical_location' || form.type === 'event'"
                    >
                        <div class="space-y-4">
                        
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
                                <input
                                    v-model="form.location.state"
                                    type="text"
                                    id="state"
                                    maxlength="2"
                                    :required="form.type === 'physical_location' || form.type === 'event'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
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
                    </AccordionSection>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <Link
                            :href="$page.props.auth.user?.role === 'admin' || $page.props.auth.user?.role === 'manager' ? '/admin/entries' : entry.url"
                            class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </Link>
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
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, reactive, onMounted } from 'vue'
import ImageUpload from '@/Components/ImageUpload.vue'
import AccordionSection from '@/Components/AccordionSection.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
    entry: Object,
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
    
    // Social Media
    facebook_url: '',
    instagram_handle: '',
    twitter_handle: '',
    youtube_channel: '',
    messenger_contact: '',
    
    // Business Metadata
    price_range: '',
    takes_reservations: null,
    accepts_credit_cards: null,
    wifi_available: null,
    pet_friendly: null,
    parking_options: '',
    wheelchair_accessible: null,
    outdoor_seating: null,
    kid_friendly: null,
    
    // Media
    video_urls: [],
    pdf_files: [],
    
    // Operational Info
    hours_of_operation: {},
    special_hours: [],
    temporarily_closed: false,
    open_24_7: false,
    
    // Location metadata
    cross_streets: '',
    neighborhood: '',
    
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

// Get URL for viewing the entry
const getEntryViewUrl = () => {
    // Use the entry's URL if available, or construct from category and slug
    if (props.entry.url) {
        return props.entry.url
    }
    
    // Fallback: construct URL based on category hierarchy
    if (props.entry.category && props.entry.category.parent_id) {
        // Get parent category from the categories list
        const parentCategory = props.categories.find(cat => cat.id === props.entry.category.parent_id)?.parent
        if (parentCategory && props.entry.category) {
            return `/${parentCategory.slug}/${props.entry.category.slug}/${props.entry.slug}`
        }
    }
    
    return `/places/entry/${props.entry.slug}`
}

// Initialize form with existing entry data
onMounted(() => {
    form.title = props.entry.title || ''
    form.description = props.entry.description || ''
    form.type = props.entry.type || ''
    form.category_id = props.entry.category_id || ''
    form.email = props.entry.email || ''
    form.phone = props.entry.phone || ''
    form.website_url = props.entry.website_url || ''
    form.logo_url = props.entry.logo_url || ''
    form.cover_image_url = props.entry.cover_image_url || ''
    form.gallery_images = props.entry.gallery_images || []
    
    // Social Media
    form.facebook_url = props.entry.facebook_url || ''
    form.instagram_handle = props.entry.instagram_handle || ''
    form.twitter_handle = props.entry.twitter_handle || ''
    form.youtube_channel = props.entry.youtube_channel || ''
    form.messenger_contact = props.entry.messenger_contact || ''
    
    // Business Metadata
    form.price_range = props.entry.price_range || ''
    form.takes_reservations = props.entry.takes_reservations
    form.accepts_credit_cards = props.entry.accepts_credit_cards
    form.wifi_available = props.entry.wifi_available
    form.pet_friendly = props.entry.pet_friendly
    form.parking_options = props.entry.parking_options || ''
    form.wheelchair_accessible = props.entry.wheelchair_accessible
    form.outdoor_seating = props.entry.outdoor_seating
    form.kid_friendly = props.entry.kid_friendly
    
    // Media
    form.video_urls = props.entry.video_urls || []
    form.pdf_files = props.entry.pdf_files || []
    
    // Operational Info
    form.hours_of_operation = props.entry.hours_of_operation || {}
    form.special_hours = props.entry.special_hours || []
    form.temporarily_closed = props.entry.temporarily_closed || false
    form.open_24_7 = props.entry.open_24_7 || false
    
    // Location metadata
    form.cross_streets = props.entry.cross_streets || ''
    form.neighborhood = props.entry.neighborhood || ''
    
    if (props.entry.location) {
        form.location.address_line1 = props.entry.location.address_line1 || ''
        form.location.address_line2 = props.entry.location.address_line2 || ''
        form.location.city = props.entry.location.city || ''
        form.location.state = props.entry.location.state || ''
        form.location.zip_code = props.entry.location.zip_code || ''
        form.location.country = props.entry.location.country || 'US'
        form.location.latitude = props.entry.location.latitude || ''
        form.location.longitude = props.entry.location.longitude || ''
        form.location.cross_streets = props.entry.location.cross_streets || ''
        form.location.neighborhood = props.entry.location.neighborhood || ''
    }
})

const handleImageUpload = (data) => {
    console.log('Image upload received:', data)
    if (data.type === 'logo') {
        form.logo_url = data.url
        console.log('Updated logo_url to:', form.logo_url)
    } else if (data.type === 'cover') {
        form.cover_image_url = data.url
        console.log('Updated cover_image_url to:', form.cover_image_url)
    }
}

const handleImageRemove = (type) => {
    if (type === 'logo') {
        form.logo_url = ''
    } else if (type === 'cover') {
        form.cover_image_url = ''
    }
}

const handleGalleryUpload = (data, index) => {
    form.gallery_images[index] = data.url
}

const handleGalleryRemove = (index) => {
    form.gallery_images.splice(index, 1)
}

const addGallerySlot = () => {
    form.gallery_images.push('')
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
    
    console.log('Submitting form data:', form)
    
    try {
        console.log('Making PUT request to:', `/api/entries/${props.entry.id}`)
        const response = await window.axios.put(`/api/entries/${props.entry.id}`, form)
        
        console.log('API Response:', response.data)
        
        if (response.data) {
            // Success - show confirmation message and stay on page
            showSuccessMessage.value = true
            console.log('Entry updated successfully')
            
            // Hide success message after 5 seconds
            setTimeout(() => {
                showSuccessMessage.value = false
            }, 5000)
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
            alert('Error updating entry: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}
</script>