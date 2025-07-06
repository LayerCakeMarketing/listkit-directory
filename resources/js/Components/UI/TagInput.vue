<template>
    <div class="relative">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        
        <!-- Selected Tags Display -->
        <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-2 mb-2">
            <span 
                v-for="tag in selectedTags" 
                :key="tag.id"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                :style="{ backgroundColor: tag.color }"
            >
                {{ tag.name }}
                <button
                    @click="removeTag(tag)"
                    type="button"
                    class="ml-2 inline-flex items-center justify-center w-4 h-4 text-white hover:text-gray-200"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        </div>
        
        <!-- Input Field -->
        <div class="relative">
            <input
                ref="input"
                v-model="searchQuery"
                @input="handleInput"
                @keydown="handleKeydown"
                @focus="showDropdown = true"
                @blur="handleBlur"
                type="text"
                :placeholder="placeholder"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            
            <!-- Dropdown -->
            <div
                v-if="showDropdown && (searchResults.length > 0 || searchQuery.length > 0)"
                class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
            >
                <!-- Search Results -->
                <div
                    v-for="(tag, index) in searchResults"
                    :key="tag.id"
                    @mousedown="selectTag(tag)"
                    :class="[
                        'px-3 py-2 cursor-pointer flex items-center space-x-2',
                        index === highlightedIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'
                    ]"
                >
                    <div 
                        class="w-3 h-3 rounded-full" 
                        :style="{ backgroundColor: tag.color }"
                    ></div>
                    <span class="text-sm">{{ tag.name }}</span>
                </div>
                
                <!-- Create New Tag Option -->
                <div
                    v-if="searchQuery.length > 0 && !tagExists && allowCreate"
                    @mousedown="createTag"
                    :class="[
                        'px-3 py-2 cursor-pointer flex items-center space-x-2 border-t border-gray-200',
                        searchResults.length === highlightedIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'
                    ]"
                >
                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                    <span class="text-sm">Create "{{ searchQuery }}"</span>
                </div>
                
                <!-- No Results -->
                <div
                    v-if="searchResults.length === 0 && searchQuery.length > 0 && !allowCreate"
                    class="px-3 py-2 text-sm text-gray-500"
                >
                    No tags found
                </div>
            </div>
        </div>
        
        <!-- Help Text -->
        <p v-if="helpText" class="mt-1 text-xs text-gray-500">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { debounce } from 'lodash'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Search or create tags...'
    },
    helpText: {
        type: String,
        default: 'Type to search existing tags or create new ones'
    },
    allowCreate: {
        type: Boolean,
        default: true
    },
    maxTags: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['update:modelValue'])

// Local state
const searchQuery = ref('')
const searchResults = ref([])
const showDropdown = ref(false)
const highlightedIndex = ref(-1)
const input = ref(null)

// Computed
const selectedTags = computed({
    get: () => props.modelValue || [],
    set: (value) => emit('update:modelValue', value)
})

const tagExists = computed(() => {
    return searchResults.value.some(tag => 
        tag.name.toLowerCase() === searchQuery.value.toLowerCase()
    )
})

// Methods
const searchTags = async (query) => {
    if (query.length < 2) {
        searchResults.value = []
        return
    }
    
    try {
        const response = await window.axios.get('/data/tags/search', {
            params: { q: query }
        })
        
        // Filter out already selected tags
        const selectedTagIds = selectedTags.value.map(tag => tag.id)
        searchResults.value = response.data.filter(tag => 
            !selectedTagIds.includes(tag.id)
        )
    } catch (error) {
        console.error('Error searching tags:', error)
        searchResults.value = []
    }
}

const debouncedSearch = debounce(searchTags, 300)

const handleInput = () => {
    highlightedIndex.value = -1
    debouncedSearch(searchQuery.value)
}

const handleKeydown = (event) => {
    const totalOptions = searchResults.value.length + (searchQuery.value.length > 0 && !tagExists.value && props.allowCreate ? 1 : 0)
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault()
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, totalOptions - 1)
            break
            
        case 'ArrowUp':
            event.preventDefault()
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
            break
            
        case 'Enter':
            event.preventDefault()
            if (highlightedIndex.value >= 0) {
                if (highlightedIndex.value < searchResults.value.length) {
                    selectTag(searchResults.value[highlightedIndex.value])
                } else if (props.allowCreate && searchQuery.value.length > 0 && !tagExists.value) {
                    createTag()
                }
            }
            break
            
        case 'Escape':
            showDropdown.value = false
            highlightedIndex.value = -1
            input.value?.blur()
            break
    }
}

const handleBlur = () => {
    // Delay hiding dropdown to allow for click events
    setTimeout(() => {
        showDropdown.value = false
        highlightedIndex.value = -1
    }, 200)
}

const selectTag = (tag) => {
    if (props.maxTags && selectedTags.value.length >= props.maxTags) {
        return
    }
    
    // Check if tag is already selected
    if (selectedTags.value.some(selected => selected.id === tag.id)) {
        return
    }
    
    selectedTags.value = [...selectedTags.value, tag]
    searchQuery.value = ''
    searchResults.value = []
    showDropdown.value = false
    
    nextTick(() => {
        input.value?.focus()
    })
}

const createTag = async () => {
    if (!props.allowCreate || !searchQuery.value.trim()) return
    
    if (props.maxTags && selectedTags.value.length >= props.maxTags) {
        return
    }
    
    const tagName = searchQuery.value.trim()
    
    // Check for profanity
    try {
        const response = await window.axios.post('/data/tags/validate', { name: tagName })
        if (!response.data.valid) {
            alert('This tag contains inappropriate content and cannot be created.')
            return
        }
    } catch (error) {
        console.error('Error validating tag:', error)
        alert('Error validating tag. Please try again.')
        return
    }
    
    // Create a temporary tag object
    const newTag = {
        id: `temp-${Date.now()}`, // Temporary ID
        name: tagName,
        slug: tagName.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
        color: '#6B7280', // Default color
        is_new: true // Flag to indicate this is a new tag
    }
    
    selectedTags.value = [...selectedTags.value, newTag]
    searchQuery.value = ''
    searchResults.value = []
    showDropdown.value = false
    
    nextTick(() => {
        input.value?.focus()
    })
}

const removeTag = (tagToRemove) => {
    selectedTags.value = selectedTags.value.filter(tag => tag.id !== tagToRemove.id)
}

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (newQuery.length === 0) {
        searchResults.value = []
        showDropdown.value = false
    }
})
</script>

<style scoped>
/* Custom scrollbar for dropdown */
.overflow-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>