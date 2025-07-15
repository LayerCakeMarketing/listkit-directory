<template>
    <div>
        <!-- Selected Places -->
        <div v-if="selectedPlacesData.length > 0" class="mb-4 space-y-2">
            <div v-for="place in selectedPlacesData" :key="place.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                <span class="text-sm">{{ place.title }}</span>
                <button
                    type="button"
                    @click="removePlace(place.id)"
                    class="text-red-600 hover:text-red-900"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Input -->
        <div v-if="selectedPlacesData.length < max" class="relative">
            <input
                v-model="searchQuery"
                @input="searchPlaces"
                type="text"
                placeholder="Search places to add..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
            
            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md border border-gray-300 max-h-60 overflow-auto">
                <button
                    v-for="place in searchResults"
                    :key="place.id"
                    type="button"
                    @click="addPlace(place)"
                    class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"
                >
                    {{ place.title }}
                </button>
            </div>
        </div>

        <p v-if="selectedPlacesData.length >= max" class="mt-2 text-sm text-gray-500">
            Maximum of {{ max }} places can be selected
        </p>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    max: {
        type: Number,
        default: 5
    },
    selectedPlaces: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue'])

// State
const searchQuery = ref('')
const searchResults = ref([])
const selectedPlacesData = ref(props.selectedPlaces || [])

// Watch for external changes
watch(() => props.selectedPlaces, (newVal) => {
    selectedPlacesData.value = newVal || []
})

// Methods
const searchPlaces = debounce(async () => {
    if (!searchQuery.value) {
        searchResults.value = []
        return
    }

    try {
        const response = await axios.get('/api/admin/marketing-pages/special/home/places', {
            params: { search: searchQuery.value }
        })
        searchResults.value = response.data.data.filter(
            place => !props.modelValue.includes(place.id)
        )
    } catch (error) {
        console.error('Error searching places:', error)
    }
}, 300)

const addPlace = (place) => {
    if (props.modelValue.length < props.max) {
        const newValue = [...props.modelValue, place.id]
        emit('update:modelValue', newValue)
        selectedPlacesData.value.push(place)
        searchQuery.value = ''
        searchResults.value = []
    }
}

const removePlace = (placeId) => {
    const newValue = props.modelValue.filter(id => id !== placeId)
    emit('update:modelValue', newValue)
    selectedPlacesData.value = selectedPlacesData.value.filter(p => p.id !== placeId)
}
</script>