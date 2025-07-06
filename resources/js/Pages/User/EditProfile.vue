<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Button from '@/Components/UI/Button.vue'
import ImageUpload from '@/Components/UI/ImageUpload.vue'
import CloudflareImageUpload from '@/Components/ImageUpload/CloudflareImageUpload.vue'
import DirectCloudflareUpload from '@/Components/ImageUpload/DirectCloudflareUpload.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    user: Object
})

const siteOrigin = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin
    }
    return ''
})

const form = useForm({
    name: props.user.name || '',
    page_title: props.user.page_title || '',
    display_title: props.user.display_title || '',
    bio: props.user.bio || '',
    profile_description: props.user.profile_description || '',
    location: props.user.location || '',
    website: props.user.website || '',
    birth_date: props.user.birth_date || '',
    custom_url: props.user.custom_url || '',
    custom_css: props.user.custom_css || '',
    profile_color: props.user.profile_color || '#3B82F6',
    show_activity: props.user.show_activity ?? true,
    show_followers: props.user.show_followers ?? true,
    show_following: props.user.show_following ?? true,
    show_join_date: props.user.show_join_date ?? true,
    show_location: props.user.show_location ?? true,
    show_website: props.user.show_website ?? true,
    is_public: props.user.is_public ?? true,
    social_links: {
        twitter: props.user.social_links?.twitter || '',
        instagram: props.user.social_links?.instagram || '',
        github: props.user.social_links?.github || ''
    },
    avatar: null,
    cover_image: null,
    page_logo: null,
    avatar_cloudflare_id: props.user.avatar_cloudflare_id || null,
    cover_cloudflare_id: props.user.cover_cloudflare_id || null,
    page_logo_cloudflare_id: props.user.page_logo_cloudflare_id || null,
    page_logo_option: props.user.page_logo_option || 'initials',
    remove_avatar: false,
    remove_cover: false,
    remove_page_logo: false
})

const showSuccessMessage = ref(false)
const successMessage = ref('')

const submit = () => {
    // For file uploads, Inertia needs to use POST with _method=PATCH
    form.transform((data) => ({
        ...data,
        _method: 'PATCH'
    })).post('/profile', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            // Show success message
            showSuccessMessage.value = true
            successMessage.value = 'Profile updated successfully!'
            setTimeout(() => {
                showSuccessMessage.value = false
            }, 5000)
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors)
        }
    })
}

const openPreview = () => {
    const profileUrl = `/${form.custom_url || props.user.username}`
    window.open(profileUrl, '_blank')
}

// Handle Cloudflare image uploads
const handleAvatarUpload = (image) => {
    console.log('Avatar uploaded:', image)
    form.avatar_cloudflare_id = image.cloudflare_id
    form.remove_avatar = false
}

const handleCoverUpload = (image) => {
    console.log('Cover uploaded:', image)
    form.cover_cloudflare_id = image.cloudflare_id
    form.remove_cover = false
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Failed to upload image: ' + error.message)
}

const handleAvatarRemove = () => {
    form.avatar_cloudflare_id = null
    form.remove_avatar = true
}

const handleCoverRemove = () => {
    form.cover_cloudflare_id = null
    form.remove_cover = true
}

// Handle page logo upload
const handlePageLogoUpload = (image) => {
    console.log('Page logo uploaded:', image)
    form.page_logo_cloudflare_id = image.cloudflare_id
    form.remove_page_logo = false
}

const handlePageLogoRemove = () => {
    form.page_logo_cloudflare_id = null
    form.remove_page_logo = true
}

// Page logo functionality - use the stored option from the user
const pageLogoOption = ref(props.user.page_logo_option || 'initials')

const pageInitials = computed(() => {
    if (form.page_title) {
        return form.page_title.split(' ')
            .map(word => word.charAt(0))
            .join('')
            .substring(0, 2)
            .toUpperCase()
    }
    return form.name.split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
})

const pageLogoPreview = computed(() => {
    if (pageLogoOption.value === 'profile') {
        // Use the user's profile avatar
        return props.user.avatar_url
    } else if (pageLogoOption.value === 'custom' && form.page_logo_cloudflare_id) {
        // Use the custom uploaded logo
        const imageService = 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A'
        return `${imageService}/${form.page_logo_cloudflare_id}/public`
    } else if (pageLogoOption.value === 'custom' && props.user.page_logo_url) {
        return props.user.page_logo_url
    }
    return null
})

// Watch for page logo option changes to update form data
watch(pageLogoOption, (newValue) => {
    form.page_logo_option = newValue
    
    if (newValue === 'initials') {
        form.page_logo_cloudflare_id = null
        form.remove_page_logo = true
    } else if (newValue === 'profile') {
        // Clear custom logo and let backend handle using profile avatar
        form.page_logo_cloudflare_id = null  
        form.remove_page_logo = false
    }
    // For 'custom', the upload handler will set page_logo_cloudflare_id
})
</script>

<template>
    <Head title="Profile Edit Page" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Profile Edit Page
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        
                        <!-- Page Images -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Page Images</h3>
                            
                            <!-- Cover Image (First) -->
                            <div>
                                <DirectCloudflareUpload
                                    label="Cover Image"
                                    upload-type="cover"
                                    :entity-id="user.id"
                                    :current-image-url="user.cover_image_url"
                                    :max-size-m-b="14"
                                    @upload-complete="handleCoverUpload"
                                    @upload-error="handleUploadError"
                                    @remove="handleCoverRemove"
                                />
                                <p v-if="form.errors.cover_cloudflare_id" class="mt-2 text-sm text-red-600">{{ form.errors.cover_cloudflare_id }}</p>
                                <p v-if="form.errors.cover_image" class="mt-2 text-sm text-red-600">{{ form.errors.cover_image }}</p>
                            </div>
                            
                            <!-- Page Logo Options -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Page Logo</label>
                                <div class="space-y-4">
                                    <!-- Option Selection -->
                                    <div class="flex space-x-4">
                                        <label class="flex items-center">
                                            <input 
                                                type="radio" 
                                                v-model="pageLogoOption" 
                                                value="profile" 
                                                class="form-radio"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Use Profile Image</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input 
                                                type="radio" 
                                                v-model="pageLogoOption" 
                                                value="custom" 
                                                class="form-radio"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Add Page Logo</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input 
                                                type="radio" 
                                                v-model="pageLogoOption" 
                                                value="initials" 
                                                class="form-radio"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Use Page Initials</span>
                                        </label>
                                    </div>
                                    
                                    <!-- Custom Logo Upload (only show if custom selected) -->
                                    <div v-if="pageLogoOption === 'custom'">
                                        <DirectCloudflareUpload
                                            label="Upload Page Logo"
                                            upload-type="page_logo"
                                            :entity-id="user.id"
                                            :current-image-url="user.page_logo_url"
                                            :max-size-m-b="14"
                                            @upload-complete="handlePageLogoUpload"
                                            @upload-error="handleUploadError"
                                            @remove="handlePageLogoRemove"
                                        />
                                    </div>
                                    
                                    <!-- Preview -->
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm text-gray-600">Preview:</div>
                                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                            <img v-if="pageLogoPreview" :src="pageLogoPreview" class="w-full h-full object-cover" />
                                            <span v-else class="text-lg font-medium text-gray-600">{{ pageInitials }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.page_logo_cloudflare_id" class="mt-2 text-sm text-red-600">{{ form.errors.page_logo_cloudflare_id }}</p>
                            </div>
                            
                            <!-- Profile Avatar (Separate from Page Logo) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Profile Avatar</label>
                                <p class="text-sm text-gray-600 mb-3">This is your personal avatar that appears in comments, activity feeds, and user lists.</p>
                                <DirectCloudflareUpload
                                    label="Upload Profile Avatar"
                                    upload-type="avatar"
                                    :entity-id="user.id"
                                    :current-image-url="user.avatar_url"
                                    :max-size-m-b="14"
                                    @upload-complete="handleAvatarUpload"
                                    @upload-error="handleUploadError"
                                    @remove="handleAvatarRemove"
                                />
                                <p v-if="form.errors.avatar_cloudflare_id" class="mt-2 text-sm text-red-600">{{ form.errors.avatar_cloudflare_id }}</p>
                                <p v-if="form.errors.avatar" class="mt-2 text-sm text-red-600">{{ form.errors.avatar }}</p>
                            </div>
                        </div>
                        
                        <!-- Basic Information -->
                        <div class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                            
                            <div>
                                <label for="page_title" class="block text-sm font-medium text-gray-700">Page Title</label>
                                <input 
                                    id="page_title"
                                    v-model="form.page_title"
                                    type="text" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.page_title }"
                                    placeholder="Your main page heading (will be displayed as the primary title)"
                                />
                                <p class="mt-1 text-sm text-gray-500">This will be the main heading on your profile page</p>
                                <p v-if="form.errors.page_title" class="mt-2 text-sm text-red-600">{{ form.errors.page_title }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input 
                                        id="name"
                                        v-model="form.name"
                                        type="text" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.name }"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label for="display_title" class="block text-sm font-medium text-gray-700">Display Title</label>
                                    <input 
                                        id="display_title"
                                        v-model="form.display_title"
                                        type="text" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.display_title }"
                                        placeholder="Professional title or subtitle"
                                    />
                                    <p class="mt-1 text-sm text-gray-500">Displayed below your page title</p>
                                    <p v-if="form.errors.display_title" class="mt-2 text-sm text-red-600">{{ form.errors.display_title }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700">Short Bio</label>
                                <RichTextEditor
                                    v-model="form.bio"
                                    placeholder="A brief description about yourself..."
                                    :max-length="300"
                                    :show-character-count="true"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.bio" class="mt-2 text-sm text-red-600">{{ form.errors.bio }}</p>
                            </div>

                            <div>
                                <label for="profile_description" class="block text-sm font-medium text-gray-700">Extended Description</label>
                                <RichTextEditor
                                    v-model="form.profile_description"
                                    placeholder="Tell visitors more about yourself, your interests, or what you do..."
                                    :max-length="750"
                                    :show-character-count="true"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.profile_description" class="mt-2 text-sm text-red-600">{{ form.errors.profile_description }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                                    <input 
                                        id="location"
                                        v-model="form.location"
                                        type="text" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.location }"
                                        placeholder="City, Country"
                                    />
                                    <p v-if="form.errors.location" class="mt-2 text-sm text-red-600">{{ form.errors.location }}</p>
                                </div>

                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input 
                                        id="website"
                                        v-model="form.website"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.website }"
                                        placeholder="https://your-website.com"
                                    />
                                    <p v-if="form.errors.website" class="mt-2 text-sm text-red-600">{{ form.errors.website }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="custom_url" class="block text-sm font-medium text-gray-700">Custom URL</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        {{ siteOrigin }}/
                                    </span>
                                    <input 
                                        id="custom_url"
                                        v-model="form.custom_url"
                                        type="text" 
                                        class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.custom_url }"
                                        placeholder="your-username"
                                        pattern="[a-zA-Z0-9_-]+"
                                    />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Only letters, numbers, dashes, and underscores allowed</p>
                                <p v-if="form.errors.custom_url" class="mt-2 text-sm text-red-600">{{ form.errors.custom_url }}</p>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Social Links</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="twitter" class="block text-sm font-medium text-gray-700">X (Twitter)</label>
                                    <input 
                                        id="twitter"
                                        v-model="form.social_links.twitter"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://x.com/username"
                                    />
                                </div>

                                <div>
                                    <label for="instagram" class="block text-sm font-medium text-gray-700">Instagram</label>
                                    <input 
                                        id="instagram"
                                        v-model="form.social_links.instagram"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://instagram.com/username"
                                    />
                                </div>

                                <div>
                                    <label for="github" class="block text-sm font-medium text-gray-700">GitHub</label>
                                    <input 
                                        id="github"
                                        v-model="form.social_links.github"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://github.com/username"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Settings -->
                        <div class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Privacy Settings</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="is_public"
                                            v-model="form.is_public"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="is_public" class="font-medium text-gray-700">Public Profile</label>
                                        <p class="text-gray-500">Allow anyone to view your profile and public lists</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_activity"
                                            v-model="form.show_activity"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_activity" class="font-medium text-gray-700">Show Activity</label>
                                        <p class="text-gray-500">Display your recent activity on your profile</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_followers"
                                            v-model="form.show_followers"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_followers" class="font-medium text-gray-700">Show Followers</label>
                                        <p class="text-gray-500">Allow others to see who follows you</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_following"
                                            v-model="form.show_following"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_following" class="font-medium text-gray-700">Show Following</label>
                                        <p class="text-gray-500">Allow others to see who you follow</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_join_date"
                                            v-model="form.show_join_date"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_join_date" class="font-medium text-gray-700">Show Join Date</label>
                                        <p class="text-gray-500">Display when you joined the platform</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_location"
                                            v-model="form.show_location"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_location" class="font-medium text-gray-700">Show Location</label>
                                        <p class="text-gray-500">Display your location on your profile</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="show_website"
                                            v-model="form.show_website"
                                            type="checkbox" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="show_website" class="font-medium text-gray-700">Show Website</label>
                                        <p class="text-gray-500">Display your website link on your profile</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Success Message -->
                        <div v-if="showSuccessMessage" class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ successMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-between border-t pt-6">
                            <Button 
                                @click="openPreview"
                                variant="outline"
                                type="button"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Preview Profile
                            </Button>
                            
                            <div class="flex items-center space-x-4">
                                <Button 
                                    href="/dashboard"
                                    variant="outline"
                                >
                                    Cancel
                                </Button>
                                <Button 
                                    type="submit"
                                    :disabled="form.processing"
                                    variant="primary"
                                >
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>