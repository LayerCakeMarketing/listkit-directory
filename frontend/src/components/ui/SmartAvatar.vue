<script setup>
import { ref, computed } from 'vue'
import { md5 } from '@/utils/md5'

const props = defineProps({
    user: Object,
    size: {
        type: [String, Number],
        default: 8
    },
    className: {
        type: String,
        default: ''
    }
})

const isGravatarFailed = ref(false)
const isUploadedAvatarFailed = ref(false)

// Convert size number to Tailwind classes
const sizeClasses = computed(() => {
    if (typeof props.size === 'number') {
        return `w-${props.size} h-${props.size}`
    }
    return props.size
})

const gravatarUrl = computed(() => {
    if (!props.user?.email) return null
    const emailHash = md5(props.user.email.trim().toLowerCase())
    // Use d=404 to return 404 if no gravatar exists
    return `https://www.gravatar.com/avatar/${emailHash}?s=200&d=404`
})

const avatarUrl = computed(() => {
    // Priority 1: Uploaded avatar (Cloudflare or local)
    if (!isUploadedAvatarFailed.value && props.user?.avatar_url) {
        // If it's a Cloudflare URL or starts with http, use as-is
        if (props.user.avatar_url.startsWith('http')) {
            return props.user.avatar_url
        }
        // If it's a local path, prepend /storage/
        if (props.user.avatar_url) {
            return `/storage/${props.user.avatar_url}`
        }
    }
    
    // Priority 2: Gravatar (if not failed)
    if (!isGravatarFailed.value && gravatarUrl.value) {
        return gravatarUrl.value
    }
    
    // Priority 3: Default Listerino profile image
    return '/images/listerino_profile.png'
})

const handleError = (event) => {
    const currentSrc = event.target.src
    
    // If uploaded avatar failed, try gravatar
    if (!isUploadedAvatarFailed.value && (currentSrc.includes('storage') || currentSrc.includes('cloudflare') || currentSrc.includes('imagedelivery'))) {
        isUploadedAvatarFailed.value = true
        return
    }
    
    // If gravatar failed, the computed property will fallback to default
    if (currentSrc.includes('gravatar.com')) {
        isGravatarFailed.value = true
    }
}

const avatarClasses = computed(() => {
    return [
        sizeClasses.value,
        'rounded-full object-cover',
        props.className
    ].filter(Boolean).join(' ')
})
</script>

<template>
    <img 
        :src="avatarUrl" 
        :alt="user?.name || 'User avatar'"
        :class="avatarClasses"
        @error="handleError"
    />
</template>