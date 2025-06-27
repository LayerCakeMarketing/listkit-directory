<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
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
                                
                                <!-- Admin Dropdown (if user has admin items) -->
                                <div v-if="navigation.admin && navigation.admin.length > 0" class="flex items-center">
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
                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 text-xs text-gray-400 border-b">
                                            {{ $page.props.auth.user.email }}
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 ml-2">
                                                {{ $page.props.auth.user.role }}
                                            </span>
                                        </div>
                                        
                                        <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                        
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
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
                            <div class="font-medium text-base text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $page.props.auth.user.role }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

// Navigation configuration
const navigationConfig = {
    main: [
        {
            name: 'Dashboard',
            href: 'dashboard',
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
    
    business: [
        {
            name: 'My Businesses',
            href: 'business.index',
            roles: ['business_owner']
        },
        {
            name: 'Claims',
            href: 'business.claims',
            roles: ['business_owner']
        }
    ],
    
    user: [
        {
            name: 'My Lists',
            href: 'user.lists',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        },
        {
            name: 'Favorites',
            href: 'user.favorites',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        }
    ]
};

// Get navigation items based on user role
const navigation = computed(() => {
    const userRole = page.props.auth.user?.role;
    if (!userRole) return { main: [], admin: [], business: [], user: [] };
    
    const filtered = {};
    
    // Filter each section based on user role
    Object.keys(navigationConfig).forEach(section => {
        filtered[section] = navigationConfig[section].filter(item => 
            item.roles.includes(userRole)
        );
    });
    
    return filtered;
});

// Check if any admin route is active
const isAdminSectionActive = computed(() => {
    return route().current('admin.*');
});
</script>