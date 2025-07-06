<template>
    <Head :title="list.name || 'Loading...'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
             
                <div class="flex space-x-2">
                    <Link
                        v-if="canEdit"
                        :href="list.id && list.user ? route('lists.edit', [list.user.username, list.slug]) : '#'"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                    >
                        Edit List
                    </Link>
                    <Link
                        :href="route('lists.my')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
                    >
                        Back to Lists
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div v-if="!list.id" class="max-w-4xl mx-auto sm:px-6 lg:px-8 text-center">
                <p class="text-gray-500">Loading list...</p>
            </div>
            <div v-else class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Title Above Image -->
                    <div class="p-6 pb-0">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ list.name }}</h1>
                    </div>

                    <!-- Full Width Featured Image -->
                    <div v-if="getListFeaturedImage()" class="w-full">
                        <img 
                            :src="getListFeaturedImage()" 
                            :alt="list.name"
                            class="w-full h-64 object-cover"
                        />
                    </div>

                    <!-- List Information Below Image -->
                    <div class="p-6 border-b border-gray-200">
                        <div v-if="list.description" class="mb-4">
                            <p class="text-gray-700 text-lg leading-relaxed">{{ list.description }}</p>
                        </div>
                        
                        <!-- List Meta -->
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                            <div v-if="list.user" class="flex items-center">
                                <span>By {{ list.user.name }}</span>
                            </div>
                            <div v-if="list.category" class="flex items-center">
                                <span class="bg-gray-100 px-2 py-1 rounded">{{ list.category.name }}</span>
                            </div>
                            <div v-if="list.items" class="flex items-center">
                                <span>{{ list.items.length }} items</span>
                            </div>
                        </div>
                        
                        <!-- Tags -->
                        <div v-if="list.tags && list.tags.length > 0" class="flex flex-wrap gap-2">
                            <span 
                                v-for="tag in list.tags" 
                                :key="tag.id"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                                :style="{ backgroundColor: tag.color || '#6B7280' }"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- List Items -->
                    <div class="p-6">
                        <div v-if="list.items && list.items.length > 0" class="space-y-6 divide-y divide-gray-200">
                            <div v-for="(item, index) in list.items" :key="item.id" class="pt-6 first:pt-0">
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <!-- Item Content (Left Side) -->
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-lg text-gray-900 mb-2">
                                            <span class="text-gray-500 mr-2">{{ index + 1 }}.</span>
                                            {{ item.display_title || item.title }}
                                        </h3>
                                        <p v-if="item.display_content || item.content" class="text-gray-700 leading-relaxed">
                                            {{ item.display_content || item.content }}
                                        </p>
                                        
                                        <!-- Additional Item Details -->
                                        <div class="mt-3 space-y-2">
                                            <div v-if="item.type === 'location' && item.data" class="text-sm text-gray-600">
                                                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                {{ item.data.address }}
                                            </div>
                                            
                                            <div v-if="item.type === 'event' && item.data" class="text-sm text-gray-600">
                                                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                {{ new Date(item.data.start_date).toLocaleString() }}
                                                <span v-if="item.data.location"> @ {{ item.data.location }}</span>
                                            </div>

                                            <div v-if="item.affiliate_url" class="mt-2">
                                                <a :href="item.affiliate_url" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                                    Visit Link
                                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                            
                                            <div v-if="item.notes" class="mt-3 p-3 bg-gray-50 rounded-md">
                                                <p class="text-sm text-gray-600 italic">{{ item.notes }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Item Image (Right Side) -->
                                    <div v-if="getItemImage(item)" class="flex-shrink-0 sm:order-last">
                                        <img 
                                            :src="getItemImage(item)" 
                                            :alt="item.display_title || item.title"
                                            class="w-full sm:w-48 h-32 sm:h-36 rounded-lg object-cover shadow-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center text-gray-500 py-8">
                            <p>No items in this list yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const axios = window.axios
const page = usePage()

const props = defineProps({
    listId: String
})

const list = ref({})

const canEdit = computed(() => {
    const user = page.props.auth.user
    if (!user) return false
    return list.value.user_id === user.id || ['admin', 'manager'].includes(user.role)
})

const fetchList = async () => {
    try {
        const response = await axios.get(`/data/lists/${props.listId}`)
        list.value = response.data
    } catch (error) {
        console.error('Error fetching list:', error)
    }
}

const getListFeaturedImage = () => {
    if (!list.value) return null
    
    // First priority: first image from gallery_images_with_urls array (with 16:9 variant)
    if (list.value.gallery_images_with_urls && list.value.gallery_images_with_urls.length > 0) {
        const firstImage = list.value.gallery_images_with_urls[0]
        if (typeof firstImage === 'object') {
            // Use gallery_url (16:9 variant) if available, fallback to standard url
            return firstImage.gallery_url || firstImage.url
        }
    }
    
    // Second priority: original gallery_images array
    if (list.value.gallery_images && list.value.gallery_images.length > 0) {
        const firstImage = list.value.gallery_images[0]
        if (typeof firstImage === 'object' && firstImage.url) {
            return firstImage.url
        } else if (typeof firstImage === 'string') {
            return firstImage
        }
    }
    
    // Fallback to legacy featured image fields
    return list.value.featured_image_url || 
           list.value.featured_image || 
           list.value.image_url ||
           list.value.image ||
           null
}

const getItemImage = (item) => {
    // First priority: item's own image
    if (item.item_image_url) {
        return item.item_image_url
    }
    
    // Second priority: directory entry's featured image (with various possible field names)
    if (item.type === 'directory_entry' && item.directory_entry) {
        return item.directory_entry.featured_image_url || 
               item.directory_entry.featured_image || 
               item.directory_entry.image_url ||
               item.directory_entry.image ||
               null
    }
    
    // Third priority: legacy image field
    if (item.image) {
        return item.image
    }
    
    return null
}

onMounted(() => {
    fetchList()
})
</script>