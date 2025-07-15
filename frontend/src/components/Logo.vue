<template>
    <router-link :to="to" class="flex items-center">
        <img 
            src="/images/listerino_logo.svg" 
            alt="Listerino" 
            :class="imgClassName" 
        />
    </router-link>
</template>

<script setup>
defineProps({
    to: {
        type: [String, Object],
        default: '/'
    },
    imgClassName: {
        type: String,
        default: 'h-8'
    }
})
</script>