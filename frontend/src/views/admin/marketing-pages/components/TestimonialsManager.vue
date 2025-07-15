<template>
    <div class="space-y-4">
        <!-- Existing Testimonials -->
        <div v-for="(testimonial, index) in testimonials" :key="index" class="border rounded-lg p-4">
            <div class="flex justify-between items-start mb-3">
                <h4 class="text-sm font-medium text-gray-900">Testimonial {{ index + 1 }}</h4>
                <button
                    type="button"
                    @click="removeTestimonial(index)"
                    class="text-red-600 hover:text-red-900"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quote *</label>
                    <textarea
                        v-model="testimonial.quote"
                        rows="3"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="This is an amazing service..."
                    ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Author *</label>
                        <input
                            v-model="testimonial.author"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="John Doe"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Company</label>
                        <input
                            v-model="testimonial.company"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="ABC Corp (optional)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Testimonial -->
        <button
            v-if="testimonials.length < 10"
            type="button"
            @click="addTestimonial"
            class="w-full py-2 px-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-700"
        >
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Testimonial
        </button>

        <p v-if="testimonials.length >= 10" class="text-sm text-gray-500 text-center">
            Maximum of 10 testimonials allowed
        </p>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue'])

// State
const testimonials = ref(props.modelValue.length > 0 ? props.modelValue : [])

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
    testimonials.value = newVal.length > 0 ? newVal : []
})

// Watch for internal changes
watch(testimonials, (newVal) => {
    emit('update:modelValue', newVal)
}, { deep: true })

// Methods
const addTestimonial = () => {
    testimonials.value.push({
        quote: '',
        author: '',
        company: ''
    })
}

const removeTestimonial = (index) => {
    testimonials.value.splice(index, 1)
}
</script>