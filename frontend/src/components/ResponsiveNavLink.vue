<template>
    <component
        :is="as"
        :to="to"
        :class="classes"
        v-bind="$attrs"
    >
        <slot />
    </component>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
    as: {
        type: String,
        default: 'router-link'
    },
    to: {
        type: [String, Object],
        default: null
    },
    active: {
        type: Boolean,
        default: false
    }
})

const route = useRoute()

const isActive = computed(() => {
    if (!props.to) return false
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
        ? 'block pl-3 pr-4 py-2 border-l-4 border-indigo-400 text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out'
})
</script>