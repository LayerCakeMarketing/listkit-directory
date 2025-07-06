<script setup>
import { ref, computed, watch } from 'vue'
import Button from './Button.vue'

const props = defineProps({
    modelValue: [String, File],
    currentImageUrl: String,
    gravatarUrl: String,
    defaultImageUrl: String,
    hasCustomImage: Boolean,
    label: String,
    accept: {
        type: String,
        default: 'image/*'
    },
    maxSize: {
        type: Number,
        default: 2048 // KB
    },
    preview: {
        type: Boolean,
        default: true
    },
    aspectRatio: {
        type: String,
        default: 'square' // 'square', 'cover', 'auto'
    }
})

const emit = defineEmits(['update:modelValue', 'remove'])

const fileInput = ref(null)
const selectedFile = ref(null)
const previewUrl = ref(null)
const isGravatarFailed = ref(false)

const aspectRatioClasses = computed(() => {
    switch (props.aspectRatio) {
        case 'square':
            return 'aspect-square'
        case 'cover':
            return 'aspect-[3/1]'
        default:
            return 'aspect-auto'
    }
})

const currentDisplayUrl = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value
    }
    
    if (props.hasCustomImage && props.currentImageUrl) {
        return props.currentImageUrl
    }
    
    if (!isGravatarFailed.value && props.gravatarUrl) {
        return props.gravatarUrl
    }
    
    return props.defaultImageUrl
})

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Validate file size
        if (file.size > props.maxSize * 1024) {
            alert(`File size must be less than ${props.maxSize}KB`)
            return
        }
        
        selectedFile.value = file
        
        // Create preview URL
        const reader = new FileReader()
        reader.onload = (e) => {
            previewUrl.value = e.target.result
        }
        reader.readAsDataURL(file)
        
        emit('update:modelValue', file)
    }
}

const handleRemove = () => {
    selectedFile.value = null
    previewUrl.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
    emit('remove')
}

const handleGravatarError = () => {
    isGravatarFailed.value = true
}

// Reset Gravatar failed state when props change
watch(() => props.gravatarUrl, () => {
    isGravatarFailed.value = false
})
</script>

<template>
    <div class="space-y-4">
        <label v-if="label" class="block text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        
        <!-- Image Preview -->
        <div v-if="preview" class="flex justify-center">
            <div :class="[
                'relative overflow-hidden rounded-lg border border-gray-300 bg-gray-50',
                aspectRatio === 'square' ? 'w-32 h-32' : aspectRatio === 'cover' ? 'w-48 h-16' : 'w-48 h-auto'
            ]">
                <img 
                    :src="currentDisplayUrl"
                    :alt="label || 'Image preview'"
                    :class="['w-full h-full object-cover', aspectRatioClasses]"
                    @error="handleGravatarError"
                />
                
                <!-- Remove button overlay -->
                <button
                    v-if="hasCustomImage || selectedFile"
                    @click="handleRemove"
                    type="button"
                    class="absolute top-1 right-1 rounded-full bg-red-500 text-white p-1 hover:bg-red-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Upload Button -->
        <div class="flex justify-center">
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                @change="handleFileSelect"
                class="hidden"
            />
            <Button
                @click="fileInput?.click()"
                variant="outline"
                type="button"
            >
                {{ hasCustomImage || selectedFile ? 'Change Image' : 'Upload Image' }}
            </Button>
        </div>
        
        <!-- Helper text -->
        <div class="text-center">
            <p class="text-sm text-gray-500">
                <template v-if="!hasCustomImage && !selectedFile">
                    <span v-if="!isGravatarFailed && gravatarUrl">Using Gravatar from your email</span>
                    <span v-else>Using default image</span>
                </template>
                <template v-else>
                    Custom image uploaded
                </template>
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Maximum file size: {{ maxSize }}KB. Supported formats: JPEG, PNG, GIF
            </p>
        </div>
    </div>
</template>