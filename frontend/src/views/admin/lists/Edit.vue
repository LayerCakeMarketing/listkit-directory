<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Edit Form -->
      <div v-else-if="list">
        <div class="mb-6">
          <router-link
            to="/admin/lists"
            class="text-indigo-600 hover:text-indigo-900 flex items-center"
          >
            <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Lists
          </router-link>
        </div>

        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h1 class="text-xl font-semibold text-gray-900">Edit List</h1>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">ID: {{ list.id }}</span>
                <span class="text-sm text-gray-500">•</span>
                <span class="text-sm text-gray-500">Created: {{ formatDate(list.created_at) }}</span>
              </div>
            </div>
          </div>

          <form @submit.prevent="saveList" class="p-6 space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- List Name -->
              <div>
                <label for="list-name" class="block text-sm font-medium text-gray-700">
                  List Name
                </label>
                <input
                  id="list-name"
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>

              <!-- Slug -->
              <div>
                <label for="list-slug" class="block text-sm font-medium text-gray-700">
                  Slug
                </label>
                <input
                  id="list-slug"
                  v-model="form.slug"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
            </div>

            <!-- Description -->
            <div>
              <label for="list-description" class="block text-sm font-medium text-gray-700">
                Description
              </label>
              <textarea
                id="list-description"
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Category -->
              <div>
                <label for="list-category" class="block text-sm font-medium text-gray-700">
                  Category
                </label>
                <select
                  id="list-category"
                  v-model="form.category_id"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="">No Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <!-- Visibility -->
              <div>
                <label for="list-visibility" class="block text-sm font-medium text-gray-700">
                  Visibility
                </label>
                <select
                  id="list-visibility"
                  v-model="form.visibility"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="private">Private</option>
                  <option value="unlisted">Unlisted</option>
                  <option value="public">Public</option>
                </select>
              </div>

              <!-- Status -->
              <div>
                <label for="list-status" class="block text-sm font-medium text-gray-700">
                  Status
                </label>
                <select
                  id="list-status"
                  v-model="form.status"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="active">Active</option>
                  <option value="draft">Draft</option>
                  <option value="on_hold">On Hold</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
            </div>

            <!-- Admin Options -->
            <div class="border-t pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Options</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center">
                  <input
                    id="is-featured"
                    v-model="form.is_featured"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <label for="is-featured" class="ml-2 block text-sm text-gray-900">
                    Featured List
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="is-verified"
                    v-model="form.is_verified"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <label for="is-verified" class="ml-2 block text-sm text-gray-900">
                    Verified List
                  </label>
                </div>
              </div>
            </div>

            <!-- List Structure -->
            <div class="border-t pt-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">List Structure</h3>
                <div class="flex items-center space-x-4">
                  <span class="text-sm text-gray-500">
                    Structure Version: {{ list.structure_version || '1.0' }}
                  </span>
                  <button
                    v-if="list.structure_version !== '2.0'"
                    type="button"
                    @click="convertToSections"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                  >
                    Convert to Sections
                  </button>
                </div>
              </div>

              <!-- Sections View -->
              <div v-if="list.structure_version === '2.0' && sections.length > 0" class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-700 mb-3">Sections</h4>
                  <draggable
                    v-model="sections"
                    :item-key="(item) => item.id"
                    handle=".drag-handle"
                    class="space-y-3"
                  >
                    <template #item="{ element: section, index }">
                      <div class="flex items-center space-x-3 bg-white p-3 rounded-md shadow-sm">
                        <div class="drag-handle cursor-move">
                          <Bars3Icon class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="flex-1">
                          <input
                            v-model="section.title"
                            type="text"
                            class="block w-full text-sm border-gray-300 rounded-md"
                            placeholder="Section heading"
                          />
                          <p class="mt-1 text-xs text-gray-500">
                            {{ getSectionItemCount(section.id) }} items in this section
                          </p>
                        </div>
                        <button
                          type="button"
                          @click="removeSection(section.id)"
                          class="text-red-600 hover:text-red-700"
                        >
                          <XMarkIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </template>
                  </draggable>
                  <button
                    type="button"
                    @click="addSection"
                    class="mt-3 w-full py-2 px-3 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                  >
                    Add Section
                  </button>
                </div>
              </div>

              <!-- Items Preview -->
              <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                  List Items ({{ items.length }})
                </h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div v-if="items.length > 0" class="space-y-2">
                    <div
                      v-for="(item, index) in items.slice(0, 5)"
                      :key="item.id"
                      class="flex items-center justify-between bg-white p-3 rounded"
                    >
                      <div>
                        <span class="text-sm font-medium text-gray-900">
                          {{ index + 1 }}. {{ item.display_title || item.title }}
                        </span>
                        <span class="ml-2 text-xs text-gray-500">
                          ({{ item.type }})
                        </span>
                      </div>
                    </div>
                    <div v-if="items.length > 5" class="text-sm text-gray-500 text-center pt-2">
                      ... and {{ items.length - 5 }} more items
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500 text-center">
                    No items in this list
                  </div>
                  <router-link
                    :to="`/mylists/${list.id}/edit`"
                    class="mt-3 block w-full text-center py-2 px-3 border border-indigo-300 rounded-md text-sm text-indigo-700 hover:bg-indigo-50"
                  >
                    Edit List Items
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Owner Information -->
            <div class="border-t pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Owner Information</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <span class="text-sm font-medium text-gray-700">Owner Type:</span>
                    <span class="ml-2 text-sm text-gray-900">{{ list.owner_type }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-700">Owner:</span>
                    <span class="ml-2 text-sm text-gray-900">
                      {{ list.user?.name || list.channel?.name || 'Unknown' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center pt-6 border-t">
              <div class="flex space-x-3">
                <router-link
                  :to="`/@${list.user?.username || list.channel?.slug}/${list.slug}`"
                  target="_blank"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  <ArrowTopRightOnSquareIcon class="h-4 w-4 mr-2" />
                  View Public
                </router-link>
                <button
                  type="button"
                  @click="deleteList"
                  class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                >
                  Delete List
                </button>
              </div>
              <div class="flex space-x-3">
                <router-link
                  to="/admin/lists"
                  class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  Cancel
                </router-link>
                <button
                  type="submit"
                  :disabled="saving"
                  class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="text-center py-12">
        <h3 class="text-lg font-medium text-gray-900">List not found</h3>
        <p class="mt-2 text-sm text-gray-500">
          The list you're looking for doesn't exist.
        </p>
        <router-link
          to="/admin/lists"
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200"
        >
          Back to Lists
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'
import { Bars3Icon, XMarkIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import { useNotification } from '@/composables/useNotification'

const route = useRoute()
const router = useRouter()
const { showSuccess, showError } = useNotification()

const list = ref(null)
const loading = ref(true)
const saving = ref(false)
const categories = ref([])
const sections = ref([])
const items = ref([])

const form = reactive({
  name: '',
  slug: '',
  description: '',
  category_id: null,
  visibility: 'public',
  status: 'active',
  is_featured: false,
  is_verified: false
})

const getSectionItemCount = (sectionId) => {
  return items.value.filter(item => item.section_id === sectionId && !item.is_section).length
}

const fetchList = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/admin/lists/${route.params.id}`)
    list.value = response.data
    
    // Populate form
    form.name = list.value.name
    form.slug = list.value.slug
    form.description = list.value.description || ''
    form.category_id = list.value.category_id
    form.visibility = list.value.visibility
    form.status = list.value.status || 'active'
    form.is_featured = list.value.is_featured || false
    form.is_verified = list.value.is_verified || false
    
    // Load items and sections
    items.value = list.value.items || []
    if (list.value.structure_version === '2.0') {
      sections.value = list.value.sections || []
    }
  } catch (error) {
    console.error('Error fetching list:', error)
    list.value = null
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/admin/list-categories')
    categories.value = response.data
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

const saveList = async () => {
  saving.value = true
  try {
    await axios.put(`/api/admin/lists/${list.value.id}`, {
      ...form,
      sections: sections.value
    })
    showSuccess('List updated successfully')
  } catch (error) {
    showError('Failed to update list')
  } finally {
    saving.value = false
  }
}

const convertToSections = async () => {
  if (!confirm('Convert this list to use sections? This will create a default section for all existing items.')) {
    return
  }
  
  try {
    await axios.post(`/api/lists/${list.value.id}/convert-to-sections`)
    showSuccess('List converted to sections format')
    await fetchList()
  } catch (error) {
    showError('Failed to convert list format')
  }
}

const addSection = () => {
  const newSection = {
    id: `new-${Date.now()}`,
    title: 'New Section',
    order_index: sections.value.length
  }
  sections.value.push(newSection)
}

const removeSection = (sectionId) => {
  if (!confirm('Remove this section? Items will be moved to the default section.')) {
    return
  }
  sections.value = sections.value.filter(s => s.id !== sectionId)
}

const deleteList = async () => {
  if (!confirm('Are you sure you want to delete this list? This action cannot be undone.')) {
    return
  }
  
  try {
    await axios.delete(`/api/admin/lists/${list.value.id}`)
    showSuccess('List deleted successfully')
    router.push('/admin/lists')
  } catch (error) {
    showError('Failed to delete list')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchList()
  fetchCategories()
})
</script>