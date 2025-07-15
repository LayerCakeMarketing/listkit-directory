<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Home Page Settings</h2>
                <p class="mt-1 text-sm text-gray-600">Customize the homepage content and layout</p>
            </div>

            <!-- Settings Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form @submit.prevent="saveSettings" class="p-6 space-y-6">
                    <!-- Hero Section -->
                    <div class="border-b pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Hero Section</h3>
                        
                        <!-- Hero Image -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hero Background Image</label>
                            <div class="flex items-start space-x-4">
                                <div v-if="settings.hero_image_url" class="relative">
                                    <img 
                                        :src="settings.hero_image_url" 
                                        alt="Hero Background" 
                                        class="w-48 h-32 object-cover rounded-lg"
                                    />
                                    <button
                                        type="button"
                                        @click="removeHeroImage"
                                        class="absolute top-2 right-2 bg-red-600 text-white p-1 rounded-full hover:bg-red-700"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div>
                                    <input
                                        type="file"
                                        @change="handleHeroImageUpload"
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Recommended size: 1920x800px, Max size: 5MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Title -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hero Title</label>
                            <input
                                v-model="form.hero_title"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Welcome to Our Directory"
                            />
                        </div>

                        <!-- Hero Subtitle -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hero Subtitle</label>
                            <input
                                v-model="form.hero_subtitle"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Find the best places in your area"
                            />
                        </div>

                        <!-- CTA Button -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button Text</label>
                                <input
                                    v-model="form.cta_text"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Get Started"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CTA Button Link</label>
                                <input
                                    v-model="form.cta_link"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="/register"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Featured Places -->
                    <div class="border-b pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Featured Places</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Featured Places (Max 5)</label>
                            <PlaceSelector
                                v-model="form.featured_places"
                                :max="5"
                                :selected-places="settings.featured_places_data"
                            />
                        </div>
                    </div>

                    <!-- Testimonials -->
                    <div class="border-b pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Testimonials</h3>
                        <TestimonialsManager v-model="form.testimonials" />
                    </div>

                    <!-- Custom Scripts -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custom Scripts</label>
                        <textarea
                            v-model="form.custom_scripts"
                            rows="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                            placeholder="<!-- Google Analytics, etc. -->"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Add custom JavaScript or tracking codes (will be placed before closing body tag)</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import PlaceSelector from './components/PlaceSelector.vue'
import TestimonialsManager from './components/TestimonialsManager.vue'

// State
const loading = ref(false)
const processing = ref(false)
const settings = ref({})
const heroImageFile = ref(null)

// Form
const form = reactive({
    hero_title: '',
    hero_subtitle: '',
    cta_text: '',
    cta_link: '',
    featured_places: [],
    testimonials: [],
    custom_scripts: '',
    remove_hero_image: false
})

// Methods
const fetchSettings = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/marketing-pages/special/home')
        settings.value = response.data.data
        
        // Update form with settings
        form.hero_title = settings.value.hero_title || ''
        form.hero_subtitle = settings.value.hero_subtitle || ''
        form.cta_text = settings.value.cta_text || ''
        form.cta_link = settings.value.cta_link || ''
        form.featured_places = settings.value.featured_places || []
        form.testimonials = settings.value.testimonials || []
        form.custom_scripts = settings.value.custom_scripts || ''
    } catch (error) {
        console.error('Error fetching settings:', error)
    } finally {
        loading.value = false
    }
}

const handleHeroImageUpload = (event) => {
    heroImageFile.value = event.target.files[0]
}

const removeHeroImage = () => {
    form.remove_hero_image = true
    settings.value.hero_image_url = null
}

const saveSettings = async () => {
    processing.value = true

    const formData = new FormData()
    formData.append('hero_title', form.hero_title)
    formData.append('hero_subtitle', form.hero_subtitle)
    formData.append('cta_text', form.cta_text)
    formData.append('cta_link', form.cta_link)
    formData.append('custom_scripts', form.custom_scripts)
    
    // Add featured places
    form.featured_places.forEach((placeId, index) => {
        formData.append(`featured_places[${index}]`, placeId)
    })
    
    // Add testimonials
    form.testimonials.forEach((testimonial, index) => {
        formData.append(`testimonials[${index}][quote]`, testimonial.quote)
        formData.append(`testimonials[${index}][author]`, testimonial.author)
        formData.append(`testimonials[${index}][company]`, testimonial.company || '')
    })
    
    if (heroImageFile.value) {
        formData.append('hero_image', heroImageFile.value)
    }
    
    if (form.remove_hero_image) {
        formData.append('remove_hero_image', '1')
    }

    try {
        const response = await axios.post('/api/admin/marketing-pages/special/home', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        
        settings.value = response.data.data
        form.remove_hero_image = false
        heroImageFile.value = null
        
        alert('Settings saved successfully!')
    } catch (error) {
        console.error('Error saving settings:', error)
        alert('Error saving settings')
    } finally {
        processing.value = false
    }
}

const resetForm = () => {
    fetchSettings()
    heroImageFile.value = null
    form.remove_hero_image = false
}

// Initialize
onMounted(() => {
    fetchSettings()
})
</script>