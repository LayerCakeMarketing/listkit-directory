<template>
    <header class="marketing bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between py-6">
                <!-- Logo -->
                <div class="flex items-center">
                    <Logo href="/" imgClassName="h-10 w-auto" />
                </div>
                
                <!-- Public Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <Link
                        href="/lists"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                    >
                        Explore
                    </Link>
                    
                </nav>

                <!-- Auth Links -->
                <div class="flex items-center space-x-4">
                    <template v-if="$page.props.auth.user">
            
                        <UserProfileDropdown />
                    </template>
                    
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Sign In
                        </Link>
                        
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button
                        @click="showMobileMenu = !showMobileMenu"
                        class="text-gray-600 hover:text-gray-900 p-2"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="showMobileMenu" class="md:hidden border-t border-gray-200 py-4">
                <div class="space-y-2">
                    <Link
                        href="/lists"
                        class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                    >
                        Browse Lists
                    </Link>
                    <Link
                        href="/places"
                        class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                    >
                        Places
                    </Link>
                    
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <template v-if="!$page.props.auth.user">
                            <Link
                                :href="route('login')"
                                class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                            >
                                Sign In
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="block px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md"
                            >
                                Get Started
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('home')"
                                class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                            >
                                Dashboard
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import UserProfileDropdown from '@/Components/UserProfileDropdown.vue'

const props = defineProps({
    canRegister: {
        type: Boolean,
        default: true
    }
})

const showMobileMenu = ref(false)
</script>