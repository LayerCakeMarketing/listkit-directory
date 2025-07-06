<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    user: Object,
    size: {
        type: String,
        default: 'w-32 h-32'
    },
    className: {
        type: String,
        default: ''
    }
})

const isGravatarFailed = ref(false)

const avatarUrl = computed(() => {
    // If user has uploaded avatar, use it
    if (props.user.has_custom_avatar && props.user.avatar_url) {
        return props.user.avatar_url
    }
    
    // Try Gravatar if not failed
    if (!isGravatarFailed.value && props.user.gravatar_url) {
        return props.user.gravatar_url
    }
    
    // Fall back to default
    return props.user.default_avatar_url
})

const handleGravatarError = () => {
    isGravatarFailed.value = true
}

const avatarClasses = computed(() => {
    return [
        props.size,
        'rounded-full border-4 border-white shadow-lg object-cover',
        props.className
    ].filter(Boolean).join(' ')
})
</script>

<template>
    <img 
        :src="avatarUrl" 
        :alt="user.name"
        :class="avatarClasses"
        @error="handleGravatarError"
    />
</template>