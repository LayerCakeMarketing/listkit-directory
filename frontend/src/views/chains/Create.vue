<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <router-link
              to="/mylists"
              class="flex items-center text-gray-500 hover:text-gray-700"
            >
              <ChevronLeftIcon class="h-5 w-5 mr-1" />
              <span class="text-sm">Back to Lists</span>
            </router-link>
            <h1 class="ml-6 text-xl font-semibold text-gray-900">Create Chain</h1>
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="saveDraft"
              :disabled="!canSave || saving"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Save Draft
            </button>
            <button
              @click="createChain"
              :disabled="!canSubmit || saving"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ saving ? 'Creating...' : 'Create Chain' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex h-[calc(100vh-4rem)]">
      <!-- Left Column: Chain Details & List Library -->
      <div class="w-5/12 bg-white border-r border-gray-200 overflow-hidden flex flex-col">
        <!-- Chain Details Form -->
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900 mb-4">Chain Details</h2>
          <div class="space-y-4">
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
          </div>
        </div>

        <!-- List Library -->
        <div class="flex-1 flex flex-col overflow-hidden">
          <div class="p-4 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Available Lists</h3>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search your lists..."
                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            </div>
          </div>

          <!-- Lists -->
          <div class="flex-1 overflow-y-auto p-4">
            <div v-if="loading" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
              <p class="mt-2 text-sm text-gray-500">Loading lists...</p>
            </div>

            <div v-else-if="filteredLists.length === 0" class="text-center py-8">
              <p class="text-sm text-gray-500">No lists found</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="list in filteredLists"
                :key="list.id"
                draggable="true"
                @dragstart="startDragList($event, list)"
                @dragend="endDragList"
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
                    @click="addListToChain(list)"
                    class="ml-2 p-1 text-indigo-600 hover:bg-indigo-50 rounded"
                    title="Add to current section"
                  >
                    <PlusIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Chain Builder -->
      <div class="flex-1 bg-gray-50 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-gray-200 bg-white">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Chain Sequence</h2>
            <button
              @click="addSection"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100"
            >
              <FolderPlusIcon class="h-4 w-4 mr-1.5" />
              Add Section
            </button>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            Drag lists here to build your chain. Add sections to organize your sequence.
          </p>
        </div>

        <!-- Chain Builder Area -->
        <div class="flex-1 overflow-y-auto p-6">
          <div
            v-if="chainSections.length === 0"
            @drop="handleDropToEmpty($event)"
            @dragover.prevent
            @dragenter.prevent
            class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center"
          >
            <FolderPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-4 text-sm font-medium text-gray-900">No sections yet</h3>
            <p class="mt-2 text-sm text-gray-500">
              Add a section or drag lists here to get started
            </p>
          </div>

          <!-- Sections -->
          <div v-else class="space-y-6">
            <div
              v-for="(section, sectionIndex) in chainSections"
              :key="section.id"
              class="bg-white rounded-lg shadow-sm border border-gray-200"
            >
              <!-- Section Header -->
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <button
                    @click="toggleSection(section.id)"
                    class="p-1 hover:bg-gray-200 rounded"
                  >
                    <ChevronRightIcon
                      v-if="!section.expanded"
                      class="h-4 w-4 text-gray-500"
                    />
                    <ChevronDownIcon
                      v-else
                      class="h-4 w-4 text-gray-500"
                    />
                  </button>
                  <input
                    v-model="section.name"
                    type="text"
                    class="text-sm font-medium bg-transparent border-0 focus:ring-0 p-0"
                    :placeholder="`Section ${sectionIndex + 1}`"
                  />
                  <span class="text-xs text-gray-500">
                    ({{ section.lists.length }} lists)
                  </span>
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    v-if="sectionIndex > 0"
                    @click="moveSectionUp(sectionIndex)"
                    class="p-1 text-gray-400 hover:text-gray-600"
                  >
                    <ArrowUpIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="sectionIndex < chainSections.length - 1"
                    @click="moveSectionDown(sectionIndex)"
                    class="p-1 text-gray-400 hover:text-gray-600"
                  >
                    <ArrowDownIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="removeSection(sectionIndex)"
                    class="p-1 text-gray-400 hover:text-red-600"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- Section Content -->
              <div
                v-show="section.expanded"
                class="p-4"
              >
                <div
                  @drop="handleDropToSection($event, sectionIndex)"
                  @dragover.prevent
                  @dragenter.prevent
                  class="min-h-[100px]"
                  :class="{
                    'border-2 border-dashed border-gray-300 rounded-lg p-4': section.lists.length === 0
                  }"
                >
                  <p
                    v-if="section.lists.length === 0"
                    class="text-center text-sm text-gray-500"
                  >
                    Drag lists here
                  </p>

                  <!-- Lists in Section -->
                  <draggable
                    v-else
                    v-model="section.lists"
                    :group="{ name: 'lists', pull: true, put: true }"
                    item-key="id"
                    handle=".drag-handle"
                    class="space-y-3"
                  >
                    <template #item="{ element: list, index }">
                      <div class="group relative flex items-start bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="drag-handle cursor-move p-1 mr-3">
                          <Bars3Icon class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <h4 class="text-sm font-medium text-gray-900">{{ list.name }}</h4>
                          <p v-if="list.customDescription" class="mt-1 text-xs text-gray-600">
                            {{ list.customDescription }}
                          </p>
                          <button
                            @click="editListDescription(sectionIndex, index)"
                            class="mt-1 text-xs text-indigo-600 hover:text-indigo-700"
                          >
                            {{ list.customDescription ? 'Edit' : 'Add' }} description
                          </button>
                        </div>
                        <button
                          @click="removeListFromSection(sectionIndex, index)"
                          class="ml-2 p-1 text-gray-400 hover:text-red-600"
                        >
                          <XMarkIcon class="h-4 w-4" />
                        </button>
                      </div>
                    </template>
                  </draggable>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Footer -->
        <div class="p-4 bg-white border-t border-gray-200">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">
              Total: {{ totalListsCount }} lists in {{ chainSections.length }} sections
            </span>
            <span
              v-if="!canSubmit"
              class="text-red-600"
            >
              Add at least 2 lists to create a chain
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Description Modal -->
    <TransitionRoot as="template" :show="editModal.show">
      <Dialog as="div" class="relative z-50" @close="editModal.show = false">
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
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                  <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                      Edit List Description
                    </h3>
                    <div class="mt-4">
                      <label class="block text-sm font-medium text-gray-700">
                        Custom description for this list in the chain
                      </label>
                      <textarea
                        v-model="editModal.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Add context for this list in your chain..."
                      />
                    </div>
                  </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button
                    type="button"
                    @click="saveListDescription"
                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                  >
                    Save
                  </button>
                  <button
                    type="button"
                    @click="editModal.show = false"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDownIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  FolderPlusIcon,
  XMarkIcon,
  TrashIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  Bars3Icon,
} from '@heroicons/vue/24/outline'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { showSuccess, showError } = useNotification()

// Form data
const form = ref({
  name: '',
  description: '',
  visibility: 'private',
  status: 'draft',
  cover_image: null,
  cover_cloudflare_id: null
})

// List management
const loading = ref(false)
const saving = ref(false)
const lists = ref([])
const searchQuery = ref('')
const draggedList = ref(null)

// Chain sections
const chainSections = ref([])
let sectionIdCounter = 1

// Edit modal
const editModal = ref({
  show: false,
  sectionIndex: null,
  listIndex: null,
  description: ''
})

// Computed properties
const filteredLists = computed(() => {
  if (!searchQuery.value) return lists.value

  const query = searchQuery.value.toLowerCase()
  return lists.value.filter(list => 
    list.name.toLowerCase().includes(query) ||
    (list.description && list.description.toLowerCase().includes(query))
  )
})

const totalListsCount = computed(() => {
  return chainSections.value.reduce((total, section) => total + section.lists.length, 0)
})

const canSave = computed(() => {
  return form.value.name && totalListsCount.value >= 1
})

const canSubmit = computed(() => {
  return form.value.name && totalListsCount.value >= 2
})

// Check if a list is already in the chain
const isListInChain = (listId) => {
  return chainSections.value.some(section => 
    section.lists.some(list => list.id === listId)
  )
}

// Count how many times a list appears in the chain
const getListUsageCount = (listId) => {
  let count = 0
  chainSections.value.forEach(section => {
    count += section.lists.filter(list => list.id === listId).length
  })
  return count
}

// Section management
const addSection = () => {
  chainSections.value.push({
    id: `section-${sectionIdCounter++}`,
    name: '',
    expanded: true,
    lists: []
  })
}

const removeSection = (index) => {
  if (confirm('Remove this section and all its lists?')) {
    chainSections.value.splice(index, 1)
  }
}

const toggleSection = (sectionId) => {
  const section = chainSections.value.find(s => s.id === sectionId)
  if (section) {
    section.expanded = !section.expanded
  }
}

const moveSectionUp = (index) => {
  if (index > 0) {
    const temp = chainSections.value[index]
    chainSections.value[index] = chainSections.value[index - 1]
    chainSections.value[index - 1] = temp
  }
}

const moveSectionDown = (index) => {
  if (index < chainSections.value.length - 1) {
    const temp = chainSections.value[index]
    chainSections.value[index] = chainSections.value[index + 1]
    chainSections.value[index + 1] = temp
  }
}

// List management
const addListToChain = (list) => {
  // Allow adding the same list multiple times
  
  // If no sections exist, create one
  if (chainSections.value.length === 0) {
    addSection()
  }

  // Add to the last section (or the first section if only one exists)
  const targetSection = chainSections.value[chainSections.value.length - 1]
  targetSection.lists.push({
    ...list,
    customDescription: ''
  })
}

const removeListFromSection = (sectionIndex, listIndex) => {
  chainSections.value[sectionIndex].lists.splice(listIndex, 1)
}

// Drag and drop handlers
const startDragList = (event, list) => {
  draggedList.value = list
  event.dataTransfer.effectAllowed = 'copy'
}

const endDragList = () => {
  draggedList.value = null
}

const handleDropToEmpty = (event) => {
  event.preventDefault()
  if (draggedList.value) {
    addSection()
    chainSections.value[0].lists.push({
      ...draggedList.value,
      customDescription: ''
    })
  }
}

const handleDropToSection = (event, sectionIndex) => {
  event.preventDefault()
  if (draggedList.value) {
    chainSections.value[sectionIndex].lists.push({
      ...draggedList.value,
      customDescription: ''
    })
  }
}

// Edit list description
const editListDescription = (sectionIndex, listIndex) => {
  const list = chainSections.value[sectionIndex].lists[listIndex]
  editModal.value = {
    show: true,
    sectionIndex,
    listIndex,
    description: list.customDescription || ''
  }
}

const saveListDescription = () => {
  const { sectionIndex, listIndex, description } = editModal.value
  chainSections.value[sectionIndex].lists[listIndex].customDescription = description
  editModal.value.show = false
}

// Save/Create chain
const saveDraft = async () => {
  if (!canSave.value) return
  await saveChain('draft')
}

const createChain = async () => {
  if (!canSubmit.value) return
  await saveChain('published')
}

const saveChain = async (status) => {
  saving.value = true
  try {
    // Flatten all lists from sections with proper labels
    const allLists = []
    chainSections.value.forEach((section, sectionIndex) => {
      const sectionLabel = section.name || `Section ${sectionIndex + 1}`
      section.lists.forEach((list, listIndex) => {
        allLists.push({
          list_id: list.id,
          label: sectionLabel + (list.customDescription ? '' : ` - ${list.name}`),
          description: list.customDescription || null
        })
      })
    })

    // Prepare the chain data
    const chainData = {
      ...form.value,
      status,
      lists: allLists,
      metadata: {
        sections: chainSections.value.map(section => ({
          name: section.name,
          listCount: section.lists.length
        }))
      }
    }

    console.log('Sending chain data:', chainData)
    const response = await axios.post('/api/chains', chainData)
    
    showSuccess(status === 'draft' ? 'Chain saved as draft!' : 'Chain created successfully!')
    
    // Navigate to the chain view
    router.push(`/chains/${response.data.chain.id}`)
  } catch (error) {
    console.error('Error saving chain:', error)
    console.error('Error response:', error.response?.data)
    
    // Show validation errors if present
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0]
      showError(Array.isArray(firstError) ? firstError[0] : firstError)
    } else {
      showError(error.response?.data?.message || 'Failed to save chain')
    }
  } finally {
    saving.value = false
  }
}

// Fetch user's lists
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
    lists.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching lists:', error)
    showError('Failed to load lists')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLists()
  // Start with one empty section
  addSection()
})
</script>

<style scoped>
/* Custom styles for drag and drop */
.drag-handle {
  cursor: move;
}

.ghost-class {
  opacity: 0.5;
}

.drag-class {
  transform: rotate(2deg);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
</style>