<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                <img 
                    :src="userProfileImage" 
                    :alt="`${user?.name || 'User'} profile`"
                    class="h-8 w-8 rounded-full object-cover mr-2"
                    @error="handleImageError"
                />
                <span class="hidden sm:inline">{{ user?.name || 'Loading...' }}</span>
                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </template>

        <template #content>
            <!-- Account Management -->
            <div class="block px-4 py-2 text-xs text-gray-400">
                Manage Account
            </div>

            <DropdownLink :to="myPageUrl">
                My Page
            </DropdownLink>

            <DropdownLink :to="myListsUrl">
                My Lists
            </DropdownLink>

            <DropdownLink :to="{ name: 'SavedItems' }">
                My Saved Items
            </DropdownLink>

            <DropdownLink :to="{ name: 'MyChannels' }">
                My Channels
            </DropdownLink>

            <DropdownLink :to="{ name: 'ProfileEdit' }">
                Settings
            </DropdownLink>

            <!-- Admin Links -->
            <template v-if="user?.role === 'admin' || user?.role === 'manager'">
                <div class="border-t border-gray-100"></div>
                <DropdownLink :to="{ name: 'AdminDashboard' }">
                    Admin Dashboard
                </DropdownLink>
            </template>

            <div class="border-t border-gray-100"></div>

            <!-- Authentication -->
            <button
                @click="handleLogout"
                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
            >
                Log Out
            </button>
        </template>
    </Dropdown>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Dropdown from '@/components/Dropdown.vue'
import DropdownLink from '@/components/DropdownLink.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const router = useRouter()
const authStore = useAuthStore()

const userProfileImage = computed(() => {
    if (!props.user) return ''
    if (props.user.avatar_cloudflare_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${props.user.avatar_cloudflare_id}/public`
    } else if (props.user.avatar) {
        return props.user.avatar
    }
    return generateDefaultAvatar()
})

const myPageUrl = computed(() => {
    if (!props.user) return '/'
    if (props.user.custom_url) {
        return `/@${props.user.custom_url}`
    }
    return `/@${props.user.username || props.user.id}`
})

const myListsUrl = computed(() => {
    return '/mylists'
})

const generateDefaultAvatar = () => {
    if (!props.user?.name) return ''
    const initials = props.user.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
    
    const canvas = document.createElement('canvas')
    canvas.width = 32
    canvas.height = 32
    const ctx = canvas.getContext('2d')
    
    ctx.fillStyle = '#6366f1'
    ctx.fillRect(0, 0, 32, 32)
    
    ctx.fillStyle = '#ffffff'
    ctx.font = '14px sans-serif'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(initials, 16, 16)
    
    return canvas.toDataURL()
}

const handleImageError = (event) => {
    event.target.src = generateDefaultAvatar()
}

const handleLogout = async () => {
    await authStore.logout()
    router.push({ name: 'Welcome' })
}
</script>