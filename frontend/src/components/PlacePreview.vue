<template>
    <div class="bg-gray-50 min-h-full">
        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header Section -->
            <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
                <!-- Cover Image -->
                <div v-if="place.cover_image_url" class="h-64 bg-gray-200 relative">
                    <img 
                        :src="place.cover_image_url" 
                        :alt="place.title"
                        class="w-full h-full object-cover"
                    />
                </div>
                
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Title and Category -->
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ place.title || 'Untitled Place' }}</h1>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-4">
                                <span v-if="place.category?.name" class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ place.category.name }}
                                </span>
                                <span v-if="place.type" class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ formatType(place.type) }}
                                </span>
                            </div>
                            
                            <!-- Location -->
                            <div v-if="place.location" class="flex items-start text-gray-600 mb-4">
                                <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div>
                                    <div v-if="place.location.address_line_1">{{ place.location.address_line_1 }}</div>
                                    <div>
                                        {{ place.location.city }}<span v-if="place.location.state">, {{ place.location.state }}</span>
                                        <span v-if="place.location.postal_code"> {{ place.location.postal_code }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logo -->
                        <div v-if="place.logo_url" class="ml-6 flex-shrink-0">
                            <img 
                                :src="place.logo_url" 
                                :alt="`${place.title} logo`"
                                class="w-24 h-24 rounded-lg object-cover shadow-sm"
                            />
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div v-if="place.phone" class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="text-gray-700">{{ place.phone }}</span>
                        </div>
                        <div v-if="place.email" class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-700">{{ place.email }}</span>
                        </div>
                        <div v-if="place.website_url" class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span class="text-blue-600 underline cursor-default">{{ formatWebsiteUrl(place.website_url) }}</span>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div v-if="place.description" class="prose max-w-none mb-6">
                        <div v-html="place.description"></div>
                    </div>
                    
                    <!-- Tags -->
                    <div v-if="place.tags && place.tags.length > 0" class="flex flex-wrap gap-2 mb-6">
                        <span 
                            v-for="tag in place.tags" 
                            :key="tag"
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700"
                        >
                            {{ tag }}
                        </span>
                    </div>
                    
                    <!-- Links -->
                    <div v-if="place.links && place.links.length > 0" class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Links</h3>
                        <div class="space-y-2">
                            <div v-for="(link, index) in place.links" :key="index" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <span class="text-blue-600 underline cursor-default">{{ link.text || link.url }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gallery -->
            <div v-if="place.gallery_images && place.gallery_images.length > 0" class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Gallery</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="(image, index) in place.gallery_images" 
                        :key="index"
                        class="aspect-square bg-gray-200 rounded-lg overflow-hidden"
                    >
                        <img 
                            :src="image" 
                            :alt="`Gallery image ${index + 1}`"
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
})

const formatType = (type) => {
    const typeMap = {
        'business_b2b': 'Business (B2B)',
        'business_b2c': 'Business (B2C)',
        'religious_org': 'Religious Organization',
        'point_of_interest': 'Point of Interest',
        'area_of_interest': 'Area of Interest',
        'service': 'Service',
        'online': 'Online'
    }
    return typeMap[type] || type
}

const formatWebsiteUrl = (url) => {
    if (!url) return ''
    return url.replace(/^https?:\/\//, '').replace(/\/$/, '')
}
</script>