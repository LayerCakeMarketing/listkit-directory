<template>
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <Link :href="route('dashboard')" class="text-xl font-bold text-gray-800">
                            {{ appName }}
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <!-- Main Navigation -->
                        <template v-for="item in navigation.main" :key="item.href">
                            <NavLink 
                                :href="route(item.href)" 
                                :active="route().current(item.href)"
                            >
                                {{ item.name }}
                            </NavLink>
                        </template>

                        <!-- Admin Dropdown (if user has admin items) -->
                        <div v-if="navigation.admin && navigation.admin.length > 0" class="relative">
                            <Dropdown align="left" width="48">
                                <template #trigger>
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                            :class="isAdminSectionActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                        Admin
                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink 
                                        v-for="item in navigation.admin" 
                                        :key="item.href"
                                        :href="route(item.href)"
                                    >
                                        {{ item.name }}
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Additional sections can be added similarly -->
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

                <!-- Right side navigation -->
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <!-- User dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                                <div>{{ user.name }}</div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-2 text-xs text-gray-400">
                                {{ user.email }}
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 ml-2">
                                    {{ user.role }}
                                </span>
                            </div>
                            <div class="border-t border-gray-100"></div>
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex items-center sm:hidden">
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div :class="{block: showingNavigationDropdown, hidden: ! showingNavigationDropdown}" class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <!-- Main items -->
                <ResponsiveNavLink 
                    v-for="item in navigation.main" 
                    :key="item.href"
                    :href="route(item.href)" 
                    :active="route().current(item.href)"
                >
                    {{ item.name }}
                </ResponsiveNavLink>

                <!-- Admin section -->
                <div v-if="navigation.admin && navigation.admin.length > 0">
                    <div class="px-4 py-2 text-xs text-gray-400">Admin</div>
                    <ResponsiveNavLink 
                        v-for="item in navigation.admin" 
                        :key="item.href"
                        :href="route(item.href)" 
                        :active="route().current(item.href)"
                    >
                        {{ item.name }}
                    </ResponsiveNavLink>
                </div>

                <!-- User section -->
                <div v-if="navigation.user && navigation.user.length > 0">
                    <div class="px-4 py-2 text-xs text-gray-400">User Menu</div>
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

            <!-- Mobile user info -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ user.name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ user.email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                        Log Out
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
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { getGroupedNavigation } from '@/config/navigation'

const page = usePage()
const showingNavigationDropdown = ref(false)

const user = computed(() => page.props.auth.user)
const appName = computed(() => page.props.appName || 'Directory App')

// Get navigation items based on user role
const navigation = computed(() => {
    if (!user.value) return { main: [] }
    return getGroupedNavigation(user.value.role)
})

// Check if any admin route is active
const isAdminSectionActive = computed(() => {
    return route().current('admin.*')
})
</script>