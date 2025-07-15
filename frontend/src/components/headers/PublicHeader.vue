<template>
    <header class="marketing bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between py-6">
                <!-- Logo -->
                <div class="flex items-center">
                    <Logo to="/" imgClassName="h-10 w-auto" />
                </div>
                
                <!-- Public Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <router-link
                        to="/lists"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                    >
                        Explore
                    </router-link>
                    <router-link
                        to="/places"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                    >
                        Places
                    </router-link>
               
                    <router-link
                        to="/local/california"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                    >
                        Browse California
                    </router-link>
                </nav>

                <!-- Auth Links -->
                <div class="flex items-center space-x-4">
                    <template v-if="user">
                        <UserProfileDropdown :user="user" />
                    </template>
                    
                    <template v-else>
                        <router-link
                            :to="{ name: 'Login' }"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Sign In
                        </router-link>
                        
                        <router-link
                            v-if="canRegister"
                            :to="{ name: 'Register' }"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Get Started
                        </router-link>
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
                    <router-link
                        to="/lists"
                        class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                        @click="showMobileMenu = false"
                    >
                        Explore Lists
                    </router-link>
                    <router-link
                        to="/places"
                        class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                        @click="showMobileMenu = false"
                    >
                        Places
                    </router-link>
              
                    <router-link
                        to="/local/california"
                        class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                        @click="showMobileMenu = false"
                    >
                         California
                    </router-link>
                    
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <template v-if="!user">
                            <router-link
                                :to="{ name: 'Login' }"
                                class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                                @click="showMobileMenu = false"
                            >
                                Sign In
                            </router-link>
                            <router-link
                                v-if="canRegister"
                                :to="{ name: 'Register' }"
                                class="block px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md"
                                @click="showMobileMenu = false"
                            >
                                Get Started
                            </router-link>
                        </template>
                        <template v-else>
                            <router-link
                                :to="{ name: 'Home' }"
                                class="block px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md"
                                @click="showMobileMenu = false"
                            >
                                Home
                            </router-link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Logo from '@/components/Logo.vue'
import UserProfileDropdown from '@/components/UserProfileDropdown.vue'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const props = defineProps({
    canRegister: {
        type: Boolean,
        default: true
    }
})

const showMobileMenu = ref(false)
</script>