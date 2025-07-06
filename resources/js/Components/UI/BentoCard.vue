<script setup>
import { computed } from 'vue'

const props = defineProps({
    dark: {
        type: Boolean,
        default: false
    },
    className: {
        type: String,
        default: ''
    },
    eyebrow: {
        type: String,
        default: ''
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: ''
    },
    fade: {
        type: Array,
        default: () => [],
        validator: (value) => {
            return value.every(item => ['top', 'bottom'].includes(item))
        }
    }
})

const cardClasses = computed(() => {
    return [
        'group relative flex flex-col overflow-hidden rounded-lg',
        'bg-white shadow-sm ring-1 ring-black/5',
        'hover:shadow-md transition-all duration-300',
        props.dark ? 'dark:bg-gray-800 dark:ring-white/15' : '',
        props.className
    ].filter(Boolean).join(' ')
})

const hasFadeTop = computed(() => props.fade.includes('top'))
const hasFadeBottom = computed(() => props.fade.includes('bottom'))
</script>

<template>
    <div 
        :class="cardClasses"
        :data-dark="dark ? 'true' : undefined"
    >
        <div class="relative h-80 shrink-0">
            <!-- Graphic slot for flexible content -->
            <slot name="graphic" />
            
            <!-- Fade effects -->
            <div 
                v-if="hasFadeTop"
                class="absolute inset-0 bg-gradient-to-b from-white to-transparent to-50% group-data-[dark=true]:from-gray-800"
            />
            <div 
                v-if="hasFadeBottom"
                class="absolute inset-0 bg-gradient-to-t from-white to-transparent to-50% group-data-[dark=true]:from-gray-800"
            />
        </div>
        
        <div class="relative p-10">
            <!-- Eyebrow/subtitle -->
            <h3 
                v-if="eyebrow"
                class="text-xs/4 font-semibold uppercase tracking-widest text-gray-500 group-data-[dark=true]:text-gray-400"
            >
                {{ eyebrow }}
            </h3>
            
            <!-- Title -->
            <p class="mt-1 text-2xl/8 font-medium tracking-tight text-gray-950 group-data-[dark=true]:text-white">
                {{ title }}
            </p>
            
            <!-- Description -->
            <p 
                v-if="description"
                class="mt-2 max-w-[600px] text-sm/6 text-gray-600 group-data-[dark=true]:text-gray-400"
            >
                {{ description }}
            </p>
            
            <!-- Additional content slot -->
            <slot />
        </div>
    </div>
</template>