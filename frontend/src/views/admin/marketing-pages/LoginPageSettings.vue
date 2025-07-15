<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Login Page Settings</h2>
                <p class="mt-1 text-sm text-gray-600">Customize the appearance and behavior of the login page</p>
            </div>

            <!-- Settings Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form @submit.prevent="saveSettings" class="p-6 space-y-6">
                    <!-- Background Image -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>
                        <div class="flex items-start space-x-4">
                            <div v-if="settings.background_image_url" class="relative">
                                <img 
                                    :src="settings.background_image_url" 
                                    alt="Background" 
                                    class="w-48 h-32 object-cover rounded-lg"
                                />
                                <button
                                    type="button"
                                    @click="removeBackgroundImage"
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
                                    @change="handleImageUpload"
                                    accept="image/*"
                                    class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                />
                                <p class="mt-1 text-xs text-gray-500">Recommended size: 1920x1080px, Max size: 5MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Welcome Message</label>
                        <textarea
                            v-model="form.welcome_message"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Welcome back! Please sign in to continue."
                        ></textarea>
                    </div>

                    <!-- Social Login -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Social Login Options</label>
                        <div class="flex items-center">
                            <input
                                v-model="form.social_login_enabled"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <label class="ml-2 text-sm text-gray-700">Enable social login buttons</label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Show login options for Google, Facebook, etc.</p>
                    </div>

                    <!-- Custom CSS -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custom CSS</label>
                        <textarea
                            v-model="form.custom_css"
                            rows="10"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                            placeholder=".login-container {&#10;  background-color: #f5f5f5;&#10;}"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Add custom CSS to override default styles</p>
                    </div>

                    <!-- Preview -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <p class="text-sm text-gray-600 text-center">
                                Live preview will be shown here in a future update
                            </p>
                        </div>
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

// State
const loading = ref(false)
const processing = ref(false)
const settings = ref({})
const backgroundImageFile = ref(null)

// Form
const form = reactive({
    welcome_message: '',
    custom_css: '',
    social_login_enabled: true,
    remove_background_image: false
})

// Methods
const fetchSettings = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/marketing-pages/special/login')
        settings.value = response.data.data
        
        // Update form with settings
        form.welcome_message = settings.value.welcome_message || ''
        form.custom_css = settings.value.custom_css || ''
        form.social_login_enabled = settings.value.social_login_enabled !== false
    } catch (error) {
        console.error('Error fetching settings:', error)
    } finally {
        loading.value = false
    }
}

const handleImageUpload = (event) => {
    backgroundImageFile.value = event.target.files[0]
}

const removeBackgroundImage = () => {
    form.remove_background_image = true
    settings.value.background_image_url = null
}

const saveSettings = async () => {
    processing.value = true

    const formData = new FormData()
    formData.append('welcome_message', form.welcome_message)
    formData.append('custom_css', form.custom_css)
    formData.append('social_login_enabled', form.social_login_enabled ? '1' : '0')
    
    if (backgroundImageFile.value) {
        formData.append('background_image', backgroundImageFile.value)
    }
    
    if (form.remove_background_image) {
        formData.append('remove_background_image', '1')
    }

    try {
        const response = await axios.post('/api/admin/marketing-pages/special/login', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        
        settings.value = response.data.data
        form.remove_background_image = false
        backgroundImageFile.value = null
        
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
    backgroundImageFile.value = null
    form.remove_background_image = false
}

// Initialize
onMounted(() => {
    fetchSettings()
})
</script>