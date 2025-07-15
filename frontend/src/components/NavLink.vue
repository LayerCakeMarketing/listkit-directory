<template>
    <router-link
        :to="to"
        :class="classes"
    >
        <slot />
    </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
    to: {
        type: [String, Object],
        required: true
    },
    active: {
        type: Boolean,
        default: false
    }
})

const route = useRoute()

const isActive = computed(() => {
    if (props.active !== undefined) return props.active
    
    if (typeof props.to === 'string') {
        return route.path === props.to
    }
    if (props.to.name) {
        return route.name === props.to.name
    }
    return false
})

const classes = computed(() => {
    return isActive.value
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out'
})
</script>