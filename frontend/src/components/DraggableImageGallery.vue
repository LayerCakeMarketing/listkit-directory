<template>
    <div v-if="images.length > 0" class="mt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ title }}:</h4>
        <draggable 
            v-model="localImages" 
            item-key="id"
            @change="handleOrderChange"
            :class="gridClass"
            ghost-class="opacity-50"
            chosen-class="ring-2 ring-blue-500"
            drag-class="transform rotate-2 shadow-lg"
        >
            <template #item="{element: image, index}">
                <div class="relative bg-gray-50 rounded-lg p-2 cursor-move group hover:bg-gray-100 transition-all duration-200">
                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2">
                        <img
                            :src="image.url"
                            :alt="image.filename"
                            class="w-full h-full object-cover transition-transform group-hover:scale-105"
                        />
                    </div>
                    <p class="text-xs text-gray-600 truncate" :title="image.filename">{{ image.filename }}</p>
                    
                    <!-- Order Number Badge -->
                    <div class="absolute top-1 left-1 bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-medium">
                        {{ index + 1 }}
                    </div>
                    
                    <!-- Remove Button -->
                    <button
                        @click="handleRemove(index)"
                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors text-xs opacity-0 group-hover:opacity-100"
                    >
                        ×
                    </button>
                    
                    <!-- Drag Hint -->
                    <div class="absolute bottom-1 right-1 bg-gray-800 bg-opacity-75 text-white rounded px-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ dragHint }}
                    </div>
                    
                    <!-- Primary Badge (for first image) -->
                    <div v-if="showPrimary && index === 0" class="absolute bottom-1 left-1 bg-green-500 text-white rounded px-1 text-xs font-medium">
                        Primary
                    </div>
                </div>
            </template>
        </draggable>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import draggable from 'vuedraggable'

const props = defineProps({
    images: {
        type: Array,
        required: true
    },
    title: {
        type: String,
        default: 'Uploaded Images'
    },
    gridCols: {
        type: String,
        default: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4'
    },
    showPrimary: {
        type: Boolean,
        default: false
    },
    dragHint: {
        type: String,
        default: 'Drag to reorder'
    }
})

const emit = defineEmits(['update:images', 'remove', 'reorder'])

// Local reactive copy of images for draggable
const localImages = ref([...props.images])

// Watch for external changes to images prop
watch(() => props.images, (newImages) => {
    localImages.value = [...newImages]
}, { deep: true })

const gridClass = computed(() => `grid ${props.gridCols} gap-4`)

const handleOrderChange = () => {
    emit('update:images', [...localImages.value])
    emit('reorder', [...localImages.value])
}

const handleRemove = (index) => {
    const removedImage = localImages.value[index]
    localImages.value.splice(index, 1)
    emit('remove', index, removedImage)
    emit('update:images', [...localImages.value])
}
</script>

<style scoped>
.cursor-move {
    cursor: grab;
}

.cursor-move:active {
    cursor: grabbing;
}
</style>