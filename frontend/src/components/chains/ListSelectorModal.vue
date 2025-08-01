<template>
  <Teleport to="body">
    <TransitionRoot as="template" :show="show">
      <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-900 bg-opacity-80 transition-opacity backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl ring-1 ring-black ring-opacity-5">
              <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                      Select Lists for Chain
                    </DialogTitle>
                    
                    <!-- Search -->
                    <div class="mt-4">
                      <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search your lists..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      />
                    </div>

                    <!-- Lists -->
                    <div class="mt-4 max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                      <div v-if="loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-500">Loading lists...</p>
                      </div>

                      <div v-else-if="filteredLists.length === 0" class="text-center py-8">
                        <p class="text-sm text-gray-500">No lists found</p>
                      </div>

                      <div v-else class="divide-y divide-gray-100">
                        <label
                          v-for="list in filteredLists"
                          :key="list.id"
                          class="flex items-center p-4 cursor-pointer transition-colors"
                          :class="{
                            'bg-indigo-50 hover:bg-indigo-100': isSelected(list.id),
                            'hover:bg-gray-50': !isSelected(list.id),
                            'opacity-50 cursor-not-allowed': isAlreadyInChain(list.id)
                          }"
                        >
                          <input
                            type="checkbox"
                            :checked="isSelected(list.id)"
                            :disabled="isAlreadyInChain(list.id)"
                            @change="toggleList(list)"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded disabled:opacity-50"
                          />
                          <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ list.name }}</p>
                            <div class="flex items-center space-x-2 mt-1">
                              <span class="text-xs text-gray-500">
                                {{ list.items_count }} {{ list.items_count === 1 ? 'item' : 'items' }}
                              </span>
                              <span class="text-xs text-gray-300">•</span>
                              <span class="text-xs px-1.5 py-0.5 rounded-full"
                                :class="{
                                  'bg-green-100 text-green-700': list.visibility === 'public',
                                  'bg-gray-100 text-gray-700': list.visibility === 'private',
                                  'bg-yellow-100 text-yellow-700': list.visibility === 'unlisted'
                                }"
                              >
                                {{ list.visibility }}
                              </span>
                            </div>
                          </div>
                          <span
                            v-if="isAlreadyInChain(list.id)"
                            class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded"
                          >
                            Already in chain
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button
                  type="button"
                  @click.stop="handleSelect($event)"
                  :disabled="tempSelected.length === 0"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Add {{ tempSelected.length }} List{{ tempSelected.length !== 1 ? 's' : '' }}
                </button>
                <button
                  type="button"
                  @click.stop="close($event)"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                  Cancel
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  selectedLists: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'select'])

const loading = ref(false)
const lists = ref([])
const searchQuery = ref('')
const tempSelected = ref([])

const filteredLists = computed(() => {
  if (!searchQuery.value) return lists.value

  const query = searchQuery.value.toLowerCase()
  return lists.value.filter(list => 
    list.name.toLowerCase().includes(query) ||
    (list.description && list.description.toLowerCase().includes(query))
  )
})

const isSelected = (listId) => {
  return tempSelected.value.some(list => list.id === listId)
}

const isAlreadyInChain = (listId) => {
  return props.selectedLists.some(list => list.id === listId)
}

const toggleList = (list) => {
  const index = tempSelected.value.findIndex(l => l.id === list.id)
  if (index > -1) {
    tempSelected.value.splice(index, 1)
  } else {
    tempSelected.value.push(list)
  }
}

const close = (event) => {
  // Prevent event bubbling if called from a click event
  if (event && typeof event.stopPropagation === 'function') {
    event.stopPropagation()
  }
  
  // Reset temporary selection when closing
  tempSelected.value = []
  emit('close')
}

const handleSelect = (event) => {
  // Prevent event bubbling
  if (event) {
    event.stopPropagation()
    event.preventDefault()
  }
  
  // Emit the selected lists
  emit('select', [...tempSelected.value])
  
  // Clear temporary selection
  tempSelected.value = []
  
  // The parent will handle closing by setting showListSelector = false
}

const fetchLists = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/lists', {
      params: {
        per_page: 100,
        sort: 'created_at',
        order: 'desc'
      }
    })
    lists.value = response.data.data
  } catch (error) {
    console.error('Error fetching lists:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLists()
})
</script>