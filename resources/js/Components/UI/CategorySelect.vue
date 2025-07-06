<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        
        <select
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">{{ placeholder }}</option>
            <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
            >
                {{ category.name }}
            </option>
        </select>
        
        <p v-if="helpText" class="mt-1 text-xs text-gray-500">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Select a category...'
    },
    helpText: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const categories = ref([])

const fetchCategories = async () => {
    try {
        const response = await window.axios.get('/data/list-categories/all')
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching categories:', error)
        categories.value = []
    }
}

onMounted(() => {
    fetchCategories()
})
</script>