<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Public Header -->
        <PublicHeader :can-register="canRegister" />

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer (optional) -->
        <footer v-if="showFooter" class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <Logo href="/" imgClassName="h-12 w-auto mb-4" />
                        <p class="text-gray-300 max-w-md">
                            Discover and share amazing places through curated community lists.
                        </p>
                    </div>
                    
                    <div>
                        <h5 class="font-semibold mb-4">Features</h5>
                        <ul class="space-y-2 text-gray-300">
                            <li>Create Lists</li>
                            <li>Discover Places</li>
                            <li>Share with Community</li>
                            <li>Browse Categories</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h5 class="font-semibold mb-4">Get Started</h5>
                        <ul class="space-y-2 text-gray-300">
                            <li v-if="!$page.props.auth.user">
                                <Link :href="route('register')" class="hover:text-white transition-colors">
                                    Sign Up
                                </Link>
                            </li>
                            <li v-if="!$page.props.auth.user">
                                <Link :href="route('login')" class="hover:text-white transition-colors">
                                    Sign In
                                </Link>
                            </li>
                            <li v-if="$page.props.auth.user">
                                <Link :href="route('home')" class="hover:text-white transition-colors">
                                    Dashboard
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; 2025 Listerino. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicHeader from '@/Components/Headers/PublicHeader.vue'
import Logo from '@/Components/Logo.vue'

const props = defineProps({
    canRegister: {
        type: Boolean,
        default: true
    },
    showFooter: {
        type: Boolean,
        default: true
    }
})
</script>