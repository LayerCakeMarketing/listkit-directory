<template>
    <div class="flex-shrink-0">
        <div 
            :class="[sizeClass, circular ? 'rounded-full' : 'rounded-lg']" 
            class="bg-gray-200 flex items-center justify-center overflow-hidden border-2 border-white shadow-lg"
        >
            <!-- Custom Logo -->
            <img 
                v-if="logoUrl" 
                :src="logoUrl" 
                :alt="`${user.name} page logo`"
                class="w-full h-full object-cover"
                @error="handleImageError"
            />
            <!-- Initials -->
            <span 
                v-else 
                class="text-white font-bold"
                :style="{ fontSize: fontSize }"
            >
                {{ initials }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    size: {
        type: String,
        default: 'w-24 h-24' // Default size
    },
    circular: {
        type: Boolean,
        default: false
    }
})

// Generate initials from page_title or name
const initials = computed(() => {
    const title = props.user.page_title || props.user.name || ''
    return title
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
})

// Determine the logo URL based on page_logo_option
const logoUrl = computed(() => {
    const option = props.user.page_logo_option || 'initials'
    
    switch (option) {
        case 'custom':
            return props.user.page_logo_url
        case 'profile':
            return props.user.avatar_url
        case 'initials':
        default:
            return null // Show initials
    }
})

// Size classes and font size mapping
const sizeClass = computed(() => props.size)

const fontSize = computed(() => {
    if (props.size.includes('w-32') || props.size.includes('h-32')) return '2rem'
    if (props.size.includes('w-24') || props.size.includes('h-24')) return '1.5rem'
    if (props.size.includes('w-16') || props.size.includes('h-16')) return '1rem'
    if (props.size.includes('w-12') || props.size.includes('h-12')) return '0.875rem'
    return '1rem'
})

// Handle image load errors
const handleImageError = (event) => {
    console.warn('Page logo failed to load, falling back to initials')
    event.target.style.display = 'none'
}
</script>

<style scoped>
/* Add a gradient background for initials */
.rounded-lg:not(:has(img)), .rounded-full:not(:has(img)) {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
}
</style>