<template>
    <Modal :show="show" @close="close" max-width="4xl">
        <div class="p-6">
            <!-- List Quick View -->
            <div v-if="item && item.feed_type === 'list'" class="space-y-6">
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900">{{ item.name }}</h2>
                        <div class="flex items-center gap-3 mt-2 text-sm text-gray-600">
                            <span>by {{ item.user?.name }}</span>
                            <span>•</span>
                            <span>{{ formatDate(item.created_at) }}</span>
                            <span v-if="item.items_count" class="ml-2">• {{ item.items_count }} items</span>
                        </div>
                    </div>
                    <div v-if="getListImage(item)" class="ml-6 flex-shrink-0">
                        <img 
                            :src="getListImage(item)"
                            :alt="item.name"
                            class="w-32 h-32 rounded-lg object-cover"
                        />
                    </div>
                </div>

                <!-- Description -->
                <div v-if="item.description">
                    <p class="text-gray-700 leading-relaxed">{{ item.description }}</p>
                </div>

                <!-- Category and Tags -->
                <div class="flex flex-wrap items-center gap-4">
                    <div v-if="item.category" class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        {{ item.category.name }}
                    </div>
                    <div v-if="item.tags && item.tags.length > 0" class="flex flex-wrap gap-1">
                        <span
                            v-for="tag in item.tags.slice(0, 5)"
                            :key="tag.id"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                            :style="{ backgroundColor: tag.color || '#6B7280' }"
                        >
                            {{ tag.name }}
                        </span>
                        <span v-if="item.tags.length > 5" class="text-xs text-gray-500">
                            +{{ item.tags.length - 5 }} more
                        </span>
                    </div>
                </div>

                <!-- First few items preview -->
                <div v-if="loading">
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                </div>
                <div v-else-if="listItems && listItems.length > 0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Preview Items</h3>
                    <div class="space-y-4">
                        <div
                            v-for="(listItem, index) in listItems.slice(0, 5)"
                            :key="listItem.id"
                            class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg"
                        >
                            <div v-if="getItemImage(listItem)" class="flex-shrink-0">
                                <img 
                                    :src="getItemImage(listItem)"
                                    :alt="listItem.display_title || listItem.title"
                                    class="w-16 h-16 rounded-lg object-cover"
                                />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">
                                    <span class="text-gray-500 mr-2">{{ index + 1 }}.</span>
                                    {{ listItem.display_title || listItem.title }}
                                </h4>
                                <p v-if="listItem.display_content || listItem.content" class="text-sm text-gray-600 mt-1">
                                    {{ truncate(listItem.display_content || listItem.content, 100) }}
                                </p>
                            </div>
                        </div>
                        <div v-if="item.items_count > 5" class="text-center text-sm text-gray-500">
                            +{{ item.items_count - 5 }} more items
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center pt-4 border-t">
                    <button 
                        @click="close"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Close
                    </button>
                    <Link 
                        :href="`/${item.user?.username || item.user?.custom_url}/${item.slug}`"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        View Full List
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Place Quick View -->
            <div v-if="item && item.feed_type === 'place'" class="space-y-6">
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900">{{ item.title }}</h2>
                        <div class="flex items-center gap-3 mt-2 text-sm text-gray-600">
                            <span v-if="item.category">
                                {{ item.category.parent?.name }} › {{ item.category.name }}
                            </span>
                            <span>•</span>
                            <span>{{ formatDate(item.created_at) }}</span>
                        </div>
                    </div>
                    <div v-if="item.featured_image" class="ml-6 flex-shrink-0">
                        <img 
                            :src="item.featured_image"
                            :alt="item.title"
                            class="w-32 h-32 rounded-lg object-cover"
                        />
                    </div>
                </div>

                <!-- Description -->
                <div v-if="item.description">
                    <p class="text-gray-700 leading-relaxed">{{ item.description }}</p>
                </div>

                <!-- Location & Contact Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="item.location" class="space-y-2">
                        <h4 class="font-medium text-gray-900">Location</h4>
                        <div class="text-sm text-gray-600">
                            <div v-if="item.location.address" class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ item.location.address }}
                            </div>
                            <div v-if="item.location.city" class="ml-6">
                                {{ item.location.city }}, {{ item.location.state }} {{ item.location.zip_code }}
                            </div>
                        </div>
                    </div>

                    <div v-if="item.phone || item.email || item.website_url" class="space-y-2">
                        <h4 class="font-medium text-gray-900">Contact</h4>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div v-if="item.phone" class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ item.phone }}
                            </div>
                            <div v-if="item.email" class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ item.email }}
                            </div>
                            <div v-if="item.website_url" class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                </svg>
                                <a :href="item.website_url" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ item.website_url }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center pt-4 border-t">
                    <button 
                        @click="close"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Close
                    </button>
                    <Link 
                        :href="getPlaceUrl(item)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        View Full Place
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    show: Boolean,
    item: Object,
})

const emit = defineEmits(['close'])

const loading = ref(false)
const listItems = ref([])

const close = () => {
    emit('close')
}

// Watch for when modal opens with a list to fetch its items
watch(() => [props.show, props.item], async ([show, item]) => {
    if (show && item && item.feed_type === 'list') {
        await fetchListItems(item.id)
    }
}, { immediate: true })

const fetchListItems = async (listId) => {
    loading.value = true
    try {
        const response = await window.axios.get(`/data/lists/${listId}`)
        listItems.value = response.data.items || []
    } catch (error) {
        console.error('Error fetching list items:', error)
        listItems.value = []
    } finally {
        loading.value = false
    }
}

const formatDate = (date) => {
    const d = new Date(date)
    const now = new Date()
    const diffInHours = Math.floor((now - d) / (1000 * 60 * 60))
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor((now - d) / (1000 * 60))
        return diffInMinutes <= 1 ? 'just now' : `${diffInMinutes}m ago`
    } else if (diffInHours < 24) {
        return `${diffInHours}h ago`
    } else if (diffInHours < 168) { // 7 days
        const diffInDays = Math.floor(diffInHours / 24)
        return `${diffInDays}d ago`
    } else {
        return d.toLocaleDateString()
    }
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const getListImage = (list) => {
    return list.featured_image_url || list.featured_image || null
}

const getItemImage = (item) => {
    return item.item_image_url || 
           (item.directory_entry && (item.directory_entry.featured_image_url || item.directory_entry.featured_image)) ||
           item.image ||
           null
}

const getPlaceUrl = (place) => {
    if (place.category?.parent && place.category?.slug) {
        return `/${place.category.parent.slug}/${place.category.slug}/${place.slug}`
    }
    return `/places/entry/${place.slug}`
}
</script>