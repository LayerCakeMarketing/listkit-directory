<template>
  <button
    @click="toggleSave"
    :disabled="loading"
    :class="[
      'inline-flex items-center px-3 py-1.5 border rounded-md text-sm font-medium transition-colors',
      isSaved
        ? 'border-blue-600 bg-blue-50 text-blue-700 hover:bg-blue-100'
        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
      loading && 'opacity-50 cursor-not-allowed'
    ]"
    :title="isSaved ? 'Unsave' : 'Save'"
  >
    <svg
      :class="['w-4 h-4 mr-1.5', loading && 'animate-spin']"
      :fill="isSaved ? 'currentColor' : 'none'"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        v-if="!loading"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
      />
      <path
        v-else
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>
    <span>{{ isSaved ? 'Saved' : 'Save' }}</span>
  </button>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
// import { useToast } from 'vue-toastification'

const props = defineProps({
  itemType: {
    type: String,
    required: true,
    validator: (value) => ['place', 'list', 'region'].includes(value)
  },
  itemId: {
    type: [String, Number],
    required: true
  },
  initialSaved: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['saved', 'unsaved'])

const authStore = useAuthStore()
// const toast = useToast()

// Simple toast alternative
const showToast = (message, type = 'success') => {
  alert(message)
}

const isSaved = ref(props.initialSaved)
const loading = ref(false)

const isAuthenticated = computed(() => authStore.isAuthenticated)

// Check if item is saved on mount
onMounted(async () => {
  if (!isAuthenticated.value) return
  
  try {
    const response = await axios.post('/api/saved-items/check', {
      items: [{ type: props.itemType, id: props.itemId }]
    })
    
    if (response.data.items && response.data.items.length > 0) {
      isSaved.value = response.data.items[0].is_saved
    }
  } catch (error) {
    console.error('Error checking saved status:', error)
  }
})

async function toggleSave() {
  if (!isAuthenticated.value) {
    showToast('Please login to save items', 'warning')
    return
  }
  
  loading.value = true
  
  try {
    if (isSaved.value) {
      // Unsave item
      await axios.delete(`/api/saved-items/${props.itemType}/${props.itemId}`)
      isSaved.value = false
      showToast('Item removed from saved')
      emit('unsaved')
    } else {
      // Save item
      await axios.post('/api/saved-items', {
        type: props.itemType,
        id: props.itemId
      })
      isSaved.value = true
      showToast('Item saved successfully')
      emit('saved')
    }
  } catch (error) {
    console.error('Error toggling save:', error)
    showToast(error.response?.data?.message || 'Failed to save item', 'error')
  } finally {
    loading.value = false
  }
}
</script>