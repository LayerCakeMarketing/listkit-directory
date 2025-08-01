<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="closeDrawer">
      <TransitionChild
        as="template"
        enter="ease-in-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in-out duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <TransitionChild
              as="template"
              enter="transform transition ease-in-out duration-300"
              enter-from="translate-x-full"
              enter-to="translate-x-0"
              leave="transform transition ease-in-out duration-300"
              leave-from="translate-x-0"
              leave-to="translate-x-full"
            >
              <DialogPanel class="pointer-events-auto w-screen max-w-2xl">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                  <!-- Header -->
                  <div class="px-4 py-6 sm:px-6 border-b border-gray-200">
                    <div class="flex items-start justify-between">
                      <div>
                        <DialogTitle class="text-lg font-medium text-gray-900">
                          Edit {{ regionType }} Details
                        </DialogTitle>
                        <p class="mt-1 text-sm text-gray-500">
                          {{ region.name }}
                        </p>
                      </div>
                      <div class="ml-3 flex h-7 items-center">
                        <button
                          type="button"
                          class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                          @click="closeDrawer"
                        >
                          <span class="sr-only">Close panel</span>
                          <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Content -->
                  <div class="flex-1 px-4 py-6 sm:px-6">
                    <div class="space-y-6">
                      <!-- Tab Navigation -->
                      <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                          <button
                            v-for="tab in editTabs"
                            :key="tab.id"
                            @click="activeEditTab = tab.id"
                            :class="[
                              activeEditTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                          >
                            {{ tab.name }}
                          </button>
                        </nav>
                      </div>

                      <!-- Loading State -->
                      <div v-if="loading" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                      </div>

                      <!-- Error State -->
                      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800">{{ error }}</p>
                      </div>

                      <!-- Tab Content -->
                      <div v-else>
                        <!-- Facts Tab -->
                        <div v-if="activeEditTab === 'facts'">
                          <h3 class="text-base font-semibold text-gray-900 mb-4">{{ regionType }} Facts</h3>
                          <div class="space-y-4">
                            <div v-for="(field, index) in factFields" :key="field.key" class="space-y-2">
                              <label :for="`fact-${field.key}`" class="block text-sm font-medium text-gray-700">
                                {{ field.label }}
                              </label>
                              <input
                                :id="`fact-${field.key}`"
                                v-model="editData.facts[field.key]"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                :placeholder="field.placeholder"
                              />
                            </div>
                          </div>
                        </div>

                        <!-- State Symbols Tab (only for states) -->
                        <div v-if="activeEditTab === 'symbols' && region.type === 'state'">
                          <h3 class="text-base font-semibold text-gray-900 mb-4">State Symbols</h3>
                          <div class="space-y-4">
                            <div v-for="symbol in stateSymbolFields" :key="symbol.key" class="space-y-2">
                              <label :for="`symbol-${symbol.key}`" class="block text-sm font-medium text-gray-700">
                                {{ symbol.label }}
                              </label>
                              <input
                                :id="`symbol-${symbol.key}`"
                                v-model="editData.state_symbols[symbol.key]"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                :placeholder="symbol.placeholder"
                              />
                            </div>
                          </div>
                        </div>

                        <!-- Featured Places Tab -->
                        <div v-if="activeEditTab === 'places'">
                          <h3 class="text-base font-semibold text-gray-900 mb-4">Featured Places</h3>
                          <p class="text-sm text-gray-500 mb-4">
                            Manage featured places through the admin panel.
                          </p>
                          <a 
                            :href="`/admin/regions?id=${region.id}&tab=featured-places`" 
                            target="_blank"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                          >
                            Open in Admin Panel
                            <ArrowTopRightOnSquareIcon class="ml-2 -mr-1 h-4 w-4" />
                          </a>
                        </div>

                        <!-- Featured Lists Tab -->
                        <div v-if="activeEditTab === 'lists'">
                          <h3 class="text-base font-semibold text-gray-900 mb-4">Featured Lists</h3>
                          <p class="text-sm text-gray-500 mb-4">
                            Manage featured lists through the admin panel.
                          </p>
                          <a 
                            :href="`/admin/regions?id=${region.id}&tab=featured-lists`" 
                            target="_blank"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                          >
                            Open in Admin Panel
                            <ArrowTopRightOnSquareIcon class="ml-2 -mr-1 h-4 w-4" />
                          </a>
                        </div>

                        <!-- General Info Tab -->
                        <div v-if="activeEditTab === 'general'">
                          <h3 class="text-base font-semibold text-gray-900 mb-4">General Information</h3>
                          <div class="space-y-4">
                            <div>
                              <label for="intro-text" class="block text-sm font-medium text-gray-700 mb-2">
                                Introduction Text
                              </label>
                              <RichTextEditor
                                v-model="editData.intro_text"
                                placeholder="Brief introduction about this region..."
                                :min-height="150"
                              />
                            </div>

                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Image
                              </label>
                              
                              <!-- Current Image Preview -->
                              <div v-if="editData.cloudflare_image_id && !newCloudflareImageId" class="mb-4">
                                <div class="relative inline-block">
                                  <img 
                                    :src="editData.cover_image || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${editData.cloudflare_image_id}/mListCover`"
                                    alt="Current cover"
                                    class="h-32 w-auto rounded-lg object-cover"
                                  />
                                  <button
                                    @click="handleImageRemove"
                                    type="button"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                  >
                                    <XMarkIcon class="h-4 w-4" />
                                  </button>
                                </div>
                              </div>
                              
                              <!-- Cloudflare Uploader -->
                              <CloudflareDragDropUploader
                                v-model="newCloudflareImageId"
                                :max-files="1"
                                :max-file-size="14680064"
                                context="region_cover"
                                :show-preview="true"
                                @upload-success="handleImageUploadSuccess"
                                @upload-error="handleImageUploadError"
                              />
                            </div>

                            <div>
                              <label class="flex items-center">
                                <input
                                  v-model="editData.is_featured"
                                  type="checkbox"
                                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Featured Region</span>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Footer -->
                  <div class="flex flex-shrink-0 justify-end px-4 py-4 border-t border-gray-200">
                    <button
                      type="button"
                      class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                      @click="closeDrawer"
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                      @click="saveChanges"
                      :disabled="saving"
                    >
                      <span v-if="!saving">Save Changes</span>
                      <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                      </span>
                    </button>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  region: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const activeEditTab = ref('facts')
const newCloudflareImageId = ref(null)

const editData = ref({
  facts: {},
  state_symbols: {},
  intro_text: '',
  cover_image: '',
  cloudflare_image_id: null,
  is_featured: false
})

const regionType = computed(() => {
  const typeMap = {
    'state': 'State',
    'city': 'City',
    'neighborhood': 'Neighborhood'
  }
  return typeMap[props.region.type] || 'Region'
})

const editTabs = computed(() => {
  const tabs = [
    { id: 'general', name: 'General' },
    { id: 'facts', name: 'Facts' },
    { id: 'places', name: 'Featured Places' },
    { id: 'lists', name: 'Featured Lists' }
  ]
  
  if (props.region.type === 'state') {
    tabs.splice(2, 0, { id: 'symbols', name: 'State Symbols' })
  }
  
  return tabs
})

const factFields = [
  { key: 'population', label: 'Population', placeholder: 'e.g., 39.5 million' },
  { key: 'area_size', label: 'Area Size', placeholder: 'e.g., 163,696 sq mi' },
  { key: 'established_year', label: 'Established Year', placeholder: 'e.g., 1850' },
  { key: 'timezone', label: 'Time Zone', placeholder: 'e.g., Pacific Time Zone' },
  { key: 'elevation', label: 'Elevation', placeholder: 'e.g., Sea level to 14,505 ft' },
  { key: 'climate', label: 'Climate', placeholder: 'e.g., Mediterranean' },
  { key: 'known_for', label: 'Known For', placeholder: 'e.g., Golden Gate Bridge, Hollywood' },
  { key: 'top_industries', label: 'Top Industries', placeholder: 'e.g., Technology, Entertainment' },
  { key: 'famous_people', label: 'Famous People', placeholder: 'e.g., Steve Jobs, Clint Eastwood' },
  { key: 'local_food', label: 'Local Food', placeholder: 'e.g., Fish tacos, In-N-Out Burger' }
]

const stateSymbolFields = [
  { key: 'bird', label: 'State Bird', placeholder: 'e.g., California Valley Quail' },
  { key: 'flower', label: 'State Flower', placeholder: 'e.g., California Poppy' },
  { key: 'tree', label: 'State Tree', placeholder: 'e.g., Coast Redwood' },
  { key: 'mammal', label: 'State Mammal', placeholder: 'e.g., California Grizzly Bear' },
  { key: 'fish', label: 'State Fish', placeholder: 'e.g., California Golden Trout' },
  { key: 'song', label: 'State Song', placeholder: 'e.g., I Love You, California' }
]

// Initialize edit data when region changes
watch(() => props.region, (newRegion) => {
  if (newRegion) {
    editData.value = {
      facts: { ...newRegion.facts } || {},
      state_symbols: { ...newRegion.state_symbols } || {},
      intro_text: newRegion.intro_text || '',
      cover_image: newRegion.cover_image || '',
      cloudflare_image_id: newRegion.cloudflare_image_id || null,
      is_featured: newRegion.is_featured || false
    }
    // Reset new image when region changes
    newCloudflareImageId.value = null
  }
}, { immediate: true })

const closeDrawer = () => {
  emit('close')
}

const handleImageRemove = () => {
  newCloudflareImageId.value = null
  editData.value.cloudflare_image_id = null
  editData.value.cover_image = ''
}

const handleImageUploadSuccess = (result) => {
  console.log('Image upload success:', result)
  newCloudflareImageId.value = result.id
  editData.value.cloudflare_image_id = result.id
  editData.value.cover_image = result.url
}

const handleImageUploadError = (error) => {
  console.error('Image upload error:', error)
  error.value = error.message || 'Failed to upload image'
}

const saveChanges = async () => {
  try {
    saving.value = true
    error.value = null

    // Prepare the payload
    const payload = {
      facts: editData.value.facts,
      intro_text: editData.value.intro_text,
      is_featured: editData.value.is_featured,
      cloudflare_image_id: editData.value.cloudflare_image_id,
      cover_image: editData.value.cover_image
    }
    
    if (props.region.type === 'state') {
      const formattedStateSymbols = {}
      Object.keys(editData.value.state_symbols).forEach(key => {
        const value = editData.value.state_symbols[key]
        // Ensure value is a string before calling trim()
        if (value && typeof value === 'string' && value.trim()) {
          formattedStateSymbols[key] = value.trim()
        }
      })
      payload.state_symbols = formattedStateSymbols
    }

    const response = await axios.put(`/api/admin/regions/${props.region.id}`, payload)
    
    console.log('Region update response:', response.data)
    
    emit('updated', response.data)
    emit('close')
  } catch (err) {
    console.error('Error saving region:', err)
    error.value = err.response?.data?.message || 'Failed to save changes'
  } finally {
    saving.value = false
  }
}
</script>