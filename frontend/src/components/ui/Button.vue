<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'outline'].includes(value)
    },
    href: {
        type: String,
        default: undefined
    },
    disabled: {
        type: Boolean,
        default: false
    },
    className: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'button'
    }
})

const emit = defineEmits(['click'])

const variants = {
    primary: [
        'inline-flex items-center justify-center px-4 py-2',
        'rounded-full border border-transparent bg-gray-950 shadow-md',
        'text-base font-medium whitespace-nowrap text-white',
        'disabled:bg-gray-950 disabled:opacity-40',
        'hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2',
        'transition-colors duration-200'
    ].join(' '),
    secondary: [
        'relative inline-flex items-center justify-center px-4 py-2',
        'rounded-full border border-transparent bg-white/15 shadow-md ring-1 ring-red-500/15',
        'after:absolute after:inset-0 after:rounded-full after:shadow-inner',
        'text-base font-medium whitespace-nowrap text-gray-950',
        'disabled:bg-white/15 disabled:opacity-40',
        'hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'transition-colors duration-200'
    ].join(' '),
    outline: [
        'inline-flex items-center justify-center px-2 py-1.5',
        'rounded-lg border border-transparent shadow-sm ring-1 ring-black/10',
        'text-sm font-medium whitespace-nowrap text-gray-950',
        'disabled:bg-transparent disabled:opacity-40',
        'hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2',
        'transition-colors duration-200'
    ].join(' ')
}

const buttonClasses = computed(() => {
    return `${variants[props.variant]} ${props.className}`.trim()
})

const handleClick = (event) => {
    if (!props.disabled) {
        emit('click', event)
    }
}
</script>

<template>
    <router-link 
        v-if="href" 
        :href="href" 
        :class="buttonClasses"
    >
        <slot />
    </router-link>
    <button 
        v-else
        :type="type"
        :class="buttonClasses"
        :disabled="disabled"
        @click="handleClick"
    >
        <slot />
    </button>
</template>