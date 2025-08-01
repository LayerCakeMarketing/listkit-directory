<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Edit Form -->
      <div v-else-if="chain">
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Edit Chain</h1>
          </div>

          <form @submit.prevent="saveChain" class="p-6 space-y-6">
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

            <!-- Status -->
            <div>
              <label for="chain-status" class="block text-sm font-medium text-gray-700">
                Status
              </label>
              <select
                id="chain-status"
                v-model="form.status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
            </div>

            <!-- Lists -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700">
                  Lists in Chain ({{ selectedLists.length }})
                </label>
                <button
                  type="button"
                  @click="showListSelector = true"
                  class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                  Add Lists
                </button>
              </div>

              <!-- Draggable List -->
              <div v-if="selectedLists.length > 0" class="space-y-2">
                <draggable
                  v-model="selectedLists"
                  :item-key="(item) => item.id"
                  handle=".drag-handle"
                  class="space-y-2"
                >
                  <template #item="{ element: list, index }">
                    <div class="flex items-center space-x-3 bg-gray-50 p-3 rounded-md">
                      <div class="drag-handle cursor-move">
                        <Bars3Icon class="h-5 w-5 text-gray-400" />
                      </div>
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <span class="text-sm font-medium text-gray-900">
                            {{ index + 1 }}.
                          </span>
                          <input
                            v-model="list.pivot.label"
                            type="text"
                            class="flex-1 text-sm border-gray-300 rounded-md"
                            :placeholder="`Label (e.g., Day ${index + 1})`"
                          />
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ list.name }}</p>
                        <input
                          v-model="list.pivot.description"
                          type="text"
                          class="mt-1 w-full text-xs border-gray-300 rounded-md"
                          placeholder="Optional description for this step..."
                        />
                      </div>
                      <button
                        type="button"
                        @click="removeList(index)"
                        class="text-red-600 hover:text-red-700"
                      >
                        <XMarkIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </template>
                </draggable>
              </div>
              <div v-else class="text-center py-8 bg-gray-50 rounded-md">
                <p class="text-sm text-gray-600">
                  Add at least 2 lists to create a chain
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
              <router-link
                :to="`/chains/${chain.id}`"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Cancel
              </router-link>
              <button
                type="submit"
                :disabled="!canSave || saving"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="text-center py-12">
        <h3 class="text-lg font-medium text-gray-900">Chain not found</h3>
        <p class="mt-2 text-sm text-gray-500">
          The chain you're looking for doesn't exist or you don't have permission to edit it.
        </p>
        <router-link
          to="/mylists"
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200"
        >
          Back to My Lists
        </router-link>
      </div>
    </div>

    <!-- List Selector Modal -->
    <ListSelectorModal
      v-if="showListSelector"
      :show="showListSelector"
      :selected-lists="selectedLists"
      @close="showListSelector = false"
      @select="handleListSelect"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import draggable from 'vuedraggable'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import ListSelectorModal from '@/components/chains/ListSelectorModal.vue'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showSuccess, showError } = useNotification()

const chain = ref(null)
const loading = ref(true)
const saving = ref(false)
const showListSelector = ref(false)
const selectedLists = ref([])

const form = reactive({
  name: '',
  description: '',
  visibility: 'private',
  status: 'draft'
})

const canSave = computed(() => {
  return form.name && selectedLists.value.length >= 2
})

const fetchChain = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/chains/${props.id}`)
    chain.value = response.data
    
    // Check permissions
    if (chain.value.owner_type !== 'App\\Models\\User' || 
        chain.value.owner_id !== authStore.user?.id) {
      chain.value = null
      return
    }
    
    // Populate form
    form.name = chain.value.name
    form.description = chain.value.description || ''
    form.visibility = chain.value.visibility
    form.status = chain.value.status
    
    // Populate lists with pivot data
    selectedLists.value = chain.value.lists.map(list => ({
      ...list,
      pivot: {
        label: list.pivot.label || '',
        description: list.pivot.description || ''
      }
    }))
  } catch (error) {
    console.error('Error fetching chain:', error)
    chain.value = null
  } finally {
    loading.value = false
  }
}

const handleListSelect = (lists) => {
  lists.forEach(list => {
    if (!selectedLists.value.find(l => l.id === list.id)) {
      selectedLists.value.push({
        ...list,
        pivot: {
          label: '',
          description: ''
        }
      })
    }
  })
  showListSelector.value = false
}

const removeList = (index) => {
  selectedLists.value.splice(index, 1)
}

const saveChain = async () => {
  if (!canSave.value || saving.value) return

  saving.value = true
  try {
    const payload = {
      ...form,
      lists: selectedLists.value.map((list, index) => ({
        list_id: list.id,
        label: list.pivot.label || `Step ${index + 1}`,
        description: list.pivot.description || null
      }))
    }

    await axios.put(`/api/chains/${props.id}`, payload)
    
    showSuccess('Chain updated successfully!')
    router.push(`/chains/${props.id}`)
  } catch (error) {
    console.error('Error updating chain:', error)
    showError(error.response?.data?.message || 'Failed to update chain')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchChain()
})
</script>