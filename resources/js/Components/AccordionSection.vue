<template>
    <div class="border border-gray-200 rounded-lg">
        <button
            type="button"
            @click="toggleOpen"
            class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset"
            :class="{ 'rounded-t-lg': isOpen, 'rounded-lg': !isOpen }"
        >
            <div class="flex items-center">
                <span class="text-lg font-semibold text-gray-900">{{ title }}</span>
                <span v-if="subtitle" class="ml-2 text-sm text-gray-500">{{ subtitle }}</span>
            </div>
            <svg
                class="w-5 h-5 text-gray-500 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        
        <div
            v-show="isOpen"
            class="px-4 py-4 bg-white border-t border-gray-200 rounded-b-lg"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    defaultOpen: {
        type: Boolean,
        default: false
    }
})

const isOpen = ref(props.defaultOpen)

const toggleOpen = () => {
    isOpen.value = !isOpen.value
}
</script>