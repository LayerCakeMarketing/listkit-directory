<template>
  <TransitionRoot as="template" :show="show">
    <Dialog 
      as="div" 
      class="relative z-40" 
      @close="close"
      :static="showListSelector"
    >
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
              <form @submit.prevent="handleSubmit">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                  <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                      <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                        Create List Chain
                      </DialogTitle>
                      <p class="mt-2 text-sm text-gray-500">
                        Combine multiple lists into a sequence, perfect for multi-day itineraries, step-by-step guides, or progressive plans.
                      </p>

                      <div class="mt-6 space-y-6">
                        <!-- Chain Name -->
                        <div>
                          <label for="chain-name" class="block text-sm font-medium text-gray-700">
                            Chain Name
                          </label>
                          <input
                            id="chain-name"
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="e.g., 7-Day Italy Trip, Marathon Training Plan"
                          />
                        </div>

                        <!-- Description -->
                        <div>
                          <label for="chain-description" class="block text-sm font-medium text-gray-700">
                            Description
                          </label>
                          <textarea
                            id="chain-description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Describe what this chain is about..."
                          />
                        </div>

                        <!-- Visibility -->
                        <div>
                          <label for="chain-visibility" class="block text-sm font-medium text-gray-700">
                            Visibility
                          </label>
                          <select
                            id="chain-visibility"
                            v-model="form.visibility"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          >
                            <option value="private">Private - Only you can see</option>
                            <option value="unlisted">Unlisted - Anyone with link can view</option>
                            <option value="public">Public - Anyone can find and view</option>
                          </select>
                        </div>

                        <!-- Selected Lists -->
                        <div>
                          <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                              Lists in Chain ({{ selectedLists.length }} selected)
                            </label>
                            <button
                              type="button"
                              @click.stop="showListSelector = true"
                              class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500 font-medium"
                            >
                              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                              </svg>
                              Add Lists
                            </button>
                          </div>

                          <!-- Draggable List -->
                          <div v-if="selectedLists.length > 0" class="space-y-3">
                            <draggable
                              v-model="selectedLists"
                              :item-key="(item) => item.id"
                              handle=".drag-handle"
                              class="space-y-3"
                              :animation="200"
                              ghost-class="opacity-50"
                              drag-class="shadow-lg"
                            >
                              <template #item="{ element: list, index }">
                                <div 
                                  class="group relative flex items-start space-x-3 bg-white border-2 border-gray-200 p-4 rounded-lg transition-all hover:border-indigo-300 hover:shadow-md"
                                  :class="{ 'ring-2 ring-indigo-500 ring-offset-2': draggedIndex === index }"
                                >
                                  <!-- Order indicator and drag handle -->
                                  <div class="flex flex-col items-center">
                                    <div class="drag-handle cursor-move p-1 rounded hover:bg-gray-100 transition-colors group-hover:text-indigo-600">
                                      <svg class="h-6 w-6 text-gray-400 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                      </svg>
                                    </div>
                                    <span class="mt-2 flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm">
                                      {{ index + 1 }}
                                    </span>
                                  </div>
                                  
                                  <!-- Content -->
                                  <div class="flex-1 min-w-0">
                                    <div class="mb-3">
                                      <label class="block text-xs font-medium text-gray-500 mb-1">Step Label</label>
                                      <input
                                        v-model="list.label"
                                        type="text"
                                        class="w-full text-sm font-medium border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                        :placeholder="`e.g., Day ${index + 1}, Step ${index + 1}, Part ${index + 1}`"
                                      />
                                    </div>
                                    
                                    <div class="mb-3">
                                      <label class="block text-xs font-medium text-gray-500 mb-1">List</label>
                                      <p class="text-sm font-medium text-gray-900 bg-gray-50 px-3 py-1.5 rounded">{{ list.name }}</p>
                                    </div>
                                    
                                    <div>
                                      <label class="block text-xs font-medium text-gray-500 mb-1">Description (optional)</label>
                                      <textarea
                                        v-model="list.description"
                                        rows="2"
                                        class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                        placeholder="Brief description of what this step covers..."
                                      />
                                    </div>
                                  </div>
                                  
                                  <!-- Actions -->
                                  <div class="flex flex-col items-center space-y-2">
                                    <button
                                      type="button"
                                      @click="removeList(index)"
                                      class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-all"
                                      title="Remove from chain"
                                    >
                                      <XMarkIcon class="h-5 w-5" />
                                    </button>
                                    
                                    <!-- Visual indicators -->
                                    <div class="flex flex-col items-center space-y-1">
                                      <button
                                        v-if="index > 0"
                                        type="button"
                                        @click="moveUp(index)"
                                        class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-all"
                                        title="Move up"
                                      >
                                        <ChevronUpIcon class="h-4 w-4" />
                                      </button>
                                      <button
                                        v-if="index < selectedLists.length - 1"
                                        type="button"
                                        @click="moveDown(index)"
                                        class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-all"
                                        title="Move down"
                                      >
                                        <ChevronDownIcon class="h-4 w-4" />
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              </template>
                            </draggable>
                            
                            <!-- Connection lines between items -->
                            <div class="text-center text-xs text-gray-500 italic">
                              <span class="bg-white px-2">Drag to reorder or use arrows</span>
                            </div>
                          </div>
                          <div v-else class="text-center py-8 bg-gray-50 rounded-md">
                            <FolderPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-gray-600">
                              Add at least 2 lists to create a chain
                            </p>
                          </div>

                          <p v-if="selectedLists.length === 1" class="mt-2 text-sm text-red-600">
                            A chain requires at least 2 lists
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button
                    type="submit"
                    :disabled="!canSubmit || saving"
                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="saving">Creating...</span>
                    <span v-else>Create Chain</span>
                  </button>
                  <button
                    type="button"
                    @click="close"
                    :disabled="saving"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto disabled:opacity-50"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- List Selector Modal -->
  <Teleport to="body">
    <ListSelectorModal
      v-if="showListSelector"
      :show="showListSelector"
      :selected-lists="selectedLists"
      @close="showListSelector = false"
      @select="handleListSelect"
    />
  </Teleport>
  
  <!-- Import styles -->
  <ChainCreationStyles />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  Bars3Icon,
  FolderPlusIcon,
  ChevronUpIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'
import ListSelectorModal from './ListSelectorModal.vue'
import ChainCreationStyles from './ChainCreationStyles.vue'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['close', 'created'])

const router = useRouter()
const { showSuccess, showError } = useNotification()

const form = ref({
  name: '',
  description: '',
  visibility: 'private',
  status: 'draft'
})

const selectedLists = ref([])
const showListSelector = ref(false)
const saving = ref(false)
const draggedIndex = ref(null)

const canSubmit = computed(() => {
  return form.value.name && selectedLists.value.length >= 2
})

const handleListSelect = (lists) => {
  // Add new lists with default label
  lists.forEach(list => {
    if (!selectedLists.value.find(l => l.id === list.id)) {
      selectedLists.value.push({
        ...list,
        label: '',
        description: ''
      })
    }
  })
  // Close the list selector modal
  showListSelector.value = false
}

const removeList = (index) => {
  selectedLists.value.splice(index, 1)
}

const moveUp = (index) => {
  if (index > 0) {
    const temp = selectedLists.value[index]
    selectedLists.value[index] = selectedLists.value[index - 1]
    selectedLists.value[index - 1] = temp
  }
}

const moveDown = (index) => {
  if (index < selectedLists.value.length - 1) {
    const temp = selectedLists.value[index]
    selectedLists.value[index] = selectedLists.value[index + 1]
    selectedLists.value[index + 1] = temp
  }
}

const close = () => {
  // Don't close if list selector is open or if saving
  if (!saving.value && !showListSelector.value) {
    emit('close')
  }
}

const handleSubmit = async () => {
  if (!canSubmit.value || saving.value) return

  saving.value = true
  try {
    const payload = {
      ...form.value,
      lists: selectedLists.value.map((list, index) => ({
        list_id: list.id,
        label: list.label || `Step ${index + 1}`,
        description: list.description || null
      }))
    }

    const response = await axios.post('/api/chains', payload)
    
    showSuccess('Chain created successfully!')
    emit('created', response.data.chain)
    emit('close')
    
    // Navigate to the chain view
    router.push(`/chains/${response.data.chain.id}`)
  } catch (error) {
    console.error('Error creating chain:', error)
    showError(error.response?.data?.message || 'Failed to create chain')
  } finally {
    saving.value = false
  }
}
</script>