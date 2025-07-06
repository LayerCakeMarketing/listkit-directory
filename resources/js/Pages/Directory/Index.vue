<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Logo from '@/Components/Logo.vue';

const props = defineProps({
    entries: Object,
    categories: Array,
});

const getEntryUrl = (entry) => {
    // Check if entry has category with parent
    if (entry.category && entry.category.parent_id) {
        const parentSlug = entry.category.parent?.slug || 'directory';
        const childSlug = entry.category.slug;
        return `/${parentSlug}/${childSlug}/${entry.slug}`;
    }
    return `/directory/entry/${entry.slug}`;
};
</script>

<template>
    <Head title="Directory" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <Logo href="/" imgClassName="h-8 w-auto" />
                        <span class="text-gray-500">/</span>
                        <h1 class="text-xl text-gray-600">Browse Directory</h1>
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <Link
                            href="/"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Home
                        </Link>
                        <Link
                            href="/login"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Get Started
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Categories Filter -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Browse by Category</h2>
                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/directory"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        All Categories
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="`/directory/category/${category.slug}`"
                        class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        {{ category.icon || '📁' }} {{ category.name }} 
                        <span class="text-sm text-gray-500">({{ category.total_entries_count || category.directory_entries_count || 0 }})</span>
                    </Link>
                </div>
            </div>

            <!-- Directory Entries -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        All Directory Entries
                        <span class="text-lg text-gray-500 font-normal">({{ entries.total }} total)</span>
                    </h2>
                </div>

                <div v-if="entries.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="entry in entries.data" 
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
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ entry.description }}</p>
                            
                            <div v-if="entry.location" class="flex items-center text-gray-500 text-sm mb-3">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ entry.location.city }}, {{ entry.location.state }}
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <a v-if="entry.website_url" 
                                       :href="entry.website_url" 
                                       target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        Visit Website
                                    </a>
                                    <span v-if="entry.phone" class="text-gray-500 text-sm">{{ entry.phone }}</span>
                                </div>
                                <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    ⭐ Featured
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="text-center py-12">
                    <div class="text-gray-500 text-lg">No directory entries found.</div>
                    <p class="text-gray-400 mt-2">Be the first to add a business or location!</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="entries.links && entries.links.length > 3" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <template v-for="(link, index) in entries.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-blue-600 text-white' 
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-blue-600 text-white' 
                                    : 'text-gray-400 cursor-not-allowed'
                            ]"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>