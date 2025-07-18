<template>
  <div class="relative">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    
    <div class="flex flex-wrap gap-2 mb-2" v-if="modelValue.length > 0">
      <div
        v-for="tag in modelValue"
        :key="tag.id"
        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm"
        :style="{ 
          backgroundColor: tag.color ? tag.color + '20' : '#E5E7EB',
          color: tag.color || '#374151'
        }"
      >
        <span>{{ tag.name }}</span>
        <button
          @click="removeTag(tag)"
          type="button"
          class="ml-1 hover:opacity-70 transition-opacity"
        >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <div class="relative">
      <input
        ref="input"
        v-model="search"
        @input="handleInput"
        @keydown.enter.prevent="handleEnter"
        @keydown.delete="handleBackspace"
        @focus="showSuggestions = true"
        @blur="handleBlur"
        type="text"
        :placeholder="placeholder"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
      />
      
      <!-- Loading indicator -->
      <div v-if="loading" class="absolute right-2 top-2">
        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
      </div>

      <!-- Suggestions dropdown -->
      <div
        v-if="showSuggestions && (suggestions.length > 0 || canCreateNew)"
        class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-60 overflow-auto"
      >
        <ul class="py-1">
          <li
            v-for="(suggestion, index) in suggestions"
            :key="suggestion.id"
            @mousedown.prevent="selectTag(suggestion)"
            :class="{
              'bg-blue-50': index === selectedIndex
            }"
            class="px-3 py-2 hover:bg-gray-50 cursor-pointer flex items-center justify-between"
          >
            <div class="flex items-center gap-2">
              <div
                class="w-3 h-3 rounded-full"
                :style="{ backgroundColor: suggestion.color || '#6B7280' }"
              ></div>
              <span class="text-sm">{{ suggestion.name }}</span>
            </div>
            <span class="text-xs text-gray-500">{{ suggestion.usage_count }} uses</span>
          </li>
          
          <!-- Create new option -->
          <li
            v-if="canCreateNew"
            @mousedown.prevent="createNewTag"
            :class="{
              'bg-blue-50': selectedIndex === suggestions.length
            }"
            class="px-3 py-2 hover:bg-gray-50 cursor-pointer text-blue-600 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="text-sm">Create "{{ search }}"</span>
          </li>
        </ul>
      </div>
    </div>

    <p v-if="helperText" class="mt-1 text-sm text-gray-500">
      {{ helperText }}
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
  label: String,
  placeholder: {
    type: String,
    default: 'Type to search or create tags...'
  },
  helperText: String,
  maxTags: {
    type: Number,
    default: 10
  },
  allowCreate: {
    type: Boolean,
    default: true
  },
  apiEndpoint: {
    type: String,
    default: '/api/tags/search'
  }
})

const emit = defineEmits(['update:modelValue'])

const search = ref('')
const suggestions = ref([])
const showSuggestions = ref(false)
const selectedIndex = ref(-1)
const loading = ref(false)
const input = ref(null)

const canCreateNew = computed(() => {
  return props.allowCreate && 
         search.value.length >= 2 && 
         !suggestions.value.some(s => s.name.toLowerCase() === search.value.toLowerCase()) &&
         props.modelValue.length < props.maxTags
})

const searchTags = debounce(async (query) => {
  if (query.length < 2) {
    suggestions.value = []
    return
  }

  loading.value = true
  try {
    const response = await axios.get(props.apiEndpoint, {
      params: { q: query }
    })
    suggestions.value = response.data.filter(tag => 
      !props.modelValue.some(selected => selected.id === tag.id)
    )
  } catch (error) {
    console.error('Error searching tags:', error)
    suggestions.value = []
  } finally {
    loading.value = false
  }
}, 300)

const handleInput = () => {
  selectedIndex.value = -1
  searchTags(search.value)
}

const selectTag = (tag) => {
  if (props.modelValue.length >= props.maxTags) {
    return
  }
  
  emit('update:modelValue', [...props.modelValue, tag])
  search.value = ''
  suggestions.value = []
  showSuggestions.value = false
  input.value?.focus()
}

const removeTag = (tag) => {
  emit('update:modelValue', props.modelValue.filter(t => t.id !== tag.id))
}

const createNewTag = async () => {
  if (!canCreateNew.value) return

  loading.value = true
  try {
    const response = await axios.post('/api/tags', {
      name: search.value
    })
    
    selectTag(response.data.tag)
  } catch (error) {
    if (error.response?.status === 422) {
      // Tag already exists, try to find it
      const existingTag = suggestions.value.find(s => 
        s.name.toLowerCase() === search.value.toLowerCase()
      )
      if (existingTag) {
        selectTag(existingTag)
      }
    } else {
      console.error('Error creating tag:', error)
    }
  } finally {
    loading.value = false
  }
}

const handleEnter = () => {
  if (selectedIndex.value >= 0 && selectedIndex.value < suggestions.value.length) {
    selectTag(suggestions.value[selectedIndex.value])
  } else if (canCreateNew.value) {
    createNewTag()
  }
}

const handleBackspace = () => {
  if (search.value === '' && props.modelValue.length > 0) {
    removeTag(props.modelValue[props.modelValue.length - 1])
  }
}

const handleBlur = () => {
  // Delay hiding to allow click events to register
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Keyboard navigation
watch(showSuggestions, (show) => {
  if (!show) {
    selectedIndex.value = -1
  }
})

// Handle arrow keys for navigation
if (typeof window !== 'undefined') {
  window.addEventListener('keydown', (e) => {
    if (!showSuggestions.value) return
    
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      selectedIndex.value = Math.min(
        selectedIndex.value + 1, 
        suggestions.value.length + (canCreateNew.value ? 0 : -1)
      )
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
    }
  })
}
</script>