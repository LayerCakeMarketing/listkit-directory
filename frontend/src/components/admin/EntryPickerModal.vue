<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="relative inline-block w-full max-w-2xl text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle">
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Select Entries to Feature</h3>
          
          <!-- Search -->
          <div class="mb-4">
            <input
              v-model="search"
              type="text"
              placeholder="Search entries..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
          </div>

          <!-- Entries List -->
          <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
            <div
              v-for="entry in filteredEntries"
              :key="entry.id"
              @click="toggleEntry(entry)"
              :class="[
                'p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50',
                isSelected(entry.id) ? 'bg-blue-50' : ''
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">{{ entry.title }}</h4>
                  <p class="text-sm text-gray-500">{{ entry.type }}</p>
                </div>
                <div v-if="isSelected(entry.id)" class="text-blue-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="confirmSelection"
            type="button"
            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Add Selected
          </button>
          <button
            @click="$emit('close')"
            type="button"
            class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  entries: Array,
  selected: Array
})

const emit = defineEmits(['select', 'close'])

const search = ref('')
const selectedEntries = ref([])

const filteredEntries = computed(() => {
  if (!search.value) return props.entries
  
  const searchLower = search.value.toLowerCase()
  return props.entries.filter(entry => 
    entry.title.toLowerCase().includes(searchLower)
  )
})

function isSelected(entryId) {
  return selectedEntries.value.includes(entryId) || props.selected.includes(entryId)
}

function toggleEntry(entry) {
  if (props.selected.includes(entry.id)) return // Already featured
  
  const index = selectedEntries.value.indexOf(entry.id)
  if (index > -1) {
    selectedEntries.value.splice(index, 1)
  } else {
    selectedEntries.value.push(entry.id)
  }
}

function confirmSelection() {
  selectedEntries.value.forEach(entryId => {
    const entry = props.entries.find(e => e.id === entryId)
    if (entry) {
      emit('select', entry)
    }
  })
  emit('close')
}
</script>