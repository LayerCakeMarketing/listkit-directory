<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '@/Components/Logo.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    publicLists: Array,
    featuredEntries: Array,
    categories: Array,
});

const hasContent = computed(() => {
    return props.publicLists?.length > 0 || props.featuredEntries?.length > 0 || props.categories?.length > 0;
});

const getEntryUrl = (entry) => {
    // Check if entry has category with parent
    if (entry.category && entry.category.parent_id) {
        // For featured entries, the category relationship might not include parent
        // We'll need to construct URL from available data or use fallback
        const parentSlug = entry.category.parent?.slug || 'places';
        const childSlug = entry.category.slug;
        return `/${parentSlug}/${childSlug}/${entry.slug}`;
    }
    return `/places/entry/${entry.slug}`;
};
</script>

<template>
    <Head title="Welcome to Listerino" />
    
    <PublicLayout :can-register="canRegister"
                  :show-footer="false">

        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-5xl font-bold mb-6">Discover Amazing Places & Lists</h2>
                    <p class="text-xl mb-8 max-w-3xl mx-auto">
                        Explore curated lists of restaurants, attractions, and local businesses. 
                        Create and share your own lists with the community.
                    </p>
                    <div class="space-x-4">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                        >
                            Start Creating Lists
                        </Link>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('lists.create')"
                            class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                        >
                            Create a List
                        </Link>
                        <a 
                            href="#explore" 
                            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
                        >
                            Explore Content
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section v-if="categories?.length > 0" id="explore" class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">Browse Categories</h3>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Discover businesses and places organized by category
                    </p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <Link 
                        v-for="category in categories" 
                        :key="category.id"
                        :href="`/places/category/${category.slug}`"
                        class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow cursor-pointer group"
                    >
                        <div class="text-4xl mb-4">{{ category.icon || '📁' }}</div>
                        <h4 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                            {{ category.name }}
                        </h4>
                        <p class="text-sm text-gray-500 mt-2">
                            {{ category.total_entries_count }} {{ category.total_entries_count === 1 ? 'entry' : 'entries' }}
                        </p>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Featured Places -->
        <section v-if="featuredEntries?.length > 0" class="py-16 bg-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">Featured Places</h3>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Hand-picked locations and businesses worth checking out
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link
                        v-for="entry in featuredEntries" 
                        :key="entry.id"
                        :href="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                    >
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ entry.category?.name || 'Uncategorized' }}
                                </span>
                                <span class="text-gray-500 text-sm">{{ entry.type }}</span>
                            </div>
                            
                            <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h4>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ entry.description }}</p>
                            
                            <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ entry.location.city }}, {{ entry.location.state }}
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Recent Public Lists -->
        <section v-if="publicLists?.length > 0" class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">Recent Community Lists</h3>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        See what the community is creating and sharing
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="list in publicLists" 
                        :key="list.id"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                    >
                        <div class="p-6">
                            <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ list.name }}</h4>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ list.description }}</p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    by {{ list.user?.name || 'Anonymous' }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    {{ list.items?.length || 0 }} items
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <Link 
                                    :href="`/${list.user?.custom_url || list.user?.username || list.user?.id}/${list.slug}`"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    View List →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-blue-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">Ready to Create Your Own Lists?</h3>
                <p class="text-gray-600 max-w-2xl mx-auto mb-8">
                    Join our community of list makers and share your favorite places with the world.
                </p>
                
                <div class="space-x-4">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Sign Up Free
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('lists.create')"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Create Your First List
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
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
                    <p>&copy; 2025 Listerino. Built with Laravel v{{ laravelVersion }}.</p>
                </div>
            </div>
        </footer>
    </PublicLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>