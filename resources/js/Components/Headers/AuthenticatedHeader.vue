<template>
    <nav class="Auth_ fixed z-50 top-0 left-0 w-full bg-white border-b border-gray-100 ">
        <!-- Primary Navigation Menu -->
        <div class=" mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="shrink-0 flex items-center">
                        <Link :href="route('home')">
                            <ApplicationLogo
                                class="block h-9 w-auto fill-current text-gray-800"
                            />
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <!-- Main Navigation Items -->
                        <template v-for="item in navigation.main" :key="item.href">
                            <NavLink 
                                :href="route(item.href)" 
                                :active="route().current(item.href)"
                            >
                                {{ item.name }}
                            </NavLink>
                        </template>
                        
                     

                        <!-- Other Navigation Items -->
                        <template v-for="item in navigation.user" :key="item.href">
                            <NavLink 
                                :href="route(item.href)" 
                                :active="route().current(item.href)"
                            >
                                {{ item.name }}
                            </NavLink>
                        </template>
                    </div>
                </div>

                <!-- Right side of navbar (user dropdown, etc.) -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <!-- User Profile Dropdown -->
                    <div class="ml-3 relative">
                        <UserProfileDropdown />
                    </div>
                </div>

                <!-- Hamburger -->
                <div class="-mr-2 flex items-center sm:hidden">
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                    >
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                :class="{
                                    hidden: showingNavigationDropdown,
                                    'inline-flex': !showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{
                                    hidden: !showingNavigationDropdown,
                                    'inline-flex': showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div
            :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
            class="sm:hidden"
        >
            <div class="pt-2 pb-3 space-y-1">
                <!-- Main Navigation -->
                <ResponsiveNavLink 
                    v-for="item in navigation.main" 
                    :key="item.href"
                    :href="route(item.href)" 
                    :active="route().current(item.href)"
                >
                    {{ item.name }}
                </ResponsiveNavLink>
                
                <!-- Admin Section -->
                <div v-if="navigation.admin && navigation.admin.length > 0">
                    <div class="px-4 py-2 text-xs text-gray-400 uppercase">Admin</div>
                    <ResponsiveNavLink 
                        v-for="item in navigation.admin" 
                        :key="item.href"
                        :href="route(item.href)" 
                        :active="route().current(item.href)"
                    >
                        {{ item.name }}
                    </ResponsiveNavLink>
                </div>

                <!-- User Section -->
                <div v-if="navigation.user && navigation.user.length > 0">
                    <div class="px-4 py-2 text-xs text-gray-400 uppercase">Menu</div>
                    <ResponsiveNavLink 
                        v-for="item in navigation.user" 
                        :key="item.href"
                        :href="route(item.href)" 
                        :active="route().current(item.href)"
                    >
                        {{ item.name }}
                    </ResponsiveNavLink>
                </div>
            </div>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="flex items-center space-x-3">
                        <img
                            :src="userProfileImage"
                            :alt="`${$page.props.auth.user.name} profile`"
                            class="h-10 w-10 rounded-full object-cover bg-gray-200"
                            @error="handleMobileImageError"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-base text-gray-800 truncate">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500 truncate">{{ $page.props.auth.user.email }}</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $page.props.auth.user.role }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <ResponsiveNavLink :href="myPageUrl">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            My Page
                        </div>
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('profile.edit')">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </div>
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Log Out
                        </div>
                    </ResponsiveNavLink>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import UserProfileDropdown from '@/Components/UserProfileDropdown.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'

const showingNavigationDropdown = ref(false)
const page = usePage()

// Navigation configuration
const navigationConfig = {
    main: [
       /* {
            name: 'Home',
            href: 'home',
            roles: ['admin', 'manager', 'editor', 'business_owner', 'user']
        }, */
        {
            name: 'Explore Lists',
            href: 'lists.public.index',
            roles: ['admin', 'manager', 'editor', 'business_owner', 'user']
        },
        {
            name: 'Places',
            href: 'places.index',
            roles: ['admin', 'manager', 'editor', 'business_owner', 'user']
        },
    ],
    
    admin: [
        {
            name: 'Admin Dashboard',
            href: 'admin.dashboard',
            roles: ['admin', 'manager']
        },
        {
            name: 'Users',
            href: 'admin.users',
            roles: ['admin', 'manager']
        },
        {
            name: 'Directory Entries',
            href: 'admin.entries',
            roles: ['admin', 'manager', 'editor']
        },
        {
            name: 'Categories',
            href: 'admin.categories',
            roles: ['admin', 'manager']
        },
        {
            name: 'Reports',
            href: 'admin.reports',
            roles: ['admin']
        },
        {
            name: 'Settings',
            href: 'admin.settings',
            roles: ['admin']
        }
    ],
    
    user: [
     /*   {
            name: 'My Lists',
            href: 'lists.my',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        },
        {
            name: 'Favorites',
            href: 'user.favorites',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        }
            */
    ]
}

// Get navigation items based on user role
const navigation = computed(() => {
    const userRole = page.props.auth.user?.role
    if (!userRole) return { main: [], admin: [], user: [] }
    
    const filtered = {}
    
    // Filter each section based on user role
    Object.keys(navigationConfig).forEach(section => {
        filtered[section] = navigationConfig[section].filter(item => 
            item.roles.includes(userRole)
        )
    })
    
    return filtered
})

// Check if any admin route is active
const isAdminSectionActive = computed(() => {
    return route().current('admin.*')
})

// Mobile profile image
const userProfileImage = computed(() => {
    const user = page.props.auth.user
    if (user.avatar_cloudflare_id) {
        return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${user.avatar_cloudflare_id}/public`
    } else if (user.avatar) {
        return user.avatar
    }
    return generateDefaultAvatar()
})

// My Page URL
const myPageUrl = computed(() => {
    const user = page.props.auth.user
    if (user.custom_url) {
        return `/${user.custom_url}`
    }
    return `/user/${user.username || user.id}`
})

// Generate default avatar for mobile view
const generateDefaultAvatar = () => {
    const user = page.props.auth.user
    const initials = user.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
    
    const canvas = document.createElement('canvas')
    canvas.width = 40
    canvas.height = 40
    const ctx = canvas.getContext('2d')
    
    ctx.fillStyle = '#6366f1'
    ctx.fillRect(0, 0, 40, 40)
    
    ctx.fillStyle = '#ffffff'
    ctx.font = '16px sans-serif'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(initials, 20, 20)
    
    return canvas.toDataURL()
}

// Handle mobile image error
const handleMobileImageError = (event) => {
    event.target.src = generateDefaultAvatar()
}
</script>