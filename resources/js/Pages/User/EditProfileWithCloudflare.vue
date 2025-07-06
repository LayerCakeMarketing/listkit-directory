<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Button from '@/Components/UI/Button.vue'
import CloudflareImageUpload from '@/Components/ImageUpload/CloudflareImageUpload.vue'
import { ref, computed } from 'vue'

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
        linkedin: props.user.social_links?.linkedin || '',
        github: props.user.social_links?.github || ''
    },
    avatar_cloudflare_id: props.user.avatar_cloudflare_id || null,
    cover_cloudflare_id: props.user.cover_cloudflare_id || null
})

// Track uploaded images
const avatarImage = ref(null)
const coverImage = ref(null)

const handleAvatarUpload = (image) => {
    console.log('Avatar uploaded:', image)
    form.avatar_cloudflare_id = image.cloudflare_id
    avatarImage.value = image
}

const handleCoverUpload = (image) => {
    console.log('Cover uploaded:', image)
    form.cover_cloudflare_id = image.cloudflare_id
    coverImage.value = image
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Failed to upload image: ' + error.message)
}

const handleAvatarRemove = () => {
    form.avatar_cloudflare_id = null
    avatarImage.value = null
}

const handleCoverRemove = () => {
    form.cover_cloudflare_id = null
    coverImage.value = null
}

const submit = () => {
    form.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
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
</script>

<template>
    <Head title="Edit Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        
                        <!-- Profile Images with Cloudflare -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Profile Images</h3>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Profile Picture -->
                                <div>
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
                                    <p v-if="form.errors.avatar_cloudflare_id" class="mt-2 text-sm text-red-600">{{ form.errors.avatar_cloudflare_id }}</p>
                                </div>

                                <!-- Cover Image -->
                                <div>
                                    <CloudflareImageUpload
                                        v-model="coverImage"
                                        label="Cover Image"
                                        upload-type="cover"
                                        :entity-id="user.id"
                                        :current-image-url="user.cover_image_url"
                                        :max-size="10"
                                        :async-threshold="5"
                                        @upload-complete="handleCoverUpload"
                                        @upload-error="handleUploadError"
                                        @remove="handleCoverRemove"
                                    />
                                    <p v-if="form.errors.cover_cloudflare_id" class="mt-2 text-sm text-red-600">{{ form.errors.cover_cloudflare_id }}</p>
                                </div>
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
                                <textarea 
                                    id="bio"
                                    v-model="form.bio"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.bio }"
                                    placeholder="A brief description about yourself..."
                                    maxlength="500"
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ (form.bio || '').length }}/500 characters</p>
                                <p v-if="form.errors.bio" class="mt-2 text-sm text-red-600">{{ form.errors.bio }}</p>
                            </div>

                            <div>
                                <label for="profile_description" class="block text-sm font-medium text-gray-700">Extended Description</label>
                                <textarea 
                                    id="profile_description"
                                    v-model="form.profile_description"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.profile_description }"
                                    placeholder="Tell visitors more about yourself, your interests, or what you do..."
                                    maxlength="1000"
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ (form.profile_description || '').length }}/1000 characters</p>
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
                                    <label for="twitter" class="block text-sm font-medium text-gray-700">Twitter</label>
                                    <input 
                                        id="twitter"
                                        v-model="form.social_links.twitter"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://twitter.com/username"
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
                                    <label for="linkedin" class="block text-sm font-medium text-gray-700">LinkedIn</label>
                                    <input 
                                        id="linkedin"
                                        v-model="form.social_links.linkedin"
                                        type="url" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://linkedin.com/in/username"
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

                        <!-- Customization -->
                        <div class="space-y-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Profile Customization</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="profile_color" class="block text-sm font-medium text-gray-700">Profile Color</label>
                                    <div class="mt-1 flex items-center space-x-3">
                                        <input 
                                            id="profile_color"
                                            v-model="form.profile_color"
                                            type="color" 
                                            class="h-10 w-16 border border-gray-300 rounded-md cursor-pointer"
                                        />
                                        <input 
                                            v-model="form.profile_color"
                                            type="text" 
                                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            :class="{ 'border-red-500': form.errors.profile_color }"
                                            placeholder="#3B82F6"
                                        />
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Choose a color theme for your profile</p>
                                    <p v-if="form.errors.profile_color" class="mt-2 text-sm text-red-600">{{ form.errors.profile_color }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="custom_css" class="block text-sm font-medium text-gray-700">Custom CSS</label>
                                <textarea 
                                    id="custom_css"
                                    v-model="form.custom_css"
                                    rows="6"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                    :class="{ 'border-red-500': form.errors.custom_css }"
                                    placeholder="/* Add custom CSS to style your profile */
.profile-container {
    /* Your custom styles here */
}"
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ (form.custom_css || '').length }}/5000 characters - Add custom CSS to personalize your profile appearance</p>
                                <p v-if="form.errors.custom_css" class="mt-2 text-sm text-red-600">{{ form.errors.custom_css }}</p>
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