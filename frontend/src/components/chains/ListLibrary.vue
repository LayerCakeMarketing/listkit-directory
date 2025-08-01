<template>
  <div class="bg-white rounded-lg shadow">
    <div class="p-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">Available Lists</h3>
      <div class="mt-2">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search lists..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>
    </div>
    
    <div class="p-4 max-h-96 overflow-y-auto">
      <!-- Channel Lists Section -->
      <div v-if="channelLists.length > 0" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Channel Lists</h4>
        <div class="space-y-2">
          <div
            v-for="list in filteredChannelLists"
            :key="`channel-${list.id}`"
            draggable="true"
            @dragstart="$emit('drag-start', $event, list)"
            @dragend="$emit('drag-end')"
            :class="[
              'group relative p-3 bg-white border rounded-lg cursor-move transition-all',
              isListInChain(list.id) 
                ? 'border-indigo-200 bg-indigo-50 hover:shadow-md' 
                : 'border-gray-200 hover:shadow-md'
            ]"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate">{{ list.name }}</h4>
                <div class="flex items-center mt-1 space-x-2 text-xs text-gray-500">
                  <span>{{ list.items_count }} items</span>
                  <span>•</span>
                  <span class="capitalize">{{ list.visibility }}</span>
                  <span v-if="isListInChain(list.id)" class="text-indigo-600 font-medium">
                    • Used {{ getListUsageCount(list.id) }}x
                  </span>
                </div>
              </div>
              <button
                @click="$emit('add-to-chain', list)"
                class="ml-2 p-1 text-indigo-600 hover:bg-indigo-50 rounded"
                title="Add to current section"
              >
                <PlusIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Personal Lists Section -->
      <div v-if="personalLists.length > 0">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Your Personal Lists</h4>
        <div class="space-y-2">
          <div
            v-for="list in filteredPersonalLists"
            :key="`personal-${list.id}`"
            draggable="true"
            @dragstart="$emit('drag-start', $event, list)"
            @dragend="$emit('drag-end')"
            :class="[
              'group relative p-3 bg-white border rounded-lg cursor-move transition-all',
              isListInChain(list.id) 
                ? 'border-indigo-200 bg-indigo-50 hover:shadow-md' 
                : 'border-gray-200 hover:shadow-md'
            ]"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate">{{ list.name }}</h4>
                <div class="flex items-center mt-1 space-x-2 text-xs text-gray-500">
                  <span>{{ list.items_count }} items</span>
                  <span>•</span>
                  <span class="capitalize">{{ list.visibility }}</span>
                  <span v-if="isListInChain(list.id)" class="text-indigo-600 font-medium">
                    • Used {{ getListUsageCount(list.id) }}x
                  </span>
                </div>
              </div>
              <button
                @click="$emit('add-to-chain', list)"
                class="ml-2 p-1 text-indigo-600 hover:bg-indigo-50 rounded"
                title="Add to current section"
              >
                <PlusIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Loading lists...</p>
      </div>
      
      <div v-else-if="filteredChannelLists.length === 0 && filteredPersonalLists.length === 0" class="text-center py-8">
        <p class="text-sm text-gray-500">No lists found</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

/**
 * ListLibrary Component
 * 
 * Displays available lists (both channel and personal) with search functionality.
 * Lists can be dragged or added to the chain builder.
 * 
 * Props:
 * - channelLists: Array of lists belonging to the channel
 * - personalLists: Array of personal lists belonging to the user
 * - loading: Boolean indicating if lists are being loaded
 * - chainLists: Array of list IDs currently in the chain (for usage tracking)
 * 
 * Emits:
 * - drag-start: When a list drag operation starts
 * - drag-end: When a list drag operation ends
 * - add-to-chain: When the add button is clicked on a list
 */

const props = defineProps({
  channelLists: {
    type: Array,
    default: () => []
  },
  personalLists: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  chainLists: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['drag-start', 'drag-end', 'add-to-chain'])

const searchQuery = ref('')

// Filter lists based on search query
const filteredChannelLists = computed(() => {
  if (!searchQuery.value) return props.channelLists

  const query = searchQuery.value.toLowerCase()
  return props.channelLists.filter(list => 
    list.name.toLowerCase().includes(query) ||
    (list.description && list.description.toLowerCase().includes(query))
  )
})

const filteredPersonalLists = computed(() => {
  if (!searchQuery.value) return props.personalLists

  const query = searchQuery.value.toLowerCase()
  return props.personalLists.filter(list => 
    list.name.toLowerCase().includes(query) ||
    (list.description && list.description.toLowerCase().includes(query))
  )
})

// Check if a list is already in the chain
const isListInChain = (listId) => {
  return props.chainLists.includes(listId)
}

// Count how many times a list appears in the chain
const getListUsageCount = (listId) => {
  return props.chainLists.filter(id => id === listId).length
}
</script>