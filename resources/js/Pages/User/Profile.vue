<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Logo from '@/Components/Logo.vue'
import Button from '@/Components/UI/Button.vue'
import PageLogo from '@/Components/UI/PageLogo.vue'
import UserProfileDropdown from '@/Components/UserProfileDropdown.vue'

const props = defineProps({
    profile_user: Object,
    current_user: Object
})

const page = usePage()
const showSuccessMessage = ref(false)
const successMessage = ref('')

// Check for flash success message and inject custom CSS
onMounted(() => {
    if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        showSuccessMessage.value = true
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            showSuccessMessage.value = false
        }, 5000)
    }
    
    // Inject custom CSS if present
    if (user.value.custom_css) {
        const styleElement = document.createElement('style')
        styleElement.id = 'profile-custom-css'
        styleElement.textContent = user.value.custom_css
        document.head.appendChild(styleElement)
    }
})

// Cleanup custom CSS on unmount
onUnmounted(() => {
    const existingStyle = document.getElementById('profile-custom-css')
    if (existingStyle) {
        existingStyle.remove()
    }
})

const user = computed(() => props.profile_user)
const currentUser = computed(() => props.current_user)
const isOwnProfile = computed(() => currentUser.value && currentUser.value.id === user.value.id)

const activeTab = ref('lists')
const isFollowing = ref(user.value.permissions.is_following)
const isProcessing = ref(false)

const handleFollow = async () => {
    if (isProcessing.value) return
    
    isProcessing.value = true
    const action = isFollowing.value ? 'unfollow' : 'follow'
    
    try {
        const response = await fetch(`/api/users/${user.value.custom_url || user.value.username}/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            isFollowing.value = data.is_following
            // Update follower count
            if (data.is_following) {
                user.value.stats.followers_count++
            } else {
                user.value.stats.followers_count--
            }
        }
    } catch (error) {
        console.error('Error following/unfollowing user:', error)
    } finally {
        isProcessing.value = false
    }
}

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num.toString()
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long' 
    })
}

const getActivityIcon = (type) => {
    const icons = {
        'created_list': '📝',
        'followed_user': '👤',
        'followed_entry': '📍',
        'updated_profile': '✏️',
        'pinned_list': '📌'
    }
    return icons[type] || '📋'
}
</script>

<template>
    <Head :title="user.page_title || user.display_title || user.name" />

    <div class="min-h-screen bg-gray-50 profile-container" :style="user.profile_color ? `--profile-color: ${user.profile_color}` : ''">
        <!-- Success Notification -->
        <div v-if="showSuccessMessage" class="fixed top-4 right-4 z-50">
            <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ successMessage }}</span>
                <button @click="showSuccessMessage = false" class="ml-3 text-green-200 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <Logo href="/" imgClassName="h-8 w-auto" />
                    
                    <nav class="flex items-center space-x-4">
                        <Link href="/places" class="text-gray-600 hover:text-gray-900">
                            Browse Places
                        </Link>
                        <template v-if="currentUser">
                            <Link href="/dashboard" class="text-gray-600 hover:text-gray-900">
                                Dashboard
                            </Link>
                            <UserProfileDropdown />
                        </template>
                        <Link v-else href="/login" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Sign In
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Cover Photo -->
        <div class="relative h-80 bg-gradient-to-r from-blue-500 to-purple-600">
            <img 
                v-if="user.cover_image_url" 
                :src="user.cover_image_url" 
                :alt="user.name + ' cover'"
                class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        </div>

        <!-- Profile Info -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative -mt-20 bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:space-x-8">
                    <!-- Avatar (Page Logo) -->
                    <div class="flex-shrink-0">
                        <PageLogo :user="user" size="w-32 h-32" :circular="true" />
                    </div>

                    <!-- User Info -->
                    <div class="flex-1 mt-6 lg:mt-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                    <h1 class="text-3xl font-bold text-gray-900">
                                        {{ user.page_title || user.display_title || user.name }}
                                    </h1>
                                <p v-if="user.display_title && user.page_title" class="text-lg text-gray-600 mt-1">{{ user.display_title }}</p>
                                <p class="text-lg text-gray-600">@{{ user.username }}</p>
                                <p v-if="user.location && user.show_location !== false" class="text-gray-500 flex items-center mt-2">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ user.location }}
                                </p>
                                <p v-if="user.website && user.show_website !== false" class="text-blue-600 hover:text-blue-800 mt-1">
                                    <a :href="user.website" target="_blank" class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        {{ user.website.replace(/^https?:\/\//, '') }}
                                    </a>
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-3 mt-4 sm:mt-0">
                                <Button 
                                    v-if="isOwnProfile" 
                                    href="/profile"
                                    variant="outline"
                                >
                                    Edit Profile
                                </Button>
                                <Button 
                                    v-else-if="user.permissions.can_follow"
                                    @click="handleFollow"
                                    :disabled="isProcessing"
                                    :variant="isFollowing ? 'secondary' : 'primary'"
                                >
                                    {{ isFollowing ? 'Following' : 'Follow' }}
                                </Button>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div v-if="user.profile_description || user.bio" class="mt-4">
                            <p class="text-gray-700 leading-relaxed">
                                {{ user.profile_description || user.bio }}
                            </p>
                        </div>

                        <!-- Stats -->
                        <div class="flex space-x-8 mt-6 border-t pt-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(user.stats.lists_count) }}</div>
                                <div class="text-sm text-gray-500">Lists</div>
                            </div>
                            <Link 
                                v-if="user.permissions.show_followers"
                                :href="`/${user.custom_url || user.username}/followers`"
                                class="text-center hover:text-blue-600 transition-colors"
                            >
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(user.stats.followers_count) }}</div>
                                <div class="text-sm text-gray-500">Followers</div>
                            </Link>
                            <Link 
                                v-if="user.permissions.show_following"
                                :href="`/${user.custom_url || user.username}/following`"
                                class="text-center hover:text-blue-600 transition-colors"
                            >
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(user.stats.following_count) }}</div>
                                <div class="text-sm text-gray-500">Following</div>
                            </Link>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(user.stats.entries_count) }}</div>
                                <div class="text-sm text-gray-500">Places</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ formatNumber(user.stats.profile_views) }}</div>
                                <div class="text-sm text-gray-500">Views</div>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div v-if="user.social_links" class="flex space-x-4 mt-4">
                            <a 
                                v-if="user.social_links.twitter"
                                :href="user.social_links.twitter"
                                target="_blank"
                                class="text-blue-400 hover:text-blue-600"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a 
                                v-if="user.social_links.instagram"
                                :href="user.social_links.instagram"
                                target="_blank"
                                class="text-pink-500 hover:text-pink-700"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987s11.987-5.367 11.987-11.987C24.004 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.291C3.85 14.81 3.029 13.61 3.029 12.017c0-1.593.821-2.793 2.097-3.68.875-.801 2.026-1.291 3.323-1.291s2.448.49 3.323 1.291c1.276.887 2.097 2.087 2.097 3.68 0 1.593-.821 2.793-2.097 3.68-.875.801-2.026 1.291-3.323 1.291zm7.117 0c-1.297 0-2.448-.49-3.323-1.291-1.276-.887-2.097-2.087-2.097-3.68 0-1.593.821-2.793 2.097-3.68.875-.801 2.026-1.291 3.323-1.291s2.448.49 3.323 1.291c1.276.887 2.097 2.087 2.097 3.68 0 1.593-.821 2.793-2.097 3.68-.875.801-2.026 1.291-3.323 1.291z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tabs -->
            <div class="bg-white rounded-lg shadow">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button
                            @click="activeTab = 'lists'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'lists' 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Lists ({{ user.stats.lists_count }})
                        </button>
                        <button
                            v-if="user.permissions.show_following && user.following_users?.length"
                            @click="activeTab = 'following'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'following' 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Following
                        </button>
                        <button
                            v-if="user.permissions.show_activity && user.recent_activities?.length"
                            @click="activeTab = 'activity'"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm',
                                activeTab === 'activity' 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Activity
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Lists Tab -->
                    <div v-if="activeTab === 'lists'" class="space-y-8">
                        <!-- Pinned Lists -->
                        <div v-if="user.pinned_lists?.length" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                                </svg>
                                Pinned Lists
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div 
                                    v-for="list in user.pinned_lists" 
                                    :key="list.id"
                                    class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow"
                                >
                                    <Link 
                                        :href="`/${user.custom_url || user.username}/${list.slug}`"
                                        class="block"
                                    >
                                        <h4 class="font-semibold text-gray-900 mb-2">{{ list.name }}</h4>
                                        <p class="text-gray-600 text-sm mb-3">{{ list.description }}</p>
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <span>{{ list.items?.length || 0 }} items</span>
                                            <span>{{ formatNumber(list.view_count) }} views</span>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Lists -->
                        <div v-if="user.recent_lists?.length" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Lists</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div 
                                    v-for="list in user.recent_lists" 
                                    :key="list.id"
                                    class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow"
                                >
                                    <Link 
                                        :href="`/${user.custom_url || user.username}/${list.slug}`"
                                        class="block"
                                    >
                                        <h4 class="font-semibold text-gray-900 mb-2">{{ list.name }}</h4>
                                        <p class="text-gray-600 text-sm mb-3">{{ list.description }}</p>
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <span>{{ list.items?.length || 0 }} items</span>
                                            <span>{{ formatNumber(list.view_count) }} views</span>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!user.pinned_lists?.length && !user.recent_lists?.length" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No lists yet</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ isOwnProfile ? "You haven't created any lists yet." : `${user.name} hasn't created any public lists yet.` }}
                            </p>
                        </div>
                    </div>

                    <!-- Following Tab -->
                    <div v-if="activeTab === 'following'" class="space-y-8">
                        <!-- Following Users -->
                        <div v-if="user.following_users?.length" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Following</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Link 
                                    v-for="followedUser in user.following_users" 
                                    :key="followedUser.id"
                                    :href="`/${followedUser.custom_url || followedUser.username}`"
                                    class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg hover:shadow-md transition-shadow"
                                >
                                    <SmartAvatar 
                                        :user="followedUser" 
                                        size="w-12 h-12"
                                        className=""
                                    />
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ followedUser.display_title || followedUser.name }}</h4>
                                        <p class="text-sm text-gray-500">@{{ followedUser.username }}</p>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Followed Places -->
                        <div v-if="user.followed_entries?.length" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Followed Places</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div 
                                    v-for="entry in user.followed_entries" 
                                    :key="entry.id"
                                    class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow"
                                >
                                    <Link 
                                        :href="`/places/${entry.slug}`"
                                        class="block"
                                    >
                                        <div class="flex items-start space-x-4">
                                            <img 
                                                v-if="entry.logo_url"
                                                :src="entry.logo_url"
                                                :alt="entry.title"
                                                class="w-12 h-12 rounded-lg object-cover"
                                            />
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 mb-1">{{ entry.title }}</h4>
                                                <p class="text-gray-600 text-sm mb-2">{{ entry.description }}</p>
                                                <div class="flex items-center text-sm text-gray-500">
                                                    <span v-if="entry.category">{{ entry.category.name }}</span>
                                                    <span v-if="entry.region" class="ml-2">• {{ entry.region.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Tab -->
                    <div v-if="activeTab === 'activity'" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        <div class="space-y-4">
                            <div 
                                v-for="activity in user.recent_activities" 
                                :key="activity.id"
                                class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg"
                            >
                                <span class="text-2xl">{{ getActivityIcon(activity.type) }}</span>
                                <div class="flex-1">
                                    <p class="text-gray-900">
                                        <span class="font-medium">{{ user.name }}</span> {{ activity.description }}
                                        <span v-if="activity.subject" class="font-medium">{{ activity.subject.name || activity.subject.title }}</span>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">{{ formatDate(activity.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Since -->
        <div v-if="user.show_join_date !== false" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-gray-500 text-sm">
                Member since {{ formatDate(user.created_at) }}
            </p>
        </div>
    </div>
</template>