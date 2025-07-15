<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div v-if="item" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ item.feed_type === 'list' ? item.name : item.title }}
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Quick view content based on feed type -->
            <div v-if="item.feed_type === 'list'" class="space-y-4">
                <p v-if="item.description" class="text-gray-700">{{ item.description }}</p>
                
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>{{ item.items_count || 0 }} items</span>
                    <span v-if="item.views_count">{{ item.views_count }} views</span>
                    <span v-if="item.category">{{ item.category.name }}</span>
                </div>

                <div class="mt-4">
                    <router-link
                        :to="`/@${item.user?.custom_url || item.user?.username}/${item.slug}`"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        @click="$emit('close')"
                    >
                        View Full List
                    </router-link>
                </div>
            </div>

            <div v-else-if="item.feed_type === 'place'" class="space-y-4">
                <p v-if="item.description" class="text-gray-700">{{ item.description }}</p>
                
                <div v-if="item.location" class="text-sm text-gray-600">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ item.location.city }}, {{ item.location.state }}
                </div>

                <div class="mt-4">
                    <router-link
                        :to="`/places/${item.slug}`"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        @click="$emit('close')"
                    >
                        View Place Details
                    </router-link>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/components/Modal.vue'

defineProps({
    show: Boolean,
    item: Object
})

defineEmits(['close'])
</script>