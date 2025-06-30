<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    category: Object,
    entries: Object,
});

const getEntryUrl = (entry) => {
    // If the entry's category has a parent, use hierarchical URL
    if (entry.category && entry.category.parent_id && entry.category.parent) {
        const parentSlug = entry.category.parent.slug;
        const childSlug = entry.category.slug;
        return `/${parentSlug}/${childSlug}/${entry.slug}`;
    }
    
    // If we're viewing a parent category and the entry is in this category,
    // it doesn't have the hierarchical structure needed
    // Use the fallback URL instead
    return `/places/entry/${entry.slug}`;
};

const stripHtml = (html) => {
    if (!html) return '';
    // Create a temporary div element to parse HTML
    const temp = document.createElement('div');
    temp.innerHTML = html;
    // Return text content which strips all HTML tags
    return temp.textContent || temp.innerText || '';
};
</script>

<template>
    <Head :title="`${category.name} Places`" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <Link href="/" class="text-3xl font-bold text-gray-900 hover:text-blue-600">
                            ListKit Directory
                        </Link>
                        <span class="text-gray-500">/</span>
                        <Link href="/places" class="text-gray-600 hover:text-blue-600">Places</Link>
                        <span class="text-gray-500">/</span>
                        <h1 class="text-xl text-gray-600">{{ category.name }}</h1>
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
            <!-- Category Header -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="text-5xl mr-4">{{ category.icon || '📁' }}</div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
                        <p v-if="category.description" class="text-gray-600 mt-2">{{ category.description }}</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <Link
                        href="/places"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        ← Back to All Categories
                    </Link>
                    <span class="text-gray-500">
                        {{ entries.total }} {{ entries.total === 1 ? 'entry' : 'entries' }} in this category
                    </span>
                </div>
            </div>

            <!-- Directory Entries -->
            <div class="mb-8">
                <div v-if="entries.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="entry in entries.data" 
                        :key="entry.id"
                        :href="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                    >
                        <!-- Entry Image -->
                        <div v-if="entry.cover_image_url || entry.logo_url" class="h-48 bg-gray-200 relative">
                            <img
                                :src="entry.cover_image_url || entry.logo_url"
                                :alt="entry.title"
                                class="w-full h-full object-cover"
                            />
                            <div v-if="entry.logo_url && entry.cover_image_url" class="absolute bottom-2 left-2">
                                <img
                                    :src="entry.logo_url"
                                    :alt="entry.title + ' logo'"
                                    class="w-12 h-12 rounded-lg object-cover border-2 border-white"
                                />
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ entry.category?.name || 'Uncategorized' }}
                                </span>
                                <span class="text-gray-500 text-sm">{{ entry.type }}</span>
                            </div>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                            <div class="text-gray-600 text-sm mb-4 line-clamp-2" v-html="stripHtml(entry.description)"></div>
                            
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
                    <div class="text-gray-500 text-lg">No entries found in {{ category.name }}.</div>
                    <p class="text-gray-400 mt-2">Be the first to add a business in this category!</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="entries.links && entries.links.length > 3" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <Link
                        v-for="(link, index) in entries.links"
                        :key="index"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-2 text-sm rounded-md transition-colors',
                            link.active 
                                ? 'bg-blue-600 text-white' 
                                : link.url 
                                    ? 'text-gray-700 hover:bg-gray-100' 
                                    : 'text-gray-400 cursor-not-allowed'
                        ]"
                    />
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