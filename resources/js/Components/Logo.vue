<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    href: {
        type: String,
        default: '/'
    },
    className: {
        type: String,
        default: ''
    },
    imgClassName: {
        type: String,
        default: 'h-8 w-auto'
    }
})
</script>

<template>
    <Link :href="href" class="inline-flex items-center">
        <img 
            src="/images/listerino_logo.svg" 
            alt="Listerino" 
            :class="imgClassName"
            class="block"
        />
    </Link>
</template>