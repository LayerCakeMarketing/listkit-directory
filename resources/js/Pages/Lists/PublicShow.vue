<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Logo from '@/Components/Logo.vue';
import { computed } from 'vue';

const props = defineProps({
    list: Object,
    canEdit: Boolean,
});

const visibilityBadge = computed(() => {
    if (props.list.visibility === 'public') {
        return { text: 'Public', class: 'bg-green-100 text-green-800' };
    } else if (props.list.visibility === 'unlisted') {
        return { text: 'Unlisted', class: 'bg-yellow-100 text-yellow-800' };
    } else {
        return { text: 'Private', class: 'bg-red-100 text-red-800' };
    }
});
</script>

<template>
    <Head :title="list.name || 'List'" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <Logo href="/" imgClassName="h-8 w-auto" />
                        <span class="text-gray-500">/</span>
                        <Link 
                            :href="`/${list.user?.custom_url || list.user?.username || list.user?.id}/lists`"
                            class="text-gray-600 hover:text-blue-600"
                        >
                            {{ list.user?.name || 'User' }}'s Lists
                        </Link>
                        <span class="text-gray-500">/</span>
                        <h1 class="text-xl text-gray-600">{{ list.name }}</h1>
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

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- List Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ list.name }}</h1>
                        <p v-if="list.description" class="text-gray-600 mb-4">{{ list.description }}</p>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <span 
                            :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-medium', visibilityBadge.class]"
                        >
                            {{ visibilityBadge.text }}
                        </span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-sm text-gray-500 border-t pt-4">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Created by {{ list.user?.name || 'Anonymous' }}
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            {{ list.items?.length || 0 }} items
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{ list.view_count || 0 }} views
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Items -->
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">List Items</h2>
                
                <div v-if="list.items && list.items.length > 0" class="space-y-4">
                    <div 
                        v-for="(item, index) in list.items" 
                        :key="item.id"
                        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
                    >
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center font-semibold">
                                {{ index + 1 }}
                            </div>
                            
                            <!-- Item Image -->
                            <div v-if="item.item_image_url" class="flex-shrink-0">
                                <img 
                                    :src="item.item_image_url" 
                                    :alt="item.title || 'List item image'"
                                    class="w-20 h-20 rounded-lg object-cover"
                                />
                            </div>
                            
                            <div class="flex-1">
                                <!-- Directory Entry Type -->
                                <div v-if="item.type === 'directory_entry' && item.directory_entry" class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900">{{ item.directory_entry.title }}</h3>
                                        <span v-if="item.directory_entry.category" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ item.directory_entry.category.name }}
                                        </span>
                                    </div>
                                    
                                    <p v-if="item.directory_entry.description" class="text-gray-600 mb-3">
                                        {{ item.directory_entry.description }}
                                    </p>
                                    
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <div v-if="item.directory_entry.location" class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ item.directory_entry.location.city }}, {{ item.directory_entry.location.state }}
                                        </div>
                                        
                                        <a v-if="item.directory_entry.website_url" 
                                           :href="item.directory_entry.website_url" 
                                           target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Visit Website
                                        </a>
                                        
                                        <span v-if="item.directory_entry.phone" class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            {{ item.directory_entry.phone }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Text Type -->
                                <div v-else-if="item.type === 'text'" class="mb-4">
                                    <h3 v-if="item.title" class="text-xl font-semibold text-gray-900 mb-2">{{ item.title }}</h3>
                                    <div v-if="item.content" class="text-gray-600 prose prose-sm max-w-none">
                                        {{ item.content }}
                                    </div>
                                </div>
                                
                                <!-- Location Type -->
                                <div v-else-if="item.type === 'location'" class="mb-4">
                                    <h3 v-if="item.title" class="text-xl font-semibold text-gray-900 mb-2">{{ item.title }}</h3>
                                    <div v-if="item.content" class="text-gray-600 mb-2">{{ item.content }}</div>
                                    <div v-if="item.data && item.data.address" class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ item.data.address }}
                                    </div>
                                </div>
                                
                                <!-- Event Type -->
                                <div v-else-if="item.type === 'event'" class="mb-4">
                                    <h3 v-if="item.title" class="text-xl font-semibold text-gray-900 mb-2">{{ item.title }}</h3>
                                    <div v-if="item.content" class="text-gray-600 mb-2">{{ item.content }}</div>
                                    <div v-if="item.data && item.data.date" class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ item.data.date }}
                                    </div>
                                </div>
                                
                                <!-- Notes (for any item type) -->
                                <div v-if="item.notes" class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-1">Notes:</h4>
                                    <p class="text-gray-600 text-sm">{{ item.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-12 bg-white rounded-lg shadow-md">
                    <div class="text-gray-500 text-lg">This list is empty.</div>
                    <p class="text-gray-400 mt-2">No items have been added yet.</p>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="mt-12 bg-blue-50 rounded-lg p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Create Your Own Lists</h3>
                <p class="text-gray-600 mb-6">Join our community and start creating and sharing your own curated lists.</p>
                <div class="space-x-4">
                    <Link
                        href="/register"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Sign Up Free
                    </Link>
                    <Link
                        href="/login"
                        class="text-blue-600 hover:text-blue-800 font-semibold"
                    >
                        Already have an account? Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>